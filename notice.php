<?php
/**
 * Robes 主题 · 评论通知接口
 *
 * 身份只认服务端登录态：\Widget\User::alloc()->hasLogin()，前端不传 uid。
 *   GET  action=count                      -> 未读评论数
 *   GET  action=list&tab=latest|mine&page=1 -> 列表 HTML（latest=全站最新评论，mine=我的评论）
 *   POST action=read&coid=N                -> 单条标记已读
 *   POST action=seen                       -> 全部标记已读
 */
$__root = dirname(__DIR__, 3);
if (!is_file($__root . '/config.inc.php')) {
    http_response_code(500);
    exit;
}
require_once $__root . '/config.inc.php';

$__options = \Widget\Options::alloc();
\Typecho\Cookie::setPrefix(rtrim($__options->siteUrl, '/'));
if (defined('__TYPECHO_COOKIE_OPTIONS__')) {
    \Typecho\Cookie::setOptions(__TYPECHO_COOKIE_OPTIONS__);
}
/* 独立入口没有走 index.php，需手动灌入路由表，Router::url 才能反解文章链接 */
\Typecho\Router::setRoutes($__options->routingTable);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');

function rnoOut($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** 身份 key 只允许字母数字，避免拼接出奇怪的文件名 */
function rnoKey($key)
{
    return preg_replace('/[^a-zA-Z0-9]/', '', (string) $key);
}

/** 「最后查看时间」按身份隔离存放（登录按 uid，游客按签名 Cookie 的 key） */
function rnoSeenFile($key)
{
    $dir = __TYPECHO_ROOT_DIR__ . '/usr/cache/notice';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir . '/seen_' . rnoKey($key) . '.txt';
}

function rnoLastSeen($key)
{
    $file = rnoSeenFile($key);
    if (!is_file($file)) {
        /* 首次使用不做历史回溯，从当前时刻开始计未读 */
        @file_put_contents($file, (string) time());
        return time();
    }
    return (int) file_get_contents($file);
}

/** 单独点开过的评论 id（「全部已读」后清空） */
function rnoReadFile($key)
{
    return dirname(rnoSeenFile($key)) . '/read_' . rnoKey($key) . '.json';
}

function rnoReadSet($key)
{
    $file = rnoReadFile($key);
    if (!is_file($file)) return array();
    $ids = json_decode((string) file_get_contents($file), true);
    $set = array();
    if (is_array($ids)) {
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) $set[$id] = 1;
        }
    }
    return $set;
}

function rnoWriteReadSet($key, $set)
{
    /* 只保留最近 300 条，避免文件无限增长 */
    $ids = array_slice(array_keys($set), -300);
    @file_put_contents(rnoReadFile($key), json_encode($ids));
}

function rnoPostUrl($row, $cid, $options)
{
    $route = (isset($row['type']) && $row['type'] === 'page') ? 'page' : 'post';
    /* 独立入口下 $options->index 会指向主题目录，这里统一用站点根地址做前缀 */
    $prefix = rtrim($options->siteUrl, '/') . '/';
    try {
        $url = \Typecho\Router::url($route, array(
            'cid'  => (int) $cid,
            'slug' => isset($row['slug']) ? $row['slug'] : '',
        ), $prefix);
        if ($url && $url !== '#') return $url;
    } catch (\Exception $e) {
        // 忽略，走下面的兜底
    }
    return $prefix . (int) $cid . '.html';
}

function rnoTimeText($ts)
{
    $diff = time() - (int) $ts;
    if ($diff < 60) return '刚刚';
    if ($diff < 3600) return floor($diff / 60) . ' 分钟前';
    if ($diff < 86400) return floor($diff / 3600) . ' 小时前';
    if ($diff < 2592000) return floor($diff / 86400) . ' 天前';
    return date('Y-m-d', $ts);
}

function rnoExcerpt($text, $limit = 80)
{
    $t = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)));
    if ($t === '') return '';
    if (mb_strlen($t, 'UTF-8') <= $limit) return $t;
    return mb_substr($t, 0, $limit, 'UTF-8') . '…';
}

function rnoAvatar($mail)
{
    return 'https://weavatar.com/avatar/' . md5(strtolower(trim((string) $mail))) . '?s=48&d=retro';
}

