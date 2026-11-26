<?php
/**
 * Robes 图片预览缩略图
 * 用法：/usr/themes/robes/img.php?s=/usr/uploads/2022/02/x.jpg&w=1000
 * 首次访问生成并缓存到 /usr/uploads/.thumbs/，之后直接读缓存；原图不做任何修改。
 */
error_reporting(0);
@set_time_limit(30);

$__root  = dirname(__DIR__, 3);
$__up    = realpath($__root . '/usr/uploads');
$__cache = $__root . '/usr/uploads/.thumbs';

function robes_img_serve($file, $mime, $cache = 'public, max-age=31536000, immutable')
{
    header('Content-Type: ' . $mime);
    header('Cache-Control: ' . $cache);
    header('Access-Control-Allow-Origin: *');
    $etag = '"' . @md5_file($file) . '"';
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', @filemtime($file)) . ' GMT');
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
        http_response_code(304);
        exit;
    }
    header('Content-Length: ' . @filesize($file));
    @readfile($file);
    exit;
}

/** 从 JPEG 的 APP1(Exif) 段里取 Orientation，不依赖 exif 扩展 */
function robes_img_orientation($file)
{
    $fp = @fopen($file, 'rb');
    if (!$fp) return 1;
    $head = (string) @fread($fp, 65536);
    @fclose($fp);
    if (strlen($head) < 12 || substr($head, 0, 2) !== "\xFF\xD8") return 1;
    $pos = 2;
    $len = strlen($head);
    while ($pos + 4 <= $len) {
        if (ord($head[$pos]) !== 0xFF) { $pos++; continue; }
        $marker = ord($head[$pos + 1]);
        $pos += 2;
        /* 无长度的标记 */
        if ($marker === 0xD8 || $marker === 0x01 || ($marker >= 0xD0 && $marker <= 0xD7)) continue;
        if ($marker === 0xD9 || $marker === 0xDA) break;
        if ($pos + 2 > $len) break;
        $seg = unpack('n', substr($head, $pos, 2));
        $segLen = (int) $seg[1];
        if ($segLen < 2) break;
        if ($marker === 0xE1 && substr($head, $pos + 2, 6) === "Exif\0\0") {
            return robes_img_tiff_orientation(substr($head, $pos + 8, $segLen - 8));
        }
        $pos += $segLen;
    }
    return 1;
}

/** 解析 TIFF 头里的 0x0112 标签 */
function robes_img_tiff_orientation($tiff)
{
    $len = strlen($tiff);
    if ($len < 8) return 1;
    $bo = substr($tiff, 0, 2);
    if ($bo === 'II') {
        $fmt = 'v';
        $long = 'V';
    } elseif ($bo === 'MM') {
        $fmt = 'n';
        $long = 'N';
    } else {
        return 1;
    }
    $magic = unpack($fmt, substr($tiff, 2, 2));
    if ((int) $magic[1] !== 42) return 1;
    $off = unpack($long, substr($tiff, 4, 4));
    $ifd = (int) $off[1];
    if ($ifd < 8 || $ifd + 2 > $len) return 1;
    $cnt = unpack($fmt, substr($tiff, $ifd, 2));
    $count = (int) $cnt[1];
    $p = $ifd + 2;
    for ($i = 0; $i < $count; $i++) {
        if ($p + 12 > $len) break;
        $tag = unpack($fmt, substr($tiff, $p, 2));
        if ((int) $tag[1] === 0x0112) {
            $val = unpack($fmt, substr($tiff, $p + 8, 2));
            $o = (int) $val[1];
            return ($o >= 1 && $o <= 8) ? $o : 1;
        }
        $p += 12;
    }
    return 1;
}

$__src = isset($_GET['s']) ? (string) $_GET['s'] : '';
$__w   = isset($_GET['w']) ? (int) $_GET['w'] : 1000;
if ($__w < 200) $__w = 200;
if ($__w > 2000) $__w = 2000;

$__path = parse_url($__src, PHP_URL_PATH);
$__path = $__path ? ltrim($__path, '/') : '';
if ($__path === '' || strpos($__path, '..') !== false || strpos($__path, 'usr/uploads/') !== 0) {
    http_response_code(403);
    exit;
}

$__file = realpath($__root . '/' . $__path);
if (!$__up || !$__file || strpos($__file, $__up . '/') !== 0 || !is_file($__file)) {
    http_response_code(404);
    exit;
}

$__ext  = strtolower(pathinfo($__file, PATHINFO_EXTENSION));
$__mime = array(
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'webp' => 'image/webp', 'gif' => 'image/gif', 'svg' => 'image/svg+xml',
    'bmp' => 'image/bmp'
);
if (!isset($__mime[$__ext])) {
    http_response_code(404);
    exit;
}

/* 动图 / 矢量图 / 不支持的格式直接给原图 */
if (!in_array($__ext, array('jpg', 'jpeg', 'png', 'webp'))) {
    robes_img_serve($__file, $__mime[$__ext], 'public, max-age=3600');
}

$__info = @getimagesize($__file);
if (!$__info) {
    robes_img_serve($__file, $__mime[$__ext], 'public, max-age=3600');
}
$__sw = $__info[0];
$__sh = $__info[1];
/* 已在目标宽度内且本身是 WebP 才直接复用；JPEG/PNG 仍转码，避免首屏拉几百 KB 原图 */
if ($__sw <= $__w && $__ext === 'webp') {
    robes_img_serve($__file, $__mime[$__ext], 'public, max-age=3600');
}

