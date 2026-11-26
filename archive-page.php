<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * 归档页面
 *
 * @package custom
 * @type page
 * @title 归档
 */

$db = \Typecho\Db::get();
$rows = $db->fetchAll(
    $db->select('table.contents.cid', 'table.contents.slug', 'table.contents.created', 'table.contents.title', 'table.contents.commentsNum')
    ->from('table.contents')
    ->where('table.contents.type = ?', 'post')
    ->where('table.contents.status = ?', 'publish')
    ->order('table.contents.created', \Typecho\Db::SORT_DESC)
);
$cids = array_column($rows, 'cid');
$viewsMap = $cids ? getBatchViews($cids) : array();

$weekMap = array('日', '一', '二', '三', '四', '五', '六');
$allPosts = array();
$years = array();
$totalViews = 0;

foreach ($rows as $row) {
    $ts = (int) $row['created'];
    $y = date('Y', $ts);
    if (!isset($years[$y])) $years[$y] = 0;
    $years[$y]++;
    $v = isset($viewsMap[$row['cid']]) ? (int) $viewsMap[$row['cid']] : 0;
    $totalViews += $v;

    $permalink = \Typecho\Router::url('post', array('cid' => $row['cid'], 'slug' => $row['slug']), \Helper::options()->index);
    $allPosts[] = array(
        'year'  => $y,
        'month' => (int) date('n', $ts),
        'day'   => date('d', $ts),
        'week'  => '周' . $weekMap[(int) date('w', $ts)],
        'title' => $row['title'],
        'url'   => $permalink,
        'views' => $v,
        'cmts'  => (int) $row['commentsNum'],
    );
}
krsort($years);

$grouped = array();
foreach ($allPosts as $p) {
    $grouped[$p['year']][$p['month']][] = $p;
}

$yearKeys = array_keys($years);
$latestYear = $yearKeys ? $yearKeys[0] : '';
$firstYear = $yearKeys ? min($yearKeys) : '';
$totalPosts = count($allPosts);
$totalYears = count($years);
$viewsText = $totalViews >= 10000
    ? rtrim(rtrim(number_format($totalViews / 10000, 1, '.', ''), '0'), '.') . '万'
    : (string) $totalViews;

$this->need('header.php');
?>

<div class="main-body">
    <?php $this->need('sidebar.php'); ?>

    <div class="content-area">
        <div class="fade-top" id="fadeTop"></div>
        <div class="content-inner">
            <div class="content-scroll" id="feed">
                <article class="article standalone-page arch-page">
                    <section class="gb-hero arch-hero">
                        <i class="ri-archive-line gb-deco arch-deco" aria-hidden="true"></i>
                        <div class="gb-hero-body">
                            <span class="gb-badge"><i class="ri-archive-line"></i> Archive · 归档</span>
                            <h1 class="gb-title">归档</h1>
                            <p class="gb-sub">写过的日子都收在这里，按年份慢慢翻。</p>
                            <?php if ($totalPosts > 0): ?>
                            <div class="links-category-tabs arch-years" id="linksCategoryTabs">
                                <?php foreach ($years as $y => $cnt): ?>
                                    <button class="links-cat-tab arch-year-btn<?php echo $y === $latestYear ? ' active' : ''; ?>" data-category="<?php echo $y; ?>">
                                        <?php echo $y; ?>
                                        <span class="links-cat-count"><?php echo $cnt; ?> 篇</span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="gb-stats">
                            <div class="gb-stat">
                                <b><?php echo $totalPosts; ?></b>
                                <span>篇文章</span>
                            </div>
                            <div class="gb-stat">
                                <b><?php echo $totalYears; ?></b>
                                <span>个年份</span>
                            </div>
                            <div class="gb-stat">
                                <b><?php echo $viewsText; ?></b>
                                <span>累计浏览</span>
                            </div>
                            <div class="gb-stat">
                                <b><?php echo htmlspecialchars($firstYear); ?></b>
                                <span>开始记录</span>
                            </div>
                        </div>

                    </section>

                    <?php if ($this->content): ?>
                    <div class="gb-intro article-content"><?php $this->content(); ?></div>
                    <?php endif; ?>

                    <?php if ($totalPosts > 0): ?>
                    <div class="pg-arch-list arch-list" id="archList">
                        <?php foreach ($grouped as $y => $months): ?>
                            <div class="links-category-group arch-year" data-category="<?php echo $y; ?>"<?php echo $y !== $latestYear ? ' style="display:none"' : ''; ?>>
                                <?php foreach ($months as $m => $posts): ?>
                                    <div class="arch-month">
                                        <b><?php echo $m; ?>月</b>
                                        <span><?php echo count($posts); ?> 篇</span>
                                        <i></i>
                                    </div>
                                    <?php foreach ($posts as $p): ?>
                                        <a class="pg-arch-row" href="<?php echo $p['url']; ?>">
                                            <span class="pg-arch-row-dot"></span>
                                            <span class="pg-arch-row-date"><b><?php echo $p['day']; ?></b><i><?php echo $p['week']; ?></i></span>
                                            <span class="pg-arch-row-title"><?php echo htmlspecialchars($p['title']); ?></span>
                                            <span class="pg-arch-row-meta">
                                                <?php if ($p['views'] > 0): ?><span><i class="ri-eye-line"></i> <?php echo $p['views']; ?></span><?php endif; ?>
                                                <?php if ($p['cmts'] > 0): ?><span><i class="ri-chat-3-line"></i> <?php echo $p['cmts']; ?></span><?php endif; ?>
                                            </span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="arch-empty"><i class="ri-archive-line"></i> 还没有可归档的文章</p>
                    <?php endif; ?>

                </article>
            </div>
        </div>
        <div class="fade-bottom" id="fadeBottom"></div>
    </div>

<?php $this->need('footer.php'); ?>
