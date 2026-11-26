<?php
/**
 * Robes 主题 · 评论删除接口
 *
 * 权限规则：
 *   - 站长（editor 及以上）：可删除任意评论，若评论有子回复则级联删除
 *   - 评论作者本人：仅可删除自己发布且没有任何子回复的评论
 *
 * 用法：
 *   POST action=whoami  coids=1,2,3   -> 返回每个 coid 是否可删 + 本次会话的删除令牌
 *   POST action=delete  coid=123&token=xxx
 */
$__root = dirname(__DIR__, 3);
if (!is_file($__root . '/config.inc.php')) {
    http_response_code(500);
    exit;
}
require_once $__root . '/config.inc.php';

/* 初始化 Cookie 前缀，确保能读到与前台一致的登录态 */
$__options = \Widget\Options::alloc();
\Typecho\Cookie::setPrefix(rtrim($__options->siteUrl, '/'));
if (defined('__TYPECHO_COOKIE_OPTIONS__')) {
    \Typecho\Cookie::setOptions(__TYPECHO_COOKIE_OPTIONS__);
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Robots-Tag: noindex');

/* 私密评论依赖 comments.private 字段，首次使用时自动补列 */
if (class_exists('RobesPrivateComments')) {
    RobesPrivateComments::ensureColumn();
}

function robesDelOut($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$__action = isset($_POST['action']) ? (string) $_POST['action'] : (isset($_GET['action']) ? (string) $_GET['action'] : '');
if (!in_array($__action, array('whoami', 'delete', 'pin', 'unpin'), true)) {
    robesDelOut(array('ok' => false, 'message' => 'bad action'), 400);
}

$user = \Widget\User::alloc();
if (!$user->hasLogin()) {
    robesDelOut(array('ok' => false, 'message' => 'not login'));
}

$uid   = (int) $user->uid;
$admin = (bool) $user->pass('editor', true);

$db = \Typecho\Db::get();

$authCode = (string) $db->fetchObject($db->select('authCode')->from('table.users')->where('uid = ?', $uid))->authCode;
$token = hash_hmac('sha256', 'robes-comment-del|' . $uid, $authCode);

/** 解析 coid 列表，最多 200 个，避免超长 SQL */
function robesDelIds($raw)
{
    $ids = array();
    foreach (explode(',', (string) $raw) as $one) {
        $id = (int) $one;
        if ($id > 0) {
            $ids[$id] = $id;
        }
        if (count($ids) >= 200) {
            break;
        }
    }
    return array_values($ids);
}

if ($__action === 'whoami') {
    $ids = robesDelIds(isset($_POST['coids']) ? $_POST['coids'] : '');
    $items = array();

    if ($ids) {
        $list = implode(',', $ids);
        $rows = $db->fetchAll($db->select('coid', 'authorId', 'status', 'private')->from('table.comments')->where("coid IN ($list)"));
        $childRows = $db->fetchAll(
            $db->select(array('COUNT(coid)' => 'n'), 'parent')
                ->from('table.comments')
                ->where("parent IN ($list)")
                ->group('parent')
        );
        $childMap = array();
        foreach ($childRows as $cr) {
            $childMap[(int) $cr['parent']] = (int) $cr['n'];
        }

        $mine = array();
        $priv = array();
        foreach ($rows as $row) {
            $mine[(int) $row['coid']] = ((int) $row['authorId'] === $uid);
            $priv[(int) $row['coid']] = !empty($row['private']);
        }

        foreach ($ids as $id) {
            $children = isset($childMap[$id]) ? $childMap[$id] : 0;
            if (!isset($mine[$id])) {
                $items[] = array('coid' => $id, 'deletable' => false, 'reason' => 'gone', 'children' => 0);
            } elseif ($admin) {
                $items[] = array('coid' => $id, 'deletable' => true, 'reason' => '', 'children' => $children);
            } elseif (!empty($priv[$id])) {
                $items[] = array('coid' => $id, 'deletable' => false, 'reason' => 'private', 'children' => $children);
            } elseif (!$mine[$id]) {
                $items[] = array('coid' => $id, 'deletable' => false, 'reason' => 'not-owner', 'children' => $children);
            } elseif ($children > 0) {
                $items[] = array('coid' => $id, 'deletable' => false, 'reason' => 'has-children', 'children' => $children);
            } else {
                $items[] = array('coid' => $id, 'deletable' => true, 'reason' => '', 'children' => 0);
            }
        }
    }

    robesDelOut(array('ok' => true, 'admin' => $admin, 'token' => $token, 'items' => $items));
}

/* ---------------- delete ---------------- */

$given = isset($_POST['token']) ? (string) $_POST['token'] : '';
if (!$given || !hash_equals($token, $given)) {
    robesDelOut(array('ok' => false, 'message' => 'bad token'), 403);
}

$coid = isset($_POST['coid']) ? (int) $_POST['coid'] : 0;
if ($coid <= 0) {
    robesDelOut(array('ok' => false, 'message' => 'bad coid'), 400);
}

/* ---------------- pin / unpin ---------------- */

if ($__action === 'pin' || $__action === 'unpin') {
    if (!$admin) {
        robesDelOut(array('ok' => false, 'message' => '没有置顶权限'), 403);
    }
    $pinRow = $db->fetchRow($db->select('coid', 'parent', 'status')->from('table.comments')->where('coid = ?', $coid)->limit(1));
    if (!$pinRow) {
        robesDelOut(array('ok' => false, 'message' => '评论不存在或已被删除'));
    }
    if ((int) $pinRow['parent'] !== 0) {
        robesDelOut(array('ok' => false, 'message' => '只能置顶顶层评论'), 400);
    }
    $pinValue = $__action === 'pin' ? time() : 0;
    $db->query($db->update('table.comments')->rows(array('pinned' => $pinValue))->where('coid = ?', $coid));
    robesDelOut(array('ok' => true, 'coid' => $coid, 'pinned' => $pinValue ? 1 : 0));
}

$row = $db->fetchRow($db->select('coid', 'cid', 'authorId', 'status')->from('table.comments')->where('coid = ?', $coid)->limit(1));
if (!$row) {
    robesDelOut(array('ok' => false, 'message' => '评论不存在或已被删除'));
}

if (!$admin && (int) $row['authorId'] !== $uid) {
    robesDelOut(array('ok' => false, 'message' => '只能删除自己的评论'), 403);
}

$children = (int) $db->fetchObject(
    $db->select(array('COUNT(coid)' => 'n'))->from('table.comments')->where('parent = ?', $coid)
)->n;

if (!$admin && $children > 0) {
    robesDelOut(array('ok' => false, 'message' => '该评论已有 ' . $children . ' 条回复，无法删除'), 403);
}

/** 站长删除：先把所有后代收集起来，级联删除 */
$deleteIds = array($coid);
if ($admin) {
    $queue = array($coid);
    while ($queue) {
        $list = implode(',', array_map('intval', $queue));
        $kids = $db->fetchAll($db->select('coid')->from('table.comments')->where("parent IN ($list)"));
        $queue = array();
        foreach ($kids as $kid) {
            $kidId = (int) $kid['coid'];
            if (!in_array($kidId, $deleteIds, true)) {
                $deleteIds[] = $kidId;
                $queue[] = $kidId;
            }
        }
    }
}

$list = implode(',', array_map('intval', $deleteIds));
$full = $db->fetchAll($db->select('coid', 'cid', 'status')->from('table.comments')->where("coid IN ($list)"));

$db->query($db->delete('table.comments')->where("coid IN ($list)"));

$perCid = array();
foreach ($full as $one) {
    if ($one['status'] === 'approved') {
        $cid = (int) $one['cid'];
        $perCid[$cid] = isset($perCid[$cid]) ? $perCid[$cid] + 1 : 1;
    }
}
foreach ($perCid as $cid => $num) {
    for ($i = 0; $i < $num; $i++) {
        $db->query($db->update('table.contents')
            ->expression('commentsNum', 'commentsNum - 1')
            ->where('cid = ? AND commentsNum > 0', $cid));
    }
}

robesDelOut(array(
    'ok'      => true,
    'deleted' => $deleteIds,
    'count'   => count($deleteIds),
));