/* 内存保护：先尝试把上限调到 512M，再按实际可用内存判断能否解码 */
$__mb = function ($v) {
    $v = trim((string) $v);
    if ($v === '' || $v === '-1') return -1;
    $n = (int) $v;
    switch (strtolower(substr($v, -1))) {
        case 'g': return $n * 1073741824;
        case 'm': return $n * 1048576;
        case 'k': return $n * 1024;
    }
    return $n;
};
$__cap = $__mb(ini_get('memory_limit'));
if ($__cap !== -1 && $__cap < 512 * 1024 * 1024) {
    @ini_set('memory_limit', '512M');
    $__cap = $__mb(ini_get('memory_limit'));
}
if ($__cap > 0 && $__sw * $__sh * 5 + 8 * 1048576 > $__cap * 0.8) {
    robes_img_serve($__file, $__mime[$__ext], 'public, max-age=3600');
}

/* 统一输出 WebP：避免 CDN 按 Accept 缓存出两种格式互相串台 */
$__webp = function_exists('imagewebp');
$__q    = 60;   /* WebP 质量：体积优先 */
$__fmt  = $__webp ? 'webp' : 'jpg';
$__key  = md5($__file . '|' . $__w . '|' . @filemtime($__file) . '|' . @filesize($__file) . '|' . $__fmt . '|' . $__q . '|v6');
$__out  = $__cache . '/' . $__key . '.' . $__fmt;

if (is_file($__out)) {
    robes_img_serve($__out, $__fmt === 'webp' ? 'image/webp' : 'image/jpeg');
}

if (!is_dir($__cache)) {
    @mkdir($__cache, 0755, true);
}

$__im = null;
switch ($__info[2]) {
    case IMAGETYPE_JPEG: $__im = @imagecreatefromjpeg($__file); break;
    case IMAGETYPE_PNG:  $__im = @imagecreatefrompng($__file);  break;
    case IMAGETYPE_WEBP: $__im = @imagecreatefromwebp($__file); break;
}
if (!$__im) {
    robes_img_serve($__file, $__mime[$__ext], 'public, max-age=3600');
}

/* EXIF 方向：手机竖拍的照片像素其实是横着存的，方向只写在 EXIF 里。
   GD 解码不会自动转正，缩略图就会歪；而灯箱读的是原图，浏览器会按 EXIF 摆正，
   于是「网格里歪、灯箱里正」。这里手动按 Orientation 转正。
   注意：服务器没装 exif 扩展，所以自己从 APP1 段里读，不依赖 exif_read_data()。 */
$__ori = 1;
if ($__info[2] === IMAGETYPE_JPEG) {
    $__ori = robes_img_orientation($__file);
}
if ($__ori >= 2 && $__ori <= 8) {
    $__deg = 0;
    if ($__ori === 2) {
        @imageflip($__im, IMG_FLIP_HORIZONTAL);
    } elseif ($__ori === 3) {
        $__deg = 180;
    } elseif ($__ori === 4) {
        @imageflip($__im, IMG_FLIP_VERTICAL);
    } elseif ($__ori === 5 || $__ori === 6 || $__ori === 7) {
        $__deg = -90;
    } elseif ($__ori === 8) {
        $__deg = 90;
    }
    if ($__deg) {
        $__rotated = @imagerotate($__im, $__deg, 0);
        if ($__rotated) {
            imagedestroy($__im);
            $__im = $__rotated;
        }
    }
    /* 5 / 7 是先转再镜像，顺序不能反 */
    if ($__ori === 5) {
        @imageflip($__im, IMG_FLIP_HORIZONTAL);
    } elseif ($__ori === 7) {
        @imageflip($__im, IMG_FLIP_VERTICAL);
    }
    /* 转正后宽高可能互换，重新取真实尺寸再算目标大小 */
    $__sw = imagesx($__im);
    $__sh = imagesy($__im);
    if ($__sw < 1 || $__sh < 1) {
        imagedestroy($__im);
        robes_img_serve($__file, $__mime[$__ext], 'public, max-age=3600');
    }
}

$__tw = min($__sw, $__w);
$__th = max(1, (int) round($__sh * $__tw / $__sw));
$__dst = @imagecreatetruecolor($__tw, $__th);
if (!$__dst) {
    imagedestroy($__im);
    robes_img_serve($__file, $__mime[$__ext], 'public, max-age=3600');
}

if ($__fmt === 'webp' && ($__info[2] === IMAGETYPE_PNG || $__info[2] === IMAGETYPE_WEBP)) {
    imagealphablending($__dst, false);
    imagesavealpha($__dst, true);
    $__tr = imagecolorallocatealpha($__dst, 0, 0, 0, 127);
    imagefill($__dst, 0, 0, $__tr);
}

imagecopyresampled($__dst, $__im, 0, 0, 0, 0, $__tw, $__th, $__sw, $__sh);
imagedestroy($__im);

$__ok = $__fmt === 'webp'
    ? @imagewebp($__dst, $__out, $__q)
    : @imagejpeg($__dst, $__out, 85);
imagedestroy($__dst);

if (!$__ok || !is_file($__out)) {
    robes_img_serve($__file, $__mime[$__ext], 'public, max-age=3600');
}

robes_img_serve($__out, $__fmt === 'webp' ? 'image/webp' : 'image/jpeg');
