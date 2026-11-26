<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 关于页面
 *
 * @package custom
 * @type page
 * @title 关于
 *
 */
$this->need('header.php');

$db = \Typecho\Db::get();
$postCount = intval($db->fetchObject($db->select(array('COUNT(cid)' => 'total'))->from('table.contents')->where('type = ?', 'post'))->total);
$commentCount = intval($db->fetchObject($db->select(array('COUNT(coid)' => 'total'))->from('table.comments'))->total);
$tagCount = intval($db->fetchObject($db->select(array('COUNT(mid)' => 'total'))->from('table.metas')->where('type = ?', 'tag'))->total);
$startDate = themeOption('siteStartDate', '');
$startTs = ($startDate && strtotime($startDate)) ? strtotime($startDate) : 0; // 留空或无效时不显示建站信息
$days = $startTs ? max(0, floor((time() - $startTs) / 86400)) : 0;
$lastPost = $db->fetchRow($db->select('created')->from('table.contents')->where('type = ?', 'post')->where('status = ?', 'publish')->order('created', \Typecho\Db::SORT_DESC)->limit(1));
$lastUpdate = $lastPost ? date('Y.m.d', $lastPost['created']) : '暂无';

/* 最近更新的三篇文章 */
$recent = array();
$this->widget('Widget_Contents_Post_Recent', 'pageSize=3')->to($recentPosts);
while ($recentPosts->next()) {
    ob_start(); $recentPosts->permalink(); $recentLink = ob_get_clean();
    $recent[] = array(
        'title' => $recentPosts->title,
        'date'  => date('m.d', $recentPosts->created),
        'full'  => date('Y.m.d', $recentPosts->created),
        'link'  => $recentLink,
    );
}

/* 留言板链接：直接查库，避免占用 footer 的页面列表组件 */
$guestLink = '';
$guestRow = $db->fetchRow($db->select('cid')->from('table.contents')->where('type = ?', 'page')->where('status = ?', 'publish')->where('title LIKE ?', '%留言%')->limit(1));
if ($guestRow) {
    $guestLink = rtrim($this->options->siteUrl, '/') . '/' . $guestRow['cid'] . '.html';
}

$socials = array(
    'socialGithub'   => array('ri-github-line', 'GitHub'),
    'socialTwitter'  => array('ri-twitter-x-line', 'Twitter'),
    'socialWeibo'    => array('ri-weibo-line', '微博'),
    'socialWechat'   => array('ri-wechat-line', '微信'),
    'socialBilibili' => array('ri-bilibili-line', 'Bilibili'),
    'socialTelegram' => array('ri-telegram-line', 'Telegram'),
    'socialEmail'    => array('ri-mail-line', '邮箱'),
    'socialRss'      => array('ri-rss-line', 'RSS'),
);
$story = trim((string) $this->content);

$aboutName = trim($this->options->title);
$aboutLogo = trim(themeOption('logoImage', ''));
?>

