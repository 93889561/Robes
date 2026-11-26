<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$GLOBALS['isLogin'] = $this->user->hasLogin();
$GLOBALS['cmtIsAdmin'] = $GLOBALS['isLogin'] && $this->user->pass('administrator', true);

function getFriendUrls() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = array();
    try {
        $db = \Typecho\Db::get();
        $prefix = $db->getPrefix();
        try {
            $rows = $db->fetchAll($db->select('url')->from($prefix . 'links'));
        } catch (Exception $e) {
            $rows = $db->fetchAll($db->select('url')->from('table.links'));
        }
        foreach ($rows as $row) {
            if (!empty($row['url'])) {
                $cache[] = rtrim(trim($row['url']), '/');
            }
        }
    } catch (Exception $e) {
        $cache = array();
    }
    return $cache;
}

function isFriend($url) {
    if (empty($url)) return false;
    $normalized = rtrim(trim($url), '/');
    $friends = getFriendUrls();
    foreach ($friends as $f) {
        if (strcasecmp($f, $normalized) === 0) return true;
    }
    return false;
}

function getCommentLevel($mail) {
    if (empty($mail)) return 0;
    static $cache = array();
    if (isset($cache[$mail])) return $cache[$mail];
    try {
        $count = \Typecho\Db::get()->fetchObject(
            \Typecho\Db::get()->select(array('COUNT(coid)' => 'total'))
                ->from('table.comments')->where('mail = ?', $mail)->where('status = ?', 'approved')->where('private = ?', 0)
        )->total;
        $cache[$mail] = $count >= 100 ? 6 : ($count >= 50 ? 5 : ($count >= 30 ? 4 : ($count >= 15 ? 3 : ($count >= 5 ? 2 : 1))));
    } catch (Exception $e) {
        $cache[$mail] = 1;
    }
    return $cache[$mail];
}

function getCommentDevice($agent) {
    if (empty($agent)) return array();
    $ua = strtolower($agent);
    $os = '';
    $icon = 'display';
    if (strpos($ua, 'windows') !== false) { $os = 'Windows'; $icon = 'windows'; }
    elseif (strpos($ua, 'android') !== false) { $os = 'Android'; $icon = 'android'; }
    elseif (strpos($ua, 'iphone') !== false) { $os = 'iPhone'; $icon = 'apple'; }
    elseif (strpos($ua, 'ipad') !== false) { $os = 'iPad'; $icon = 'apple'; }
    elseif (strpos($ua, 'mac os x') !== false || strpos($ua, 'macintosh') !== false) { $os = 'Mac'; $icon = 'apple'; }
    elseif (strpos($ua, 'cros') !== false) { $os = 'ChromeOS'; $icon = 'linux'; }
    elseif (strpos($ua, 'linux') !== false) { $os = 'Linux'; $icon = 'linux'; }
    $br = '';
    if (strpos($ua, 'edg') !== false) { $br = 'Edge'; }
    elseif (strpos($ua, 'firefox') !== false) { $br = 'Firefox'; }
    elseif (strpos($ua, 'chrome') !== false || strpos($ua, 'crios') !== false) { $br = 'Chrome'; }
    elseif (strpos($ua, 'safari') !== false) { $br = 'Safari'; }
    return array('os' => $os, 'browser' => $br, 'icon' => $icon);
}

/** 设备图标（内联 SVG 雪碧图引用，跟随系统显示） */
function cmtDeviceIcon($device) {
    $map = array(
        'windows' => 'cmt-ico-windows',
        'android' => 'cmt-ico-android',
        'apple'   => 'cmt-ico-apple',
        'linux'   => 'cmt-ico-linux',
    );
    $key = !empty($device['icon']) ? $device['icon'] : '';
    $id = isset($map[$key]) ? $map[$key] : 'cmt-ico-display';
    return '<svg class="cmt-dev-icon" aria-hidden="true" focusable="false"><use href="#' . $id . '"></use></svg>';
}

/**
 * 计算需要遮挡的私密评论
 *
 * 站长始终可见；评论作者本人（登录用户按 authorId，游客按签名 Cookie）可见；
 * 其他访问者内容替换为马赛克。私密评论的全部后代一并遮挡，
 * 避免通过回复内容反推私密内容。
 *
 * @param array $rows 评论行（需包含 coid / parent / private / authorId）
 * @param bool  $isAdmin 是否站长
 * @param int   $uid 当前登录用户 id（游客为 0）
 * @return array coid => 1
 */