function rnoUnread($db, $uid, $ownedIds, $lastSeen, $read)
{
    /* 全站最新评论里，比「最后查看时间」更新、且没被单独点开过的条数 */
    $build = function ($withPrivate) use ($db, $lastSeen, $uid, $ownedIds) {
        $q = $db->select('coid')->from('table.comments')
            ->where('status = ?', 'approved')
            ->where('created > ?', $lastSeen)
            ->limit(1000);
        if ($uid > 0) {
            $q->where('authorId <> ?', $uid);
        } elseif ($ownedIds) {
            /* 游客排除自己发过的评论 */
            $q->where('coid NOT IN (' . implode(',', array_map('intval', $ownedIds)) . ')');
        }
        if ($withPrivate) $q->where('private = ?', 0);
        return $q;
    };
    try {
        $rows = $db->fetchAll($build(true));
    } catch (\Exception $e) {
        $rows = $db->fetchAll($build(false));
    }
    $n = 0;
    foreach ($rows as $row) {
        if (!isset($read[(int) $row['coid']])) $n++;
    }
    return $n;
}

/** 全站最新一条公开评论的时间戳：游客即使没有任何身份，也能据此判断「有没有新评论」 */
function rnoLatestComment($db)
{
    try {
        $row = $db->fetchRow(
            $db->select(array('MAX(created)' => 'm'))->from('table.comments')
                ->where('status = ?', 'approved')
                ->where('private = ?', 0)
        );
    } catch (\Exception $e) {
        $row = $db->fetchRow(
            $db->select(array('MAX(created)' => 'm'))->from('table.comments')
                ->where('status = ?', 'approved')
        );
    }
    return $row ? (int) $row['m'] : 0;
}

function rnoListQuery($db, $tab, $uid, $ownedIds, $withPrivate)
{
    /* 注意：select() 传数组会被当成「表达式 => 别名」，这里必须用逗号分隔的字符串 */
    $fields = 'coid, cid, parent, created, text, status, authorId, author, url, mail';
    if ($withPrivate) $fields .= ', private';

    $q = $db->select($fields)->from('table.comments');
    if ($tab === 'latest') {
        $q->where('status = ?', 'approved');
        /* 「最新评论」= 别人的新评论，避免自己发完立刻出现在自己的列表里 */
        if ($uid > 0) {
            $q->where('authorId <> ?', $uid);
        } elseif ($ownedIds) {
            $q->where('coid NOT IN (' . implode(',', array_map('intval', $ownedIds)) . ')');
        }
        /* 最新评论只展示公开评论，私密评论只在「我的评论」里出现 */
        if ($withPrivate) $q->where('private = ?', 0);
    } else {
        if ($uid > 0) {
            $q->where('authorId = ?', $uid);
        } else {
            /* 游客「我的评论」= 签名 Cookie 里记录并验签通过的评论 */
            $ids = $ownedIds ? implode(',', array_map('intval', $ownedIds)) : '0';
            $q->where('coid IN (' . $ids . ')');
        }
        /* 括号必须保留：否则 SQL 的 AND 优先级会把所有待审核评论也查出来 */
        $q->where("(status = 'approved' OR status = 'waiting')");
    }
    return $q;
}

function rnoCountList($db, $tab, $uid, $ownedIds, $withPrivate)
{
    $q = $db->select(array('COUNT(coid)' => 'n'))->from('table.comments');
    if ($tab === 'latest') {
        $q->where('status = ?', 'approved');
        if ($uid > 0) {
            $q->where('authorId <> ?', $uid);
        } elseif ($ownedIds) {
            $q->where('coid NOT IN (' . implode(',', array_map('intval', $ownedIds)) . ')');
        }
        if ($withPrivate) $q->where('private = ?', 0);
    } else {
        if ($uid > 0) {
            $q->where('authorId = ?', $uid);
        } else {
            $ids = $ownedIds ? implode(',', array_map('intval', $ownedIds)) : '0';
            $q->where('coid IN (' . $ids . ')');
        }
        /* 括号必须保留：否则 SQL 的 AND 优先级会把所有待审核评论也查出来 */
        $q->where("(status = 'approved' OR status = 'waiting')");
    }
    $row = $db->fetchRow($q);
    return $row ? (int) $row['n'] : 0;
}

