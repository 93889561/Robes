<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 留言
 *
 * @package custom
 * @type page
 * @title 留言
 *
 */
$this->need('header.php');

$db = \Typecho\Db::get();
$prefix = $db->getPrefix();
$gbCid = (int) $this->cid;

/* 留言概览：总数 / 留言人数 / 最近一条 */
$gbStatRow = $db->fetchRow(
    $db->select(array('COUNT(coid)' => 'cnt'))
        ->from($prefix . 'comments')
        ->where('cid = ?', $gbCid)
        ->where('status = ?', 'approved')
);
$gbCount = $gbStatRow ? (int) $gbStatRow['cnt'] : 0;

$gbPeopleRow = $db->fetchRow(
    $db->select(array('COUNT(DISTINCT author)' => 'cnt'))
        ->from($prefix . 'comments')
        ->where('cid = ?', $gbCid)
        ->where('status = ?', 'approved')
);
$gbPeople = $gbPeopleRow ? (int) $gbPeopleRow['cnt'] : 0;

$gbLastRow = $db->fetchRow(
    $db->select('created')
        ->from($prefix . 'comments')
        ->where('cid = ?', $gbCid)
        ->where('status = ?', 'approved')
        ->order('created', \Typecho\Db::SORT_DESC)
        ->limit(1)
);
$gbLastText = '还没有留言';
if (!empty($gbLastRow['created'])) {
    $gbDiff = time() - (int) $gbLastRow['created'];
    if ($gbDiff < 60) {
        $gbLastText = '刚刚';
    } elseif ($gbDiff < 3600) {
        $gbLastText = floor($gbDiff / 60) . ' 分钟前';
    } elseif ($gbDiff < 86400) {
        $gbLastText = floor($gbDiff / 3600) . ' 小时前';
    } elseif ($gbDiff < 2592000) {
        $gbLastText = floor($gbDiff / 86400) . ' 天前';
    } else {
        $gbLastText = date('m-d', (int) $gbLastRow['created']);
    }
}
?>

<div class="main-body">
    <?php $this->need('sidebar.php'); ?>
    
    <div class="content-area">
        <div class="fade-top" id="fadeTop"></div>
        <div class="content-inner">
            <div class="content-scroll" id="feed">
                <div class="gb-page">
                    <section class="gb-hero">
                        <i class="ri-chat-3-line gb-deco" aria-hidden="true"></i>
                        <div class="gb-hero-body">
                            <span class="gb-badge"><i class="ri-chat-1-line"></i> Guestbook</span>
                            <h1 class="gb-title">留言板</h1>
                            <p class="gb-sub">不必想好开场白，路过就留下点什么吧。</p>
                        </div>
                        <div class="gb-stats">
                            <div class="gb-stat">
                                <b><?php echo $gbCount; ?></b>
                                <span>条留言</span>
                            </div>
                            <div class="gb-stat">
                                <b><?php echo $gbPeople; ?></b>
                                <span>位留言者</span>
                            </div>
                            <div class="gb-stat">
                                <b class="gb-stat-time"><?php echo htmlspecialchars($gbLastText, ENT_QUOTES, 'UTF-8'); ?></b>
                                <span>最近留言</span>
                            </div>
                        </div>
                    </section>

                    <?php if ($this->content): ?>
                    <div class="gb-intro article-content"><?php $this->content(); ?></div>
                    <?php endif; ?>

                    <div class="gb-tips">
                        <div class="gb-tip"><i class="ri-file-text-line"></i><span><b>文章相关</b>讨论请到文章下留言</span></div>
                        <div class="gb-tip"><i class="ri-link"></i><span><b>友链申请</b>请移步友链页</span></div>
                        <div class="gb-tip"><i class="ri-lock-line"></i><span><b>邮箱仅用于头像</b>，不会公开</span></div>
                    </div>

                    <div id="comments">
                        <?php $GLOBALS['ROBES_GUESTBOOK'] = true; ?>
                        <?php $this->need('comments.php'); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="fade-bottom" id="fadeBottom"></div>
    </div>

<?php $this->need('footer.php'); ?>
