<?php
/**
 * Robes 主题 · 站长心情 / 状态接口
 *
 *   POST action=save token=... emoji=... status=... text=...
 *
 * 仅站长（administrator）可写；数据落在 usr/cache/notice/mood.php，
 * 前台「小站」面板顶部直接读取渲染。
 */
$__root = dirname(__DIR__, 3);
if (!is_file($__root . '/config.inc.php')) {
    http_response_code(500);
    exit;
}
require_once $__root . '/config.inc.php';

/* 与前台一致的 Cookie 前缀，才能识别登录态 */
$__options = \Widget\Options::alloc();
\Typecho\Cookie::setPrefix(rtrim($__options->siteUrl, '/'));
if (defined('__TYPECHO_COOKIE_OPTIONS__')) {
    \Typecho\Cookie::setOptions(__TYPECHO_COOKIE_OPTIONS__);
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');

define('RB_MOOD_TEXT_MAX', 60);
define('RB_MOOD_STATUS_MAX', 8);
define('RB_MOOD_SONGS_MAX', 5);

function robesMoodOut($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function robesMoodFile()
{
    global $__root;
    $dir = $__root . '/usr/cache/notice';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (is_dir($dir) && !is_file($dir . '/index.html')) {
        @file_put_contents($dir . '/index.html', '');
    }
    return $dir . '/mood.php';
}

/** 去标签、去控制字符、限长 */
function robesMoodClean($raw, $max)
{
    $v = strip_tags((string) $raw);
    $v = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $v);
    $v = trim(preg_replace('/\s+/u', ' ', (string) $v));
    return mb_substr($v, 0, $max, 'UTF-8');
}

$user = \Widget\User::alloc();
/* 与前台编辑入口保持一致：登录即可进入，真正写库由下面的令牌校验把关 */
if (!$user->hasLogin()) {
    robesMoodOut(array('ok' => false, 'message' => '只有站长可以修改状态'), 403);
}

$db = \Typecho\Db::get();
$auth = $db->fetchObject($db->select('authCode')->from('table.users')->where('uid = ?', (int) $user->uid));
$expect = hash_hmac('sha256', 'robes-mood|' . (int) $user->uid, $auth ? (string) $auth->authCode : '');
$token = isset($_POST['token']) ? (string) $_POST['token'] : '';
if ($token === '' || !hash_equals($expect, $token)) {
    robesMoodOut(array('ok' => false, 'message' => '校验失败，请刷新页面重试'), 403);
}

$action = isset($_POST['action']) ? (string) $_POST['action'] : '';
if ($action !== 'save') {
    robesMoodOut(array('ok' => false, 'message' => 'bad action'), 400);
}

/** 试听歌曲：名称必填，链接只收 https，避免混合内容与脚本注入 */
function robesMoodSongItem($d)
{
    if (!is_array($d)) {
        return array();
    }
    $name = robesMoodClean(isset($d['name']) ? $d['name'] : '', 40);
    if ($name === '') {
        return array();
    }
    $href = function ($v) {
        $v = trim((string) $v);
        if ($v === '' || strlen($v) > 300) {
            return '';
        }
        return preg_match('#^https://[a-z0-9.\-]+(:\d+)?(/[^\s"\'<>\\\\]*)?$#i', $v) ? $v : '';
    };
    return array(
        'name' => $name,
        'artist' => robesMoodClean(isset($d['artist']) ? $d['artist'] : '', 30),
        'cover' => $href(isset($d['cover']) ? $d['cover'] : ''),
        'preview' => $href(isset($d['preview']) ? $d['preview'] : ''),
        'link' => $href(isset($d['link']) ? $d['link'] : ''),
    );
}

/** 单曲（旧字段 song）：兼容未升级的前端 */
function robesMoodSong($raw)
{
    return robesMoodSongItem(json_decode((string) $raw, true));
}

/** 歌单（字段 songs）：最多 5 首，按 名称+歌手 去重，保持用户排的顺序 */
function robesMoodSongs($raw)
{
    $list = json_decode((string) $raw, true);
    if (!is_array($list)) {
        return array();
    }
    $out = array();
    $seen = array();
    foreach ($list as $item) {
        $song = robesMoodSongItem($item);
        if (!$song) {
            continue;
        }
        $key = $song['name'] . "\n" . $song['artist'];
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $out[] = $song;
        if (count($out) >= RB_MOOD_SONGS_MAX) {
            break;
        }
    }
    return $out;
}

$emoji = robesMoodClean(isset($_POST['emoji']) ? $_POST['emoji'] : '', 4);
$status = robesMoodClean(isset($_POST['status']) ? $_POST['status'] : '', RB_MOOD_STATUS_MAX);
$text = robesMoodClean(isset($_POST['text']) ? $_POST['text'] : '', RB_MOOD_TEXT_MAX);

/* 没写状态就一并清掉表情，避免留下孤零零的 emoji */
if ($status === '') {
    $emoji = '';
}

/* 歌单优先；只传了单曲的老前端也能存进来 */
$songs = robesMoodSongs(isset($_POST['songs']) ? $_POST['songs'] : '');
if (!$songs) {
    $legacySong = robesMoodSong(isset($_POST['song']) ? $_POST['song'] : '');
    if ($legacySong) {
        $songs[] = $legacySong;
    }
}

$data = array(
    'emoji' => $emoji,
    'status' => $status,
    'text' => $text,
    'songs' => $songs,
    'song' => $songs ? $songs[0] : array(),
    'time' => time(),
);

$file = robesMoodFile();
$payload = "<?php exit;?>\n" . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (@file_put_contents($file, $payload, LOCK_EX) === false) {
    robesMoodOut(array('ok' => false, 'message' => '写入失败，请检查 usr/cache 目录权限'), 500);
}

robesMoodOut(array(
    'ok' => true,
    'mood' => $data,
    'timeText' => date('n月j日 H:i', $data['time']),
    'fallback' => '今天也在认真生活，慢慢来，别着急。',
));
