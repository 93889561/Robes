<?php
/**
 * Robes 主题 · 友链申请提交
 *
 * 独立接口：接收表单 POST，校验后邮件通知站长（复用站点评论通知的 SMTP/收件箱），
 * 不写入评论，也不落盘存档（仅 IP 频率限制所需的计数文件）。
 * 返回 JSON。
 */
$__root = dirname(__DIR__, 3);
if (!is_file($__root . '/config.inc.php')) {
    http_response_code(500);
    exit;
}
require_once $__root . '/config.inc.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');
header('Cache-Control: no-store');

function raOut($ok, $msg = '', $code = 200)
{
    http_response_code($code);
    echo json_encode(array('ok' => (bool) $ok, 'message' => $msg), JSON_UNESCAPED_UNICODE);
    exit;
}

if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
    raOut(false, '仅支持 POST 提交', 405);
}

/* 蜜罐：机器人填了就静默成功，不落库不发信 */
if (!empty($_POST['hp'])) {
    raOut(true, '提交成功，我们会尽快审核');
}

$name = trim((string) (isset($_POST['name']) ? $_POST['name'] : ''));
$url  = trim((string) (isset($_POST['url']) ? $_POST['url'] : ''));
$mail = trim((string) (isset($_POST['email']) ? $_POST['email'] : ''));
$desc = trim((string) (isset($_POST['desc']) ? $_POST['desc'] : ''));
$avatar = trim((string) (isset($_POST['avatar']) ? $_POST['avatar'] : ''));

$name = preg_replace('/[\x00-\x1F\x7F]+/u', '', $name);
$desc = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $desc);
$name = mb_substr((string) $name, 0, 40, 'UTF-8');
$desc = mb_substr((string) $desc, 0, 200, 'UTF-8');
$url  = mb_substr($url, 0, 200, 'UTF-8');
$avatar = mb_substr($avatar, 0, 200, 'UTF-8');

if ($name === '' || $url === '' || $mail === '' || $desc === '') {
    raOut(false, '请填写完整的站点名称、地址、邮箱和简介', 400);
}
if (!preg_match('#^https?://[^\s]+$#i', $url)) {
    raOut(false, '站点地址需以 http:// 或 https:// 开头', 400);
}
if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    raOut(false, '联系邮箱格式不正确', 400);
}
if ($avatar !== '' && !preg_match('#^https?://[^\s]+$#i', $avatar)) {
    raOut(false, '头像地址需以 http:// 或 https:// 开头', 400);
}
/* 头像留空：用站点自身的 favicon 兜底 */
if ($avatar === '') {
    $host = parse_url($url, PHP_URL_HOST);
    if ($host) {
        $avatar = 'https://' . $host . '/favicon.ico';
    }
}

/* ---------- 频率限制：同 IP 60 秒 1 次，每小时最多 5 次 ---------- */
$ip  = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
$dir = $__root . '/usr/cache/links-apply';
if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
}
if (is_dir($dir) && !is_file($dir . '/index.html')) {
    @file_put_contents($dir . '/index.html', '');
}
$now  = time();
$lock = $dir . '/ip_' . md5($ip) . '.log';
$logs = is_file($lock) ? json_decode((string) @file_get_contents($lock), true) : array();
$logs = is_array($logs) ? array_values(array_filter($logs, function ($t) use ($now) {
    return is_numeric($t) && $now - (int) $t < 3600;
})) : array();
if (count($logs) >= 5) {
    raOut(false, '提交次数过多，请一小时后再试', 429);
}
if ($logs && $now - (int) end($logs) < 60) {
    raOut(false, '刚提交过啦，请 1 分钟后再试', 429);
}
$logs[] = $now;
@file_put_contents($lock, json_encode($logs), LOCK_EX);

/* ---------- 通知站长：仅发邮件，不落盘存档 ---------- */
$record = array(
    'time'  => date('Y-m-d H:i:s'),
    'name'  => $name,
    'url'   => $url,
    'email' => $mail,
    'avatar' => $avatar,
    'desc'  => $desc,
    'ip'    => $ip,
);
raNotify($record);

raOut(true, '提交成功，我们会尽快审核');

/**
 * 读取主题配置（theme:robes 存的是 JSON）
 */
function raOption($key, $default = '')
{
    static $opts = null;
    if ($opts === null) {
        $opts = array();
        try {
            $db  = \Typecho\Db::get();
            $row = $db->fetchRow($db->select('value')->from('table.options')->where('name = ?', 'theme:robes'));
            $raw = isset($row['value']) ? (string) $row['value'] : '';
            $d   = json_decode($raw, true);
            if (!is_array($d)) {
                $d = @unserialize($raw);
            }
            if (is_array($d)) {
                $opts = $d;
            }
        } catch (Throwable $e) {
            $opts = array();
        }
    }
    return isset($opts[$key]) && $opts[$key] !== '' ? $opts[$key] : $default;
}

function raSiteOption($key)
{
    try {
        $db  = \Typecho\Db::get();
        $row = $db->fetchRow($db->select('value')->from('table.options')->where('name = ?', $key));
        return isset($row['value']) ? (string) $row['value'] : '';
    } catch (Throwable $e) {
        return '';
    }
}