$action = isset($_POST['action']) ? (string) $_POST['action'] : (isset($_GET['action']) ? (string) $_GET['action'] : '');
if (!in_array($action, array('count', 'list', 'read', 'seen'), true)) {
    rnoOut(array('ok' => false, 'message' => 'bad action'), 400);
}

$user = \Widget\User::alloc();
$isLogin = $user->hasLogin();

/* 游客身份复用私密评论那套签名 Cookie：同一浏览器 = 同一个人 */
if (!class_exists('RobesPrivateComments')) {
    $__privFile = __TYPECHO_ROOT_DIR__ . '/usr/themes/robes/comment-private.php';
    if (is_file($__privFile)) require_once $__privFile;
}

$uid = $isLogin ? (int) $user->uid : 0;
$ownedIds = (!$isLogin && class_exists('RobesPrivateComments')) ? RobesPrivateComments::mineIds() : array();
$guestKey = (!$isLogin && class_exists('RobesPrivateComments')) ? RobesPrivateComments::guestKey() : '';
/* 未登录且浏览器没有任何评论记录 → 没有可用的身份，未读恒为 0 */
$ownerKey = $isLogin ? (string) $uid : $guestKey;

$isAdmin = $isLogin && (bool) $user->pass('administrator', true);
$db = \Typecho\Db::get();
$lastSeen = $ownerKey !== '' ? rnoLastSeen($ownerKey) : time();
$read = $ownerKey !== '' ? rnoReadSet($ownerKey) : array();

if ($action === 'count') {
    $latest = rnoLatestComment($db);
    if ($ownerKey === '') rnoOut(array('ok' => true, 'unread' => 0, 'latest' => $latest));
    rnoOut(array('ok' => true, 'unread' => rnoUnread($db, $uid, $ownedIds, $lastSeen, $read), 'latest' => $latest));
}

if ($action === 'seen') {
    if ($ownerKey === '') rnoOut(array('ok' => true, 'unread' => 0));
    @file_put_contents(rnoSeenFile($ownerKey), (string) time());
    @unlink(rnoReadFile($ownerKey));
    rnoOut(array('ok' => true, 'unread' => 0));
}

if ($action === 'read') {
    if ($ownerKey === '') rnoOut(array('ok' => true, 'unread' => 0));
    $coid = (int) (isset($_POST['coid']) ? $_POST['coid'] : 0);
    if ($coid <= 0) rnoOut(array('ok' => false, 'message' => 'bad coid'), 400);

    /* 只允许标记「全站最新评论」里真实存在的公开评论，防止越权写入任意 coid */
    $valid = false;
    try {
        $valid = (bool) $db->fetchRow(
            $db->select('coid')->from('table.comments')
                ->where('coid = ?', $coid)
                ->where('status = ?', 'approved')
                ->where('private = ?', 0)
        );
    } catch (\Exception $e) {
        $valid = (bool) $db->fetchRow(
            $db->select('coid')->from('table.comments')
                ->where('coid = ?', $coid)
                ->where('status = ?', 'approved')
        );
    }
    if (!$valid) rnoOut(array('ok' => false, 'message' => 'not found'), 404);

    $read[$coid] = 1;
    rnoWriteReadSet($ownerKey, $read);
    rnoOut(array('ok' => true, 'unread' => rnoUnread($db, $uid, $ownedIds, $lastSeen, $read)));
}