<div class="main-body">
    <?php $this->need('sidebar.php'); ?>

    <div class="content-area">
        <div class="fade-top" id="fadeTop"></div>
        <div class="content-inner">
            <div class="content-scroll" id="feed">
                <article class="article standalone-page">
                    <div class="abt">

                        <header class="abt-head">
                            <i class="ri-user-line abt-deco" aria-hidden="true"></i>
                            <div class="abt-profile">
                                <div class="abt-avatar">
                                    <?php if ($aboutLogo !== ''): ?>
                                    <img src="<?php echo htmlspecialchars($aboutLogo); ?>" alt="<?php echo htmlspecialchars($aboutName); ?>">
                                    <?php else: ?>
                                    <?php echo htmlspecialchars(mb_substr($aboutName, 0, 1, 'UTF-8')); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="abt-intro">
                                    <span class="abt-eyebrow"><i class="ri-user-line"></i>About · 关于</span>
                                    <h1 class="abt-title"><?php echo $this->options->description ?: '关于这个站点'; ?></h1>
                                    <p class="abt-meta">
                                        <span><?php echo htmlspecialchars($aboutName); ?></span>
                                        <?php if ($startTs): ?>
                                        <i></i>
                                        <span>建于 <?php echo date('Y.m.d', $startTs); ?></span>
                                        <i></i>
                                        <span>运行 <?php echo $days; ?> 天</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>

                            <div class="abt-links">
                                <?php
                                $hasRss = trim(themeOption('socialRss', '')) !== '';
                                foreach ($socials as $key => $info) {
                                    $val = trim(themeOption($key, ''));
                                    if (empty($val)) continue;
                                    if ($key === 'socialEmail') $val = 'mailto:' . $val;
                                    elseif ($key === 'socialRss' && strpos($val, 'http') !== 0) $val = rtrim($this->options->siteUrl, '/') . '/feed/';
                                    elseif (strpos($val, 'http') !== 0 && strpos($val, 'mailto:') !== 0) {
                                        $prefixes = array(
                                            'socialGithub'   => 'https://github.com/',
                                            'socialTwitter'  => 'https://x.com/',
                                            'socialWeibo'    => 'https://weibo.com/',
                                            'socialBilibili' => 'https://space.bilibili.com/',
                                            'socialTelegram' => 'https://t.me/',
                                        );
                                        $val = (isset($prefixes[$key]) ? $prefixes[$key] : '') . $val;
                                    }
                                    echo '<a href="' . htmlspecialchars($val) . '" target="_blank" rel="noopener noreferrer" title="' . $info[1] . '"><i class="' . $info[0] . '"></i>' . $info[1] . '</a>' . "\n";
                                }
                                if (!$hasRss) {
                                    echo '<a href="' . rtrim($this->options->siteUrl, '/') . '/feed/' . '" target="_blank" rel="noopener noreferrer" title="RSS"><i class="ri-rss-line"></i>RSS</a>' . "\n";
                                }
                                ?>
                            </div>

                            <div class="abt-stats">
                                <div class="abt-stat"><b><?php echo $postCount; ?></b><span>篇文章</span></div>
                                <div class="abt-stat"><b><?php echo $commentCount; ?></b><span>条评论</span></div>
                                <div class="abt-stat"><b><?php echo $tagCount; ?></b><span>个标签</span></div>
                                <div class="abt-stat"><b><?php echo $lastUpdate; ?></b><span>最近更新</span></div>
                            </div>
                        </header>

                        <?php if ($story): ?>
                        <section class="abt-sec">
                            <div class="abt-sec-head">
                                <h2>建站历程</h2>
                                <?php if ($startTs): ?><span><?php echo date('Y', $startTs); ?> — <?php echo date('Y'); ?></span><?php endif; ?>
                            </div>
                            <div class="abt-story"><?php $this->content(); ?></div>
                        </section>
                        <?php endif; ?>

                        <?php if (!empty($recent)): ?>
                        <section class="abt-sec">
                            <div class="abt-sec-head">
                                <h2>最近更新</h2>
                                <a class="abt-more" href="<?php $this->options->siteUrl(); ?>">全部文章<i class="ri-arrow-right-line"></i></a>
                            </div>
                            <ul class="abt-posts">
                                <?php foreach ($recent as $item): ?>
                                <li>
                                    <a href="<?php echo $item['link']; ?>">
                                        <time><?php echo $item['date']; ?></time>
                                        <span class="abt-post-title"><?php echo htmlspecialchars($item['title']); ?></span>
                                        <i class="ri-arrow-right-line"></i>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                        <?php endif; ?>

                        <?php if ($guestLink): ?>
                        <div class="abt-cta">
                            <span>想聊点什么，或者只是路过打个招呼。</span>
                            <a href="<?php echo $guestLink; ?>">去留言板<i class="ri-arrow-right-line"></i></a>
                        </div>
                        <?php endif; ?>

                    </div>
                </article>
            </div>
        </div>
        <div class="fade-bottom" id="fadeBottom"></div>
    </div>

<?php $this->need('footer.php'); ?>
