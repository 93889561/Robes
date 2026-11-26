<?php
/**
 * Robes 主题 · 歌曲搜索 / 试听代理
 *
 *   ?action=search&q=晴天     返回歌曲列表
 *   ?action=audio&id=123456   代理播放（音乐外链 http、且带时效，统一由服务端转一手）
 *
 * 搜索：网易云 cloudsearch（带封面、会员标记）
 * 可播性：music-api.gdstudio.xyz 解析（并发探测，只判定能不能播）
 * 播放：同源代理 + Range 透传，避免混合内容、链接过期
 */
$__root = dirname(__DIR__, 3);
if (!is_file($__root . '/config.inc.php')) {
    http_response_code(500);
    exit;
}
require_once $__root . '/config.inc.php';

header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');

define('RB_SONG_API', 'https://music-api.gdstudio.xyz/api.php');

function robesSongOut($data, $code = 200)
{
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function robesSongHttps($url)
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    if (stripos($url, '//') === 0) {
        $url = 'https:' . $url;
    }
    return strlen($url) <= 500 ? $url : '';
}

/** 当前 song.php 的绝对地址 */
function robesSongBase()
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $dir = rtrim(str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/')), '/');
    return ($https ? 'https://' : 'http://') . $host . $dir . '/song.php';
}

function robesSongCacheDir()
{
    global $__root;
    $dir = $__root . '/usr/cache/notice/song';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (is_dir($dir) && !is_file($dir . '/index.html')) {
        @file_put_contents($dir . '/index.html', '');
    }
    return $dir;
}

function robesSongHttp($url, $post = null, $timeout = 10)
{
    if (!function_exists('curl_init')) {
        return '';
    }
    $ch = curl_init($url);
    $headers = array(
        'Accept: application/json, text/plain, */*',
        'Accept-Language: zh-CN,zh;q=0.9',
        'Referer: https://music.163.com/',
        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
    );
    $opts = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_ENCODING => '',
    );
    if ($post !== null) {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = http_build_query($post);
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    curl_close($ch);
    return $body === false ? '' : (string) $body;
}

/** 并发解析一批曲目能不能播，返回可播的 id 列表 */
function robesSongPlayable($ids)
{
    if (!$ids || !function_exists('curl_multi_init')) {
        return array();
    }
    $mh = curl_multi_init();
    $handles = array();
    foreach ($ids as $id) {
        $id = (int) $id;
        $ch = curl_init(RB_SONG_API . '?types=url&source=netease&br=320&id=' . $id);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 7,
            CURLOPT_HTTPHEADER => array('User-Agent: Mozilla/5.0'),
            CURLOPT_SSL_VERIFYPEER => true,
        ));
        curl_multi_add_handle($mh, $ch);
        $handles[$id] = $ch;
    }
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        if ($running > 0) {
            @curl_multi_select($mh, 0.3);
        }
    } while ($running > 0);
    $ok = array();
    foreach ($handles as $id => $ch) {
        $body = (string) curl_multi_getcontent($ch);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        $d = json_decode($body, true);
        if (is_array($d) && !empty($d['url']) && strikesongUrl($d['url'])) {
            $ok[] = $id;
        }
    }
    curl_multi_close($mh);

    /* 解析接口没给地址的，再走官方外链兜底 */
    $missing = array_values(array_diff(array_map('intval', $ids), $ok));
    if ($missing) {
        foreach (robesSongOuterMany($missing) as $id => $url) {
            $ok[] = $id;
        }
    }
    return $ok;
}

/** 统一成 https，避免 https 页面里 302 到 http 被浏览器拦截 */
function robesSongSecure($url)
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    if (stripos($url, '//') === 0) {
        $url = 'https:' . $url;
    }
    if (stripos($url, 'http://') === 0) {
        $url = 'https://' . substr($url, 7);
    }
    return strikesongUrl($url) ? $url : '';
}

function strikesongUrl($url)
{
    return (bool) preg_match('#^https?://[^\s"\']+$#i', (string) $url);
}

/** 网易云官方外链：只有 302 到 music.126.net 才算可播（VIP 会跳 /404） */
function robesSongOuterMany($ids)
{
    if (!$ids || !function_exists('curl_multi_init')) {
        return array();
    }
    $mh = curl_multi_init();
    $handles = array();
    foreach ($ids as $id) {
        $id = (int) $id;
        $ch = curl_init('https://music.163.com/song/media/outer/url?id=' . $id . '.mp3');
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => array(
                'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                'Referer: https://music.163.com/',
            ),
        ));
        curl_multi_add_handle($mh, $ch);
        $handles[$id] = $ch;
    }
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        if ($running > 0) {
            @curl_multi_select($mh, 0.3);
        }
    } while ($running > 0);
    $out = array();
    foreach ($handles as $id => $ch) {
        $raw = (string) curl_multi_getcontent($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        if ($code !== 302 || !preg_match('/^Location:\s*(\S+)/mi', $raw, $m)) {
            continue;
        }
        $loc = trim($m[1]);
        if (stripos($loc, '/404') !== false || stripos($loc, 'music.126.net') === false) {
            continue;
        }
        $url = robesSongSecure($loc);
        if ($url !== '') {
            $out[$id] = $url;
        }
    }
    curl_multi_close($mh);
    return $out;
}