$tab = isset($_REQUEST['tab']) ? (string) $_REQUEST['tab'] : 'latest';
if (!in_array($tab, array('latest', 'mine'), true)) $tab = 'latest';
$page = max(1, (int) (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$query = rnoListQuery($db, $tab, $uid, $ownedIds, true);
$rows = array();
$hasPrivate = true;
try {
    $rows = $db->fetchAll($query->order('created', \Typecho\Db::SORT_DESC)->limit($perPage)->offset($offset));
} catch (\Exception $e) {
    /* 兼容未启用私密评论（comments.private 列不存在）的库 */
    $hasPrivate = false;
    $query = rnoListQuery($db, $tab, $uid, $ownedIds, false);
    try {
        $rows = $db->fetchAll($query->order('created', \Typecho\Db::SORT_DESC)->limit($perPage)->offset($offset));
    } catch (\Throwable $e2) {
        rnoOut(array('ok' => false, 'message' => 'query failed'), 500);
    }
}
$total = rnoCountList($db, $tab, $uid, $ownedIds, $hasPrivate);

/* 批量取文章标题与链接 */
$cids = array();
foreach ($rows as $row) {
    $cid = (int) $row['cid'];
    if ($cid > 0) $cids[$cid] = 1;
}
$contents = array();
if ($cids) {
    try {
        $list = $db->fetchAll(
            $db->select('cid', 'title', 'slug', 'type')->from('table.contents')
                ->where('cid IN (' . implode(',', array_keys($cids)) . ')')
        );
        foreach ($list as $c) {
            $contents[(int) $c['cid']] = $c;
        }
    } catch (\Exception $e) {
        $contents = array();
    }
}

$html = '';
foreach ($rows as $row) {
    $coid    = (int) $row['coid'];
    $cid     = (int) $row['cid'];
    $created = (int) $row['created'];
    $isNew   = ($tab === 'latest' && $ownerKey !== '' && $created > $lastSeen && !isset($read[$coid]));
    /* 私密评论只对站长和作者本人可见：登录按 authorId，游客按签名 Cookie */
    $isOwner = $isLogin
        ? ((int) $row['authorId'] === $uid)
        : ((int) $row['authorId'] === 0 && in_array($coid, $ownedIds, true));
    $masked  = !$isAdmin && !empty($row['private']) && !$isOwner;

    $name = trim((string) $row['author']);
    if ($name === '') $name = '访客';
    $initial = mb_substr($name, 0, 1, 'UTF-8');
    if ($initial === '') $initial = '?';

    $article = isset($contents[$cid]) ? $contents[$cid] : array();
    $title = isset($article['title']) && $article['title'] !== '' ? $article['title'] : '文章';
    $url = rnoPostUrl($article, $cid, $__options) . '#comment-' . $coid;

    if ($masked) {
        $text = '<i class="ri-lock-line"></i> 私密回复，仅作者与站长可见';
    } else {
        $text = rnoExcerpt(isset($row['text']) ? $row['text'] : '');
        if ($text === '') $text = '（无内容）';
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    $waiting = ($tab === 'mine' && isset($row['status']) && $row['status'] === 'waiting');

    $html .= '<a class="notice-item' . ($isNew ? ' is-new' : '') . '" data-coid="' . $coid . '" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
        . '<span class="notice-avatar">'
        . '<span class="notice-avatar-text">' . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') . '</span>'
        . '<img src="' . htmlspecialchars(rnoAvatar(isset($row['mail']) ? $row['mail'] : ''), ENT_QUOTES, 'UTF-8') . '" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer" onerror="this.remove()">'
        . '</span>'
        . '<span class="notice-main">'
        . '<span class="notice-row1">'
        . '<span class="notice-name">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</span>'
        . ($waiting ? '<span class="notice-tag">审核中</span>' : '')
        . ($isNew ? '<span class="notice-dot" title="新回复"></span>' : '')
        . '<span class="notice-time">' . htmlspecialchars(rnoTimeText($created), ENT_QUOTES, 'UTF-8') . '</span>'
        . '</span>'
        . '<span class="notice-text">' . $text . '</span>'
        . '<span class="notice-post">《' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '》</span>'
        . '</span>'
        . '</a>';
}

if ($html === '') {
    $html = '<div class="notice-empty">'
        . '<i class="ri-chat-1-line"></i>'
        . '<p>' . ($tab === 'latest' ? '还没有新的评论' : '你还没有发布过评论') . '</p>'
        . '</div>';
}

rnoOut(array(
    'ok'      => true,
    'unread'  => $ownerKey !== '' ? rnoUnread($db, $uid, $ownedIds, $lastSeen, $read) : 0,
    'tab'     => $tab,
    'page'    => $page,
    'hasMore' => ($offset + count($rows)) < $total,
    'html'    => $html,
));