function cmtPrivateMask($rows, $isAdmin, $uid) {
    $masked = array();
    if ($isAdmin) return $masked;

    foreach ($rows as $row) {
        if (empty($row['private'])) continue;
        $coid = (int) $row['coid'];
        $authorId = isset($row['authorId']) ? (int) $row['authorId'] : 0;
        if ($authorId > 0) {
            if ($uid > 0 && $authorId === $uid) continue;
        } elseif (class_exists('RobesPrivateComments') && RobesPrivateComments::owns($coid)) {
            continue;
        }
        $masked[$coid] = 1;
    }

    if (!$masked) return $masked;
    $changed = true;
    while ($changed) {
        $changed = false;
        foreach ($rows as $row) {
            $coid = (int) $row['coid'];
            $parent = (int) $row['parent'];
            if ($parent > 0 && isset($masked[$parent]) && !isset($masked[$coid])) {
                $masked[$coid] = 1;
                $changed = true;
            }
        }
    }
    return $masked;
}

/** 置顶评论（DB 行）内容格式化，与 Typecho 前台输出保持一致 */
function cmtFormatContent($text, $options) {
    try {
        if (!empty($options->commentsMarkdown)) {
            $html = \Utils\Markdown::convert((string) $text);
        } else {
            $parser = new \Utils\AutoP();
            $html = $parser->parse((string) $text);
        }
        return \Typecho\Common::stripTags($html, '<p><br>' . (string) $options->commentsHTMLTagAllowed);
    } catch (Exception $e) {
        return nl2br(htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'));
    }
}

/** 把评论正文里的裸链接自动变成可点击链接：只处理文本节点，
    跳过已有的 <a>、<code>、<pre>，避免破坏用户自己写的 HTML 与代码块 */
function cmtLinkify($html) {
    $html = (string) $html;
    if ($html === '') return $html;
    $parts = preg_split('/(<[^>]*>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!is_array($parts)) return $html;
    $inA = 0;
    $skip = 0;
    foreach ($parts as $i => $seg) {
        if ($seg === '') continue;
        if ($seg[0] === '<') {
            if (preg_match('/^<\s*(\/?)\s*([a-zA-Z0-9]+)/', $seg, $m)) {
                $tag = strtolower($m[2]);
                $close = $m[1] === '/';
                if ($tag === 'a') {
                    $inA += $close ? -1 : 1;
                    if ($inA < 0) $inA = 0;
                } elseif (in_array($tag, array('code', 'pre', 'script', 'style'), true)) {
                    $skip += $close ? -1 : 1;
                    if ($skip < 0) $skip = 0;
                }
            }
            continue;
        }
        if ($inA > 0 || $skip > 0) continue;
        $linkified = preg_replace_callback(
            '~(?<![A-Za-z0-9_@/])(?:https?://[^\s<>"\'，。；：！？、（）【】《》「」『』]+|www\.[^\s<>"\'，。；：！？、（）【】《》「」『』]+|[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,})~iu',
            function ($m) {
                $raw = $m[0];
                $rest = '';
                /* 链接后面直接跟中文时，在第一个中日韩字符处截断，别把正文吞进链接 */
                if (preg_match('/[\x{3000}-\x{303F}\x{4E00}-\x{9FFF}\x{FF00}-\x{FFEF}]/u', $raw, $cm, PREG_OFFSET_CAPTURE)) {
                    $rest = substr($raw, $cm[0][1]);
                    $raw = substr($raw, 0, $cm[0][1]);
                }
                $tail = '';
                while ($raw !== '' && preg_match('/[.,;:!?，。；：！？、）)\]】》"\']$/u', $raw)) {
                    $tail = mb_substr($raw, -1, 1, 'UTF-8') . $tail;
                    $raw = mb_substr($raw, 0, mb_strlen($raw, 'UTF-8') - 1, 'UTF-8');
                }
                if ($raw === '') return $m[0];
                $tail .= $rest;
                $isMail = strpos($raw, '@') !== false
                    && stripos($raw, 'http') !== 0 && stripos($raw, 'www.') !== 0;
                if ($isMail) {
                    return '<a href="mailto:' . $raw . '" rel="nofollow">' . $raw . '</a>' . $tail;
                }
                $href = stripos($raw, 'www.') === 0 ? 'https://' . $raw : $raw;
                $href = htmlspecialchars(htmlspecialchars_decode($href, ENT_QUOTES), ENT_QUOTES, 'UTF-8');
                return '<a href="' . $href . '" target="_blank" rel="external nofollow noopener noreferrer">' . $raw . '</a>' . $tail;
            },
            $seg
        );
        if ($linkified !== null) $parts[$i] = $linkified;
    }
    return implode('', $parts);
}

/** 渲染单条评论（主列表与置顶区共用） */
function cmtRenderItem(array $d) {
    $coid = (int) $d['coid'];
    $levels = (int) $d['levels'];
    $authorId = (int) $d['authorId'];
    $ownerId = (int) $d['ownerId'];
    $isAdmin = $authorId > 0 && $authorId === $ownerId;
    $isFriend = !$isAdmin && isFriend(isset($d['url']) ? $d['url'] : '');
    $level = getCommentLevel(isset($d['mail']) ? $d['mail'] : '');
    $device = getCommentDevice(isset($d['agent']) ? $d['agent'] : '');
    $floor = isset($d['floor']) ? (int) $d['floor'] : 0;
    $likes = isset($d['likes']) ? (int) $d['likes'] : 0;
    $pinned = !empty($d['pinned']);
    $isPrivate = !empty($d['private']);
    $masked = !empty($d['masked']);
    $created = isset($d['created']) ? (int) $d['created'] : 0;
    $name = isset($d['author']) ? (string) $d['author'] : '';
    $url = isset($d['url']) ? trim((string) $d['url']) : '';
    $nameHtml = $url
        ? '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="external nofollow">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</a>'
        : htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $mail = isset($d['mail']) && $d['mail'] ? $d['mail'] : 'guest@example.com';
    $hash = md5(strtolower(trim($mail)));
    $avatarPrimary = 'https://weavatar.com/avatar/' . $hash . '?s=80&d=retro';
    $avatarBackup  = 'https://cravatar.cn/avatar/' . $hash . '?s=80&d=retro';
    $initial = mb_substr(trim(strip_tags($name)), 0, 1, 'UTF-8');
    if ($initial === '') { $initial = '?'; }
    $avatarLocal = 'data:image/svg+xml;charset=UTF-8,' . rawurlencode(
        "<svg xmlns='http://www.w3.org/2000/svg' width='80' height='80'><rect width='80' height='80' fill='#eef1f4'/><text x='40' y='53' font-size='32' font-family='-apple-system,Segoe UI,sans-serif' text-anchor='middle' fill='#8a94a6'>"
        . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') . "</text></svg>"
    );
    $theId = 'comment-' . $coid;
    $classes = 'cmt-item' . ($isAdmin ? ' cmt-is-admin' : '') . ($levels > 0 ? ' cmt-is-child' : '') . ($pinned ? ' cmt-is-pinned' : '') . ($isPrivate ? ' cmt-is-private' : '') . ($masked ? ' cmt-is-masked' : '');
    $childrenHtml = isset($d['childrenHtml']) ? $d['childrenHtml'] : '';
    $contentHtml = isset($d['contentHtml']) ? cmtLinkify($d['contentHtml']) : '';
?>
<li id="li-<?php echo $theId; ?>" class="<?php echo $classes; ?>" data-coid="<?php echo $coid; ?>">
    <div class="cmt-card" id="<?php echo $theId; ?>">
        <div class="cmt-avatar-col">
            <img class="cmt-avatar"
                 src="<?php echo htmlspecialchars($avatarPrimary, ENT_QUOTES, 'UTF-8'); ?>"
                 data-fallback="<?php echo htmlspecialchars($avatarBackup, ENT_QUOTES, 'UTF-8'); ?>"
                 data-default="<?php echo htmlspecialchars($avatarLocal, ENT_QUOTES, 'UTF-8'); ?>"
                 alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"
                 onerror="var d=this.dataset;if(!d.s1){d.s1='1';this.src=d.fallback;}else if(!d.s2){d.s2='1';this.src=d.default;}else{this.onerror=null;}">
        </div>
        <div class="cmt-body">
            <div class="cmt-header">
                <div class="cmt-user">
                    <span class="cmt-name"><?php echo $nameHtml; ?></span>
                    <?php if ($pinned): ?><span class="cmt-tag cmt-tag-pin">置顶</span><?php endif; ?>
                    <?php if ($isPrivate): ?><span class="cmt-tag cmt-tag-private" title="仅站长与作者本人可见"><i class="ri-lock-line"></i> 私密</span><?php endif; ?>
                    <?php if ($isAdmin): ?><span class="cmt-tag cmt-tag-admin">站长</span><?php endif; ?>
                    <?php if ($isFriend): ?><span class="cmt-tag cmt-tag-friend">好友</span><?php endif; ?>
                    <?php if ($level > 0): ?><span class="cmt-tag cmt-tag-lv">Lv<?php echo $level; ?></span><?php endif; ?>
                </div>
                <?php if ($floor > 0): ?>
                <button type="button" class="cmt-floor" data-cid="<?php echo $theId; ?>" title="复制评论链接">#<?php echo $floor; ?></button>
                <?php endif; ?>
                <time class="cmt-time"><?php echo humanTimeDiff($created); ?></time>
            </div>
            <?php if ($masked): ?>
            <div class="cmt-text cmt-text-masked">
                <div class="cmt-mosaic" aria-hidden="true">
                    <span class="cmt-mosaic-bar cmt-mosaic-bar-1"></span>
                    <span class="cmt-mosaic-bar cmt-mosaic-bar-2"></span>
                </div>
                <p class="cmt-mosaic-hint"><i class="ri-lock-line"></i> 私密评论 · 仅作者与站长可见</p>
            </div>
            <?php else: ?>
            <div class="cmt-text"><?php echo $contentHtml; ?></div>
            <?php endif; ?>
            <div class="cmt-actions">
                <button type="button" class="cmt-like-btn" data-coid="<?php echo $coid; ?>" data-likes="<?php echo $likes; ?>" title="点赞"><i class="ri-heart-line"></i><span class="cmt-like-text">点赞</span><span class="cmt-like-count"><?php echo $likes > 0 ? $likes : ''; ?></span></button>
                <?php if (!$masked): ?><a href="javascript:;" class="cmt-reply-btn" data-cid="<?php echo $theId; ?>" data-coid="<?php echo $coid; ?>" title="回复这条评论"><i class="ri-chat-3-line"></i> 回复</a><?php endif; ?>
                <button type="button" class="cmt-del-btn" data-coid="<?php echo $coid; ?>" hidden><i class="ri-delete-bin-line"></i> 删除</button>
                <?php if ($levels === 0): ?><button type="button" class="cmt-pin-btn" data-coid="<?php echo $coid; ?>" data-pinned="<?php echo $pinned ? 1 : 0; ?>" hidden><i class="ri-pushpin-line"></i> <?php echo $pinned ? '取消置顶' : '置顶'; ?></button><?php endif; ?>
                <?php if (!empty($device['os']) || !empty($device['browser'])): ?>
                <span class="cmt-device" title="评论设备">
                    <?php echo cmtDeviceIcon($device); ?>
                    <?php if (!empty($device['os'])): ?><span class="cmt-device-os"><?php echo htmlspecialchars($device['os'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                    <?php if (!empty($device['os']) && !empty($device['browser'])): ?><span class="cmt-device-dot"></span><?php endif; ?>
                    <?php if (!empty($device['browser'])): ?><span class="cmt-device-br"><?php echo htmlspecialchars($device['browser'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($childrenHtml !== ''): echo $childrenHtml; endif; ?>
</li>
<?php
}

function cmtRenderItemHtml(array $d) {
    ob_start();
    cmtRenderItem($d);
    return ob_get_clean();
}

/** 评论子树渲染（直接使用数据库行，子回复包在 ol.cmt-children 中） */
function cmtRenderRowTree(array $row, array $byParent, $level, $options, $maxLevel, array $masked = array()) {
    $childrenHtml = '';
    if ($level < $maxLevel && isset($byParent[(int) $row['coid']])) {
        $childrenInner = '';
        foreach ($byParent[(int) $row['coid']] as $kid) {
            $childrenInner .= cmtRenderRowTree($kid, $byParent, $level + 1, $options, $maxLevel, $masked);
        }
        if ($childrenInner !== '') {
            $childrenHtml = '<ol class="cmt-children">' . $childrenInner . '</ol>';
        }
    }
    $coid = (int) $row['coid'];
    $isMasked = isset($masked[$coid]);
    return cmtRenderItemHtml(array(
        'coid'     => $coid,
        'author'   => isset($row['author']) ? $row['author'] : '',
        'url'      => isset($row['url']) ? $row['url'] : '',
        'mail'     => isset($row['mail']) ? $row['mail'] : '',
        'agent'    => isset($row['agent']) ? $row['agent'] : '',
        'created'  => isset($row['created']) ? (int) $row['created'] : 0,
        'authorId' => isset($row['authorId']) ? (int) $row['authorId'] : 0,
        'ownerId'  => isset($row['ownerId']) ? (int) $row['ownerId'] : 0,
        'levels'   => $level,
        /* 被遮挡时不生成内容，确保私密正文不会出现在页面源码里 */
        'contentHtml' => $isMasked ? '' : cmtFormatContent(isset($row['text']) ? $row['text'] : '', $options),
        'floor'    => ($level === 0 && isset($GLOBALS['cmt_floor_map'][$coid])) ? (int) $GLOBALS['cmt_floor_map'][$coid] : 0,
        'pinned'   => !empty($row['pinned']),
        'private'  => !empty($row['private']),
        'masked'   => $isMasked,
        'likes'    => isset($row['likes']) ? (int) $row['likes'] : 0,
        'childrenHtml' => $childrenHtml,
    ));
}

function threadedComments($comments, $options) {
    $childrenHtml = '';
    if ($comments->children) {
        ob_start();
        $comments->threadedComments($options);
        $childrenHtml = ob_get_clean();
    }
    $coid = (int) $comments->coid;
    $levels = (int) $comments->levels;
    cmtRenderItem(array(
        'coid'     => $coid,
        'author'   => (string) $comments->author,
        'url'      => (string) $comments->url,
        'mail'     => (string) $comments->mail,
        'agent'    => (string) $comments->agent,
        'created'  => (int) $comments->created,
        'authorId' => (int) $comments->authorId,
        'ownerId'  => (int) $comments->ownerId,
        'levels'   => $levels,
        'contentHtml' => (string) $comments->content,
        'floor'    => ($levels === 0 && isset($GLOBALS['cmt_floor_map'][$coid])) ? (int) $GLOBALS['cmt_floor_map'][$coid] : 0,
        'pinned'   => !empty($comments->pinned),
        'private'  => !empty($comments->private),
        'likes'    => (int) $comments->likes,
        'childrenHtml' => $childrenHtml,
    ));
}
?>

<?php
$cmtPinnedRoots = array();
$cmtByParent = array();
$cmtRows = array();
try {
    $cmtSelect = \Typecho\Db::get()->select()->from('table.comments')
        ->where('cid = ?', (int) $this->cid)
        ->where('status = ?', 'approved')
        ->order('coid', 'ASC');
    if (!empty($this->options->commentsShowCommentOnly)) {
        $cmtSelect->where('type = ?', 'comment');
    }
    $cmtRows = \Typecho\Db::get()->fetchAll($cmtSelect);
} catch (Exception $e) {}
/* 私密评论按视角遮挡：站长与作者本人可见全文，其他人只看到马赛克 */
$cmtMasked = cmtPrivateMask(
    $cmtRows,
    !empty($GLOBALS['cmtIsAdmin']),
    $GLOBALS['isLogin'] ? (int) $this->user->uid : 0
);
foreach ($cmtRows as $cmtRow) {
    $cmtParent = (int) $cmtRow['parent'];
    $cmtByParent[$cmtParent][] = $cmtRow;
    if (!empty($cmtRow['pinned']) && $cmtParent === 0) {
        $cmtPinnedRoots[] = $cmtRow;
    }
}
if ($cmtPinnedRoots) {
    usort($cmtPinnedRoots, function ($a, $b) {
        return (int) $b['pinned'] - (int) $a['pinned'];
    });
}
$GLOBALS['cmt_floor_map'] = array();
$cmtFloorNo = 0;
foreach ($cmtRows as $cmtFloorRow) {
    /* 私密评论同样占楼层（他人看到的是马赛克，但仍是一条评论） */
    if ((int) $cmtFloorRow['parent'] === 0) {
        $GLOBALS['cmt_floor_map'][(int) $cmtFloorRow['coid']] = ++$cmtFloorNo;
    }
}
$cmtVisibleCount = count($cmtRows);
?>
<div class="cmt-section" id="comments">
    <svg class="cmt-dev-sprite" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
        <symbol id="cmt-ico-windows" viewBox="0 0 24 24"><path fill="currentColor" d="M2.5 4.9l8.2-1.1v7.6H2.5V4.9zm9.3-1.3L22 2v9.4h-10.2V3.6zM2.5 12.5h8.2v7.6L2.5 19V12.5zm9.3 0H22V22l-10.2-1.4v-8.1z"/></symbol>
        <symbol id="cmt-ico-android" viewBox="0 0 24 24"><path fill="currentColor" d="M8 9h8v6a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2V9zm1-2.9l-.9-1.6a.5.5 0 0 1 .8-.5l1 1.7c.3-.1.7-.2 1.1-.2s.8.1 1.1.2l1-1.7a.5.5 0 0 1 .8.5l-.9 1.6c.9.5 1.6 1.2 1.9 2.1H6.1c.3-.9 1-1.6 1.9-2.1zM9.7 8.4a.6.6 0 1 0 0-1.2.6.6 0 0 0 0 1.2zm4.6 0a.6.6 0 1 0 0-1.2.6.6 0 0 0 0 1.2zM5.8 10a1 1 0 0 1 2 0v5a1 1 0 0 1-2 0v-5zm10.4 0a1 1 0 0 1 2 0v5a1 1 0 0 1-2 0v-5z"/></symbol>
        <symbol id="cmt-ico-apple" viewBox="0 0 24 24"><path fill="currentColor" d="M17.05 12.54c-.03-2.5 2.04-3.7 2.13-3.76-1.16-1.7-2.97-1.93-3.61-1.96-1.54-.16-3 .9-3.78.9-.78 0-1.98-.88-3.26-.86-1.68.02-3.23.98-4.1 2.48-1.75 3.03-.45 7.52 1.26 9.98.83 1.2 1.82 2.55 3.12 2.5 1.25-.05 1.72-.81 3.23-.81 1.5 0 1.93.81 3.25.79 1.34-.02 2.19-1.22 3.01-2.43.95-1.39 1.34-2.73 1.36-2.8-.03-.01-2.6-1-2.63-4.03zM14.75 5.35c.69-.84 1.15-2 1.02-3.16-.99.04-2.18.66-2.89 1.49-.63.73-1.19 1.91-1.04 3.04 1.1.09 2.23-.56 2.91-1.37z"/></symbol>
        <symbol id="cmt-ico-linux" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" d="M3.2 4.5h17.6a1.6 1.6 0 0 1 1.6 1.6v11.8a1.6 1.6 0 0 1-1.6 1.6H3.2a1.6 1.6 0 0 1-1.6-1.6V6.1a1.6 1.6 0 0 1 1.6-1.6z"/><path fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" d="M6.4 9.4l3 2.6-3 2.6M11.8 14.6h4.6"/></symbol>
        <symbol id="cmt-ico-display" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" d="M3.2 4.5h17.6a1.4 1.4 0 0 1 1.4 1.4v9.6a1.4 1.4 0 0 1-1.4 1.4H3.2a1.4 1.4 0 0 1-1.4-1.4V5.9a1.4 1.4 0 0 1 1.4-1.4zM8.6 20.5h6.8M12 16.9v3.6"/></symbol>
    </svg>
    <?php $cmtGb = !empty($GLOBALS['ROBES_GUESTBOOK']); ?>
    <div class="cmt-section-head">
        <h3 class="cmt-title">
            <i class="ri-chat-3-line"></i> <?php echo $cmtVisibleCount > 0 ? $cmtVisibleCount . ($cmtGb ? '条留言' : '条评论') : ($cmtGb ? '还没有留言' : '暂无评论'); ?>
        </h3>
    </div>

    <?php if ($this->allow('comment')): ?>
    <div class="cmt-form-area" id="<?php $this->respondId(); ?>">
        <div class="cmt-reply-bar" id="cmt-reply-bar">
            <div class="cmt-reply-inner">
                <i class="ri-reply-line"></i>
                <span id="cmt-reply-info"></span>
                <button type="button" class="cmt-cancel-btn" id="cmt-cancel-btn"><i class="ri-close-line"></i></button>
            </div>
        </div>
        <div class="cmt-form" id="cmt-form-wrap">
            <form method="post" action="<?php $this->commentUrl(); ?>" id="comment-form">
                <input type="hidden" name="parent" id="comment-parent" value="" />
                <?php if ($this->user->hasLogin()): ?>
                <div class="cmt-logged">已登录为 <strong><?php $this->user->screenName(); ?></strong> <a href="<?php $this->options->logoutUrl(); ?>" class="no-pjax">退出</a></div>
                <?php else: ?>
                <div class="cmt-fields">
                    <input type="text" name="author" placeholder="昵称 *" value="<?php $this->remember('author'); ?>" required maxlength="50" />
                    <input type="email" name="mail" placeholder="邮箱 *" value="<?php $this->remember('mail'); ?>" required maxlength="100" />
                    <input type="url" name="url" placeholder="网址 *" value="<?php $this->remember('url'); ?>" required aria-required="true" maxlength="100" />
                </div>
                <?php endif; ?>
                <div class="cmt-textarea-wrap">
                    <textarea name="text" rows="3" placeholder="说点什么吧…" required><?php $this->remember('text'); ?></textarea>
                </div>
                <div class="cmt-form-bar">
                    <div class="cmt-form-left">
                        <?php include __DIR__ . '/verify/verify-fields.php'; ?>
                        <label class="cmt-private-toggle" title="勾选后仅站长和你本人可见">
                            <input type="checkbox" name="private" value="1" id="comment-private" />
                            <span class="cmt-private-pill"><i class="ri-lock-line"></i><span>私密</span></span>
                        </label>
                        <button type="button" class="cmt-emoji-btn" id="cmt-emoji-btn"><i class="ri-emotion-happy-line"></i></button>
                        <span class="cmt-hint">Ctrl + Enter 发送</span>
                    </div>
                    <button type="submit" class="cmt-submit" id="comment-submit"><?php echo $cmtGb ? '发送留言' : '提交评论'; ?></button>
                </div>
            </form>
        </div>
        <div class="cmt-emoji-panel" id="cmt-emoji-panel" style="display:none;"></div>
    </div>
    <?php else: ?>
    <p class="cmt-closed"><i class="ri-lock-line"></i> 评论已关闭</p>
    <?php endif; ?>

    <?php if ($cmtPinnedRoots): ?>
    <div class="cmt-pinned" id="cmt-pinned">
        <div class="cmt-pinned-head"><i class="ri-pushpin-line"></i> 置顶评论</div>
        <ol class="cmt-list cmt-pinned-list" data-del="<?php echo $this->options->themeUrl; ?>/comment-del.php">
            <?php foreach ($cmtPinnedRoots as $cmtRoot) {
                echo cmtRenderRowTree($cmtRoot, $cmtByParent, 0, $this->options, (int) $this->options->commentsMaxNestingLevels - 1, $cmtMasked);
            } ?>
        </ol>
    </div>
    <?php endif; ?>

    <?php
    /* 主列表由模板渲染并分页：私密评论在渲染前按视角遮挡，
       不依赖核心 Widget\Comments\Archive 的过滤与分页 */
    $cmtListRoots = array();
    $cmtListByParent = array();
    $cmtLevels = array();
    $cmtParentMap = array();
    $cmtMaxDepth = max(1, (int) $this->options->commentsMaxNestingLevels);
    foreach ($cmtRows as $cmtListRow) {
        $cmtListCoid = (int) $cmtListRow['coid'];
        $cmtListParent = (int) $cmtListRow['parent'];
        $cmtListLevel = ($cmtListParent > 0 && isset($cmtLevels[$cmtListParent])) ? $cmtLevels[$cmtListParent] + 1 : 0;
        /* 超过最大嵌套层级时挂到上一层，与核心行为一致 */
        if ($cmtListParent > 0 && $cmtListLevel >= $cmtMaxDepth) {
            $cmtListParent = isset($cmtParentMap[$cmtListParent]) ? $cmtParentMap[$cmtListParent] : 0;
            $cmtListLevel = max(0, $cmtListLevel - 1);
        }
        $cmtLevels[$cmtListCoid] = $cmtListLevel;
        $cmtParentMap[$cmtListCoid] = $cmtListParent;
        $cmtListByParent[$cmtListParent][] = $cmtListRow;
        if ($cmtListParent === 0 && empty($cmtListRow['pinned'])) {
            $cmtListRoots[] = $cmtListRow;
        }
    }
    if ('DESC' === strtoupper((string) $this->options->commentsOrder)) {
        $cmtListRoots = array_reverse($cmtListRoots);
        foreach ($cmtListByParent as $cmtListParentKey => $cmtListKids) {
            $cmtListByParent[$cmtListParentKey] = array_reverse($cmtListKids);
        }
    }
    $cmtPageBreak = !empty($this->options->commentsPageBreak);
    $cmtPageSize = max(1, (int) $this->options->commentsPageSize);
    $cmtTotalRoots = count($cmtListRoots);
    $cmtTotalPages = max(1, (int) ceil($cmtTotalRoots / $cmtPageSize));
    $cmtCurrentPage = $cmtPageBreak ? (int) $this->parameter->commentPage : 1;
    if ($cmtPageBreak && $cmtCurrentPage < 1 && 'last' === $this->options->commentsPageDisplay) {
        $cmtCurrentPage = $cmtTotalPages;
    }
    if ($cmtCurrentPage < 1) $cmtCurrentPage = 1;
    if ($cmtCurrentPage > $cmtTotalPages) $cmtCurrentPage = $cmtTotalPages;
    $cmtPageRoots = $cmtPageBreak
        ? array_slice($cmtListRoots, ($cmtCurrentPage - 1) * $cmtPageSize, $cmtPageSize)
        : $cmtListRoots;
    ?>
    <?php if ($cmtTotalRoots > 0): ?>
    <?php if ($cmtPageRoots): ?>
    <ol class="cmt-list" data-del="<?php echo $this->options->themeUrl; ?>/comment-del.php">
        <?php foreach ($cmtPageRoots as $cmtRoot) {
            echo cmtRenderRowTree($cmtRoot, $cmtListByParent, 0, $this->options, $cmtMaxDepth - 1, $cmtMasked);
        } ?>
    </ol>
    <?php endif; ?>
    <?php
    if ($cmtPageBreak && $cmtTotalPages > 1) {
        $cmtPagePath = '';
        $cmtParentContent = $this->parameter->parentContent;
        if (is_object($cmtParentContent) && !empty($cmtParentContent->path)) {
            $cmtPagePath = $cmtParentContent->path;
        } elseif (is_array($cmtParentContent) && !empty($cmtParentContent['path'])) {
            $cmtPagePath = $cmtParentContent['path'];
        } else {
            $cmtPagePath = trim((string) parse_url((string) $this->permalink, PHP_URL_PATH), '/');
        }
        try {
            $cmtPageTpl = \Typecho\Router::url('comment_page', array(
                'permalink' => $cmtPagePath,
                'commentPage' => '{commentPage}'
            ), $this->options->index);
            $cmtNav = new \Typecho\Widget\Helper\PageNavigator\Box(
                $cmtTotalRoots,
                $cmtCurrentPage,
                $cmtPageSize,
                $cmtPageTpl
            );
            $cmtNav->setPageHolder('commentPage');
            $cmtNav->setAnchor('comments');
            echo '<div class="pagination"><ol class="page-navigator">';
            $cmtNav->render('← 上一页', '下一页 →');
            echo '</ol></div>';
        } catch (Exception $e) {
        }
    }
    ?>
    <?php elseif (empty($cmtPinnedRoots)): ?>
    <div class="cmt-empty">
        <i class="ri-message-3-line"></i>
        <p>还没有评论，来说点什么吧</p>
    </div>
    <?php endif; ?>
</div>