/** 取一首歌的即时播放地址（有时效，不落盘长期保存） */
function robesSongResolve($id)
{
    $id = (int) $id;
    if ($id <= 0) {
        return '';
    }
    $dir = robesSongCacheDir();
    $file = $dir . '/u_' . $id . '.json';
    if (is_file($file) && time() - (int) filemtime($file) < 300) {
        $d = json_decode((string) @file_get_contents($file), true);
        if (is_array($d) && !empty($d['url'])) {
            $cachedUrl = robesSongSecure($d['url']);
            if ($cachedUrl !== '') {
                return $cachedUrl;
            }
        }
    }
    $url = '';
    $raw = robesSongHttp(RB_SONG_API . '?types=url&source=netease&br=320&id=' . $id, null, 8);
    $d = json_decode($raw, true);
    if (is_array($d) && !empty($d['url'])) {
        $url = robesSongSecure($d['url']);
    }
    /* 解析接口给不出地址（会员曲目常见），退回官方外链 */
    if ($url === '') {
        $outer = robesSongOuterMany(array($id));
        $url = isset($outer[$id]) ? $outer[$id] : '';
    }
    if ($url !== '') {
        @file_put_contents($file, json_encode(array('url' => $url), JSON_UNESCAPED_SLASHES), LOCK_EX);
        return $url;
    }
    return '';
}

$action = isset($_GET['action']) ? (string) $_GET['action'] : (isset($_POST['action']) ? (string) $_POST['action'] : 'search');

/* ================= 播放：跳到即时地址 ================= */
/* 音频直连走代理会被 CDN 截断，这里只做 302，播放交给音乐 CDN */
if ($action === 'audio' || $action === 'redirect') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        http_response_code(404);
        exit;
    }
    $src = robesSongResolve($id);
    if ($src === '') {
        http_response_code(404);
        exit;
    }
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Location: ' . $src, true, 302);
    exit;
}

/* ================= 歌词 ================= */
if ($action === 'lyric') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        robesSongOut(array('ok' => false, 'message' => '参数错误'), 400);
    }
    $raw = robesSongHttp('https://music.163.com/api/song/lyric?id=' . $id . '&lv=1', null, 8);
    $d = json_decode($raw, true);
    $lrc = '';
    if (is_array($d) && !empty($d['lrc']['lyric'])) {
        $lrc = (string) $d['lrc']['lyric'];
    }
    robesSongOut(array('ok' => true, 'lrc' => $lrc));
}

/* ================= 搜索 ================= */
$q = isset($_POST['q']) ? (string) $_POST['q'] : (isset($_GET['q']) ? (string) $_GET['q'] : '');
$q = strip_tags($q);
$q = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $q);
$q = trim(preg_replace('/\s+/u', ' ', (string) $q));
if ($q === '') {
    robesSongOut(array('ok' => false, 'message' => '请输入歌名'), 400);
}
$q = mb_substr($q, 0, 40, 'UTF-8');

$cacheDir = robesSongCacheDir();
$cacheFile = $cacheDir . '/' . md5('v4|' . $q) . '.json';
if (is_file($cacheFile) && time() - (int) filemtime($cacheFile) < 600) {
    $cached = (string) @file_get_contents($cacheFile);
    if ($cached !== '') {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo $cached;
        exit;
    }
}

$raw = robesSongHttp('https://music.163.com/api/cloudsearch/pc', array(
    's' => $q,
    'type' => 1,
    'limit' => 6,
    'offset' => 0,
));
$json = json_decode($raw, true);
$candidates = array();
if (is_array($json) && !empty($json['result']['songs']) && is_array($json['result']['songs'])) {
    foreach ($json['result']['songs'] as $song) {
        $name = isset($song['name']) ? trim((string) $song['name']) : '';
        $id = isset($song['id']) ? (int) $song['id'] : 0;
        if ($name === '' || $id <= 0) {
            continue;
        }
        $artist = '';
        if (!empty($song['ar']) && is_array($song['ar'])) {
            $names = array();
            foreach ($song['ar'] as $ar) {
                if (!empty($ar['name'])) {
                    $names[] = (string) $ar['name'];
                }
            }
            $artist = implode(' / ', array_slice($names, 0, 2));
        }
        $candidates[] = array(
            'id' => $id,
            'name' => mb_substr($name, 0, 40, 'UTF-8'),
            'artist' => mb_substr($artist, 0, 30, 'UTF-8'),
            'cover' => !empty($song['al']['picUrl']) ? robesSongHttps(str_replace('http://', 'https://', (string) $song['al']['picUrl'])) : '',
        );
    }
}

if (!$candidates) {
    robesSongOut(array('ok' => false, 'message' => '没搜到，换个关键词试试'), 200);
}

$ids = array();
foreach ($candidates as $c) {
    $ids[] = $c['id'];
}
$playable = robesSongPlayable($ids);

$base = robesSongBase();
$results = array();
foreach ($candidates as $c) {
    $can = in_array($c['id'], $playable, true);
    $results[] = array(
        'name' => $c['name'],
        'artist' => $c['artist'],
        'cover' => $c['cover'],
        'preview' => $can ? $base . '?action=redirect&id=' . $c['id'] : '',
        'link' => 'https://music.163.com/song?id=' . $c['id'],
    );
}

/* 能播的排前面 */
usort($results, function ($a, $b) {
    return ($a['preview'] !== '' ? 0 : 1) - ($b['preview'] !== '' ? 0 : 1);
});

$payload = json_encode(array('ok' => true, 'results' => $results), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@file_put_contents($cacheFile, $payload, LOCK_EX);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo $payload;
