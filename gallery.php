<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 相册
 *
 * @package custom
 * @type page
 * @title 相册
 */
$this->need('header.php');

$db = \Typecho\Db::get();
$prefix = $db->getPrefix();
$rows = $db->fetchAll(
    $db->select('table.contents.cid', 'table.contents.title', 'table.contents.slug', 'table.contents.text', 'table.contents.created', 'table.contents.commentsNum')
    ->from('table.contents')
    ->join($prefix . 'relationships', 'table.contents.cid = ' . $prefix . 'relationships.cid', \Typecho\Db::LEFT_JOIN)
    ->where('table.contents.type = ?', 'post')
    ->where('table.contents.status = ?', 'publish')
    ->where($prefix . 'relationships.mid = ?', intval(themeOption('galleryCategoryId', '6')))
    ->order('table.contents.created', \Typecho\Db::SORT_DESC)
);

$albums = array();
foreach ($rows as $row) {
    $text = $row['text'];
    $imgs = array();
    if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/', $text, $m)) $imgs = array_merge($imgs, $m[1]);
    if (preg_match_all('/!\[[^\]]*\]\(([^)]+)\)/', $text, $m)) $imgs = array_merge($imgs, $m[1]);
    if (preg_match_all('/https?:\/\/[^\s"\'<>\)]+\.(?:jpg|jpeg|png|gif|webp|bmp)/i', $text, $m)) $imgs = array_merge($imgs, $m[0]);
    if (preg_match_all('/\/usr\/uploads\/[^\s"\'<>\)]+/i', $text, $m)) $imgs = array_merge($imgs, $m[0]);
    $imgs = array_values(array_unique($imgs));

    $albums[] = array(
        'title'    => $row['title'],
        'cover'    => !empty($imgs) ? $imgs[0] : '',
        'date'     => date('Y-m-d', $row['created']),
        'count'    => count($imgs),
        'comments' => intval($row['commentsNum']),
        'cid'      => $row['cid'],
        'slug'     => $row['slug'],
    );
}
?>

<div class="main-body">
    <?php $this->need('sidebar.php'); ?>

    <?php
    $albumCount = count($albums);
    $photoCount = 0;
    foreach ($albums as $album) $photoCount += $album['count'];
    $albumLatest = $albumCount ? $albums[0]['date'] : '—';
    ?>

    <div class="content-area">
        <div class="fade-top" id="fadeTop"></div>
        <div class="content-inner">
            <div class="content-scroll" id="feed">
                <article class="article standalone-page album-page">
                    <section class="gb-hero album-hero">
                        <span class="gb-deco"><i class="ri-gallery-line"></i></span>
                        <div class="gb-hero-body">
                            <span class="gb-badge"><i class="ri-image-line"></i>Gallery</span>
                            <h1 class="gb-title">相册</h1>
                            <p class="gb-sub">把生活的碎片，一张张存起来。</p>
                        </div>
                        <div class="gb-stats">
                            <div class="gb-stat"><b><?php echo $albumCount; ?></b><span>个相册</span></div>
                            <div class="gb-stat"><b><?php echo $photoCount; ?></b><span>张照片</span></div>
                            <div class="gb-stat"><b><?php echo $albumLatest; ?></b><span>最近更新</span></div>
                        </div>
                    </section>

                    <?php if ($this->content): ?>
                        <div class="article-content"><?php $this->content(); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($albums)): ?>
                        <div class="album-grid">
                            <?php foreach ($albums as $album): ?>
                                <a class="album-card" href="<?php echo \Typecho\Router::url('post', array('cid' => $album['cid'], 'slug' => $album['slug']), $this->options->index); ?>">
                                    <div class="album-cover">
                                        <?php if ($album['cover']): ?>
                                            <img src="<?php echo htmlspecialchars($album['cover']); ?>" alt="<?php echo htmlspecialchars($album['title']); ?>" loading="lazy">
                                        <?php else: ?>
                                            <div class="album-cover-empty"><i class="ri-gallery-line"></i></div>
                                        <?php endif; ?>
                                        <span class="album-count"><i class="ri-image-line"></i> <?php echo $album['count']; ?></span>
                                    </div>
                                    <div class="album-info">
                                        <div class="album-text">
                                            <span class="album-title"><?php echo htmlspecialchars($album['title']); ?></span>
                                            <span class="album-meta"><?php echo $album['date']; ?> · <?php echo $album['comments']; ?> 评论</span>
                                        </div>
                                        <span class="album-arrow"><i class="ri-arrow-right-line"></i></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="links-empty">
                            <i class="ri-gallery-line" style="font-size:32px;margin-bottom:12px;display:block;color:var(--text-light);"></i>
                            暂无相册，请在分类ID=<?php echo intval(themeOption('galleryCategoryId', '6')); ?>的文章中插入图片
                        </div>
                    <?php endif; ?>
                </article>
            </div>
        </div>
        <div class="fade-bottom" id="fadeBottom"></div>
    </div>

<?php $this->need('footer.php'); ?>