/**
 * 邮件通知：优先 SMTP（复用主题的 comment-mail/PHPMailer），失败回退 mail()
 */
function raNotify($rec)
{
    $emails = array();
    $custom = (string) raOption('cmMail', '');
    if ($custom !== '') {
        foreach (preg_split('/[,，\s]+/u', $custom) as $one) {
            if (filter_var($one, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $one;
            }
        }
    }
    if (!$emails) {
        $admin = raSiteOption('mail');
        if (filter_var($admin, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $admin;
        }
    }
    if (!$emails) {
        return;
    }

    $site = raSiteOption('title');
    $site = $site !== '' ? $site : 'Robes';
    $subject = '【友链申请】' . $rec['name'];

    $rows = '';
    foreach (array('站点名称' => $rec['name'], '站点地址' => $rec['url'], '联系邮箱' => $rec['email'], '头像地址' => $rec['avatar'], '站点简介' => $rec['desc'], '提交时间' => $rec['time'], '来源 IP' => $rec['ip']) as $k => $v) {
        $rows .= '<tr><td style="padding:10px 14px;color:#6b7a90;font-size:13px;white-space:nowrap;border-bottom:1px solid #eef1f5">'
            . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td style="padding:10px 14px;color:#1f2a3a;font-size:14px;border-bottom:1px solid #eef1f5">'
            . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    }
    $html = '<div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,\'PingFang SC\',\'Microsoft YaHei\',sans-serif;max-width:560px;margin:0 auto;border:1px solid #e6ebf2;border-radius:14px;overflow:hidden">'
        . '<div style="padding:18px 22px;background:#f7f9fc;border-bottom:1px solid #e6ebf2">'
        . '<div style="font-size:15px;font-weight:600;color:#1f2a3a">' . htmlspecialchars($site, ENT_QUOTES, 'UTF-8') . ' · 新的友链申请</div>'
        . '<div style="margin-top:4px;font-size:12px;color:#8b98ab">' . htmlspecialchars($rec['time'], ENT_QUOTES, 'UTF-8') . '</div></div>'
        . '<table style="width:100%;border-collapse:collapse">' . $rows . '</table>'
        . '<div style="padding:16px 22px;background:#f7f9fc;font-size:12px;color:#8b98ab">直接回复此邮件即可联系申请人</div>'
        . '</div>';

    $fromName = raSiteOption('title');
    $fromName = $fromName !== '' ? $fromName : 'Robes';
    $smtpHost = (string) raOption('cmSmtpHost', '');
    $smtpUser = (string) raOption('cmSmtpUser', '');
    $from     = filter_var($smtpUser, FILTER_VALIDATE_EMAIL) ? $smtpUser : ($emails[0]);

    if ($smtpHost !== '') {
        try {
            raMailer($from, $fromName)->sendAll($emails, $subject, $html);
            return;
        } catch (Throwable $e) {
            /* 落到 mail() */
        }
    }

    $headers = 'MIME-Version: 1.0' . "\r\n"
        . 'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $from . '>' . "\r\n"
        . 'Content-Type: text/html; charset=UTF-8' . "\r\n"
        . 'Content-Transfer-Encoding: base64';
    @mail(implode(',', $emails), '=?UTF-8?B?' . base64_encode($subject) . '?=', chunk_split(base64_encode($html)), $headers, '-f' . $from);
}

/**
 * 构造一个简易 SMTP 发送器（依赖主题自带的 PHPMailer）
 */
function raMailer($from, $fromName)
{
    $base = __DIR__ . '/comment-mail';
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer') && is_dir($base . '/PHPMailer')) {
        require_once $base . '/PHPMailer/Exception.php';
        require_once $base . '/PHPMailer/PHPMailer.php';
        require_once $base . '/PHPMailer/SMTP.php';
    }
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        throw new RuntimeException('PHPMailer 不可用');
    }
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host       = (string) raOption('cmSmtpHost', '');
    $mail->SMTPAuth   = raOption('cmSmtpUser', '') !== '';
    $mail->Username   = (string) raOption('cmSmtpUser', '');
    $mail->Password   = (string) raOption('cmSmtpPass', '');
    $mail->Port       = (int) raOption('cmSmtpPort', '465');
    $secure           = (string) raOption('cmSmtpSecure', 'ssl');
    $mail->SMTPSecure = $secure === 'none' ? '' : $secure;
    if ($secure === 'none') {
        $mail->SMTPAutoTLS = false;
    }
    $mail->Timeout = 12;
    $mail->setFrom($from, $fromName);

    return new class($mail, $from, $fromName) {
        private $mail;
        private $from;
        private $fromName;

        public function __construct($mail, $from, $fromName)
        {
            $this->mail     = $mail;
            $this->from     = $from;
            $this->fromName = $fromName;
        }

        public function sendAll(array $to, $subject, $html)
        {
            foreach ($to as $addr) {
                $this->mail->addAddress($addr);
            }
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $html;
            $this->mail->AltBody = trim(preg_replace('/\s+/u', ' ', strip_tags($html)));
            $this->mail->send();
            $this->mail->clearAddresses();
        }
    };
}
