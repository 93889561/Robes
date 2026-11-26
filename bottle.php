<?php
/**
 * Robes 主题 · 漂流瓶接口
 *
 *   GET  action=pick&exclude=ID   -> 随机捞一个瓶子
 *   POST action=drop (nick,text)  -> 扔一个瓶子
 *
 * 数据落在 usr/cache/notice/bottles.json，不依赖数据库。
 */
$__root = dirname(__DIR__, 3);
if (!is_file($__root . '/config.inc.php')) {
    http_response_code(500);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');

define('RB_MAX_KEEP', 400);
define('RB_TEXT_MAX', 120);
define('RB_NICK_MAX', 12);
define('RB_COOLDOWN', 45);      // 两次投掷的最小间隔（秒）
define('RB_DAILY_MAX', 20);     // 每个 IP 每天最多投掷数量

function rbOut($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function rbDir()
{
    global $__root;
    $dir = $__root . '/usr/cache/notice';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    /* 防止目录被直接浏览 */
    if (is_dir($dir) && !is_file($dir . '/index.html')) {
        @file_put_contents($dir . '/index.html', '');
    }
    return $dir;
}

/** 存成 .php 并带 exit 头，避免被直接下载看到内容 */
function rbFile()
{
    return rbDir() . '/bottles.php';
}

function rbDecode($raw)
{
    $raw = (string) $raw;
    if (strpos($raw, '<?php') === 0) {
        $pos = strpos($raw, "\n");
        $raw = $pos === false ? '' : substr($raw, $pos + 1);
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : array();
}

/** 读取并清洗存储：只保留字段完整的记录 */
function rbReadAll()
{
    $file = rbFile();
    if (!is_file($file)) return array();
    $data = rbDecode(@file_get_contents($file));
    if (!is_array($data)) return array();
    $out = array();
    foreach ($data as $row) {
        if (!is_array($row) || !isset($row['t']) || $row['t'] === '') continue;
        $out[] = array(
            'id' => (int) (isset($row['id']) ? $row['id'] : 0),
            'n'  => (string) (isset($row['n']) ? $row['n'] : ''),
            't'  => (string) $row['t'],
            'ts' => (int) (isset($row['ts']) ? $row['ts'] : 0),
        );
    }
    return $out;
}

/** 加锁追加一条，返回瓶子 id（失败返回 0） */
function rbAppend($nick, $text)
{
    $file = rbFile();
    $fp = @fopen($file, 'c+');
    if (!$fp) return 0;
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return 0;
    }
    $raw = stream_get_contents($fp);
    $data = rbDecode($raw);

    $maxId = 0;
    foreach ($data as $row) {
        if (is_array($row) && isset($row['id'])) {
            $id = (int) $row['id'];
            if ($id > $maxId) $maxId = $id;
        }
    }
    $id = max($maxId + 1, time());
    $data[] = array('id' => $id, 'n' => $nick, 't' => $text, 'ts' => time());
    if (count($data) > RB_MAX_KEEP) {
        $data = array_slice($data, -RB_MAX_KEEP);
    }

    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, "<?php exit; ?>\n" . json_encode($data, JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $id;
}

/** 清洗文本：去标签、去控制字符、压缩空白 */
function rbClean($s, $max)
{
    $s = strip_tags((string) $s);
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s);
    $s = preg_replace('/\s+/u', ' ', (string) $s);
    $s = trim((string) $s);
    if (function_exists('mb_substr') && mb_strlen($s, 'UTF-8') > $max) {
        $s = mb_substr($s, 0, $max, 'UTF-8');
    }
    return $s;
}

/** 简单反垃圾：出现链接直接拒绝 */
function rbHasLink($s)
{
    return (bool) preg_match('~(https?://|www\.|[a-z0-9][a-z0-9-]*\.(com|cn|net|org|io|me|top|xyz|cc|vip)\b)~i', $s);
}

/** 真实访客 IP（站点在 EdgeOne 后面，优先取回源头） */
function rbClientIp()
{
    $keys = array('HTTP_EO_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR');
    foreach ($keys as $key) {
        if (empty($_SERVER[$key])) continue;
        $val = trim((string) $_SERVER[$key]);
        if ($key === 'HTTP_X_FORWARDED_FOR') {
            $parts = explode(',', $val);
            $val = trim($parts[0]);
        }
        if (filter_var($val, FILTER_VALIDATE_IP)) return $val;
    }
    return '0.0.0.0';
}

/** 投掷限流：返回 array(是否允许, 提示) */
function rbRateLimit($ip)
{
    $file = rbDir() . '/rl_' . md5($ip) . '.json';
    $now = time();
    $today = date('Ymd', $now);
    $data = array('last' => 0, 'day' => $today, 'n' => 0);

    if (is_file($file)) {
        $saved = json_decode((string) @file_get_contents($file), true);
        if (is_array($saved)) {
            if (isset($saved['last'])) $data['last'] = (int) $saved['last'];
            if (isset($saved['day'])) $data['day'] = (string) $saved['day'];
            if (isset($saved['n'])) $data['n'] = (int) $saved['n'];
        }
    }
    if ($data['day'] !== $today) {
        $data['day'] = $today;
        $data['n'] = 0;
    }
    if ($now - $data['last'] < RB_COOLDOWN) {
        $wait = RB_COOLDOWN - ($now - $data['last']);
        return array(false, '慢一点，' . $wait . ' 秒后再扔');
    }
    if ($data['n'] >= RB_DAILY_MAX) {
        return array(false, '今天扔得够多啦，明天再来');
    }

    $data['last'] = $now;
    $data['n']++;
    @file_put_contents($file, json_encode($data), LOCK_EX);
    return array(true, '');
}

$action = isset($_POST['action']) ? (string) $_POST['action'] : (isset($_GET['action']) ? (string) $_GET['action'] : '');
if (!in_array($action, array('pick', 'drop'), true)) {
    rbOut(array('ok' => false, 'message' => 'bad action'), 400);
}

if ($action === 'pick') {
    $all = rbReadAll();
    $total = count($all);
    if ($total === 0) {
        rbOut(array('ok' => true, 'bottle' => null, 'total' => 0));
    }
    $exclude = (int) (isset($_GET['exclude']) ? $_GET['exclude'] : 0);
    $pool = $all;
    if ($exclude > 0 && $total > 1) {
        $filtered = array();
        foreach ($all as $row) {
            if ($row['id'] !== $exclude) $filtered[] = $row;
        }
        if ($filtered) $pool = $filtered;
    }
    $item = $pool[array_rand($pool)];
    rbOut(array('ok' => true, 'bottle' => $item, 'total' => $total));
}

/* 投掷只接受 POST */
if (strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '') !== 'POST') {
    rbOut(array('ok' => false, 'message' => 'method not allowed'), 405);
}

$text = rbClean(isset($_POST['text']) ? $_POST['text'] : '', RB_TEXT_MAX);
$nick = rbClean(isset($_POST['nick']) ? $_POST['nick'] : '', RB_NICK_MAX);
if ($nick === '') $nick = '匿名';

$len = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
if ($len < 2) {
    rbOut(array('ok' => false, 'message' => '再多写几个字吧'), 400);
}
if (rbHasLink($text) || rbHasLink($nick)) {
    rbOut(array('ok' => false, 'message' => '瓶子里不能装链接哦'), 400);
}

list($allowed, $tip) = rbRateLimit(rbClientIp());
if (!$allowed) {
    rbOut(array('ok' => false, 'message' => $tip), 429);
}

$id = rbAppend($nick, $text);
if (!$id) {
    rbOut(array('ok' => false, 'message' => '海面有点风浪，稍后再试'), 500);
}

rbOut(array(
    'ok'     => true,
    'bottle' => array('id' => $id, 'n' => $nick, 't' => $text, 'ts' => time()),
));
