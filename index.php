<?php
/**
 * 平凡的日子也是限量版。
 * @package Robes
 * @author Robes
 * @version 2.7.2
 * @link https://robes.xin/
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
?>

<div class="main-body">
    <?php $this->need('sidebar.php'); ?>
    
    <div class="content-area">
        <div class="fade-top" id="fadeTop"></div>
        <div class="content-inner">
            <div class="content-scroll" id="feed">

                <?php if ($this->is('search')): ?>
                <div class="search-result-hint">
                    <i class="ri-search-line"></i>
                    <span>搜索 <strong><?php $this->archiveTitle(' - ', '', ''); ?></strong> 的结果，共 <?php echo $this->length; ?> 篇文章</span>
                </div>
                <?php endif; ?>

                
                <?php if ($this->is('index')):
                    list($heroSource, $heroVal) = heroSourceConfig();
                    $heroBg = $heroSource === 'image' ? heroThumb($heroVal) : '';
                    $recPosts = getHeroRandomPosts();
                    /* 图片来源：指定文章内的图片（每次随机打乱） → 自定义图片 → 各文章封面 */
                    if ($heroSource === 'post' && $heroVal !== '') {
                        $heroPool = getHeroImagePool($heroVal);
                    } else {
                        $heroPool = $heroBg !== '' ? array($heroBg) : array();
                    }
                    if (!empty($heroPool) && !empty($recPosts)) {
                        /* 每篇文章按 cid 哈希固定取池中一张，
                           这样首页轮播、首页列表进入文章页看到的都是同一张图 */
                        $heroPool = array_values(array_unique(array_map('heroThumb', $heroPool)));
                        $heroPoolN = count($heroPool);
                        if ($heroPoolN > 0) {
                            foreach ($recPosts as $hpIdx => $hpRow) {
                                $hpPick = abs((int) crc32('robes-hero-' . (int) $hpRow['cid'])) % $heroPoolN;
                                $recPosts[$hpIdx]['cover'] = $heroPool[$hpPick];
                            }
                        }
                    }
                    $recStart = $recPosts ? mt_rand(0, count($recPosts) - 1) : 0;
                    $recMulti = count($recPosts) > 1;
                    $firstCover = heroThumb((!empty($recPosts) && $recPosts[$recStart]['cover'] !== '') ? $recPosts[$recStart]['cover'] : $heroBg);
                    $firstBgStyle = $firstCover !== '' ? 'background-image:url(&quot;' . htmlspecialchars($firstCover) . '&quot;)' : '';
                ?>
                <section class="hero hero-standard<?php echo empty($recPosts) ? ' hero-plain' : ''; ?>" id="hero"
                         data-weather="1">
                    <div class="hero-bgs" id="heroBgs">
                        <div class="hero-bg-layer is-active"<?php if ($firstBgStyle !== ''): ?> style="<?php echo $firstBgStyle; ?>"<?php endif; ?>></div>
                        <div class="hero-bg-layer"></div>
                    </div>
                    <div class="hero-scrim"></div>
                    <div class="hero-content">
                        <div class="hero-head">
                            <div class="hero-weather" id="heroWeather" title="天气数据来自 wttr.in">
                                <span class="hw-row hw-row-sub">
                                    <span class="hw-desc">加载中…</span>
                                    <span class="hw-meta"></span>
                                </span>
                                <span class="hw-row hw-row-main">
                                    <i class="ri-cloudy-line hw-icon"></i>
                                    <span class="hw-city"></span>
                                    <span class="hw-temp">--°</span>
                                </span>
                            </div>
                        </div>
                        <?php if (!empty($recPosts)): ?>
                        <div class="hero-feature" id="heroFeature" data-start="<?php echo $recStart; ?>" data-fallback="<?php echo htmlspecialchars($heroBg); ?>">
                            <ul class="hero-feat-track">
                                <?php foreach ($recPosts as $i => $hp): ?>
                                <li class="hero-feat<?php echo $i === $recStart ? ' is-active' : ''; ?>" data-bg="<?php echo htmlspecialchars(heroThumb($hp['cover'] !== '' ? $hp['cover'] : $heroBg)); ?>">
                                    <a href="<?php echo $hp['url']; ?>">
                                        <span class="hf-title"><?php echo htmlspecialchars($hp['title']); ?></span>
                                        <span class="hf-meta">
                                            <span><i class="ri-calendar-line"></i><?php echo $hp['date']; ?></span>
                                            <span><i class="ri-time-line"></i>约 <?php echo (int) $hp['read']; ?> 分钟</span>
                                            <?php if ($hp['cat'] !== ''): ?>
                                            <span class="hf-cat"><?php echo htmlspecialchars($hp['cat']); ?></span>
                                            <?php endif; ?>
                                        </span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="hero-foot">
                            <?php if ($recMulti): ?>
                            <div class="hero-progress">
                                <div class="hero-dots" id="heroDots"></div>
                                <span class="hero-count" id="heroCount"><b><?php echo sprintf('%02d', $recStart + 1); ?></b><i>/</i><?php echo sprintf('%02d', count($recPosts)); ?></span>
                            </div>
                            <div class="hero-rec-ctrl">
                                <button type="button" class="hero-rec-nav" id="heroPrev" aria-label="上一篇"><i class="ri-arrow-left-s-line"></i></button>
                                <button type="button" class="hero-rec-nav" id="heroNext" aria-label="下一篇"><i class="ri-arrow-right-s-line"></i></button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($this->have()): ?>
                <?php while ($this->next()): ?>
                    <?php $postViews = getPostViews($this->cid); ?>

                    <a href="<?php $this->permalink(); ?>" class="post-card-simple">
                            <div class="pcs-body">
                                <h3 class="pcs-title"><?php $this->title(); ?></h3>
                                <p class="pcs-excerpt"><?php echo getExcerpt($this->content, 120); ?></p>
                                <div class="pcs-meta">
                                    <span class="pcs-meta-item"><i class="ri-time-line"></i> <?php echo $this->date('Y-m-d'); ?></span>
                                    <span class="pcs-meta-item"><i class="ri-eye-line"></i> <?php echo $postViews; ?></span>
                                    <span class="pcs-meta-item"><i class="ri-chat-3-line"></i> <?php $this->commentsNum('0', '1', '%d'); ?></span>
                                </div>
                            </div>
                        </a>

                <?php endwhile; ?>
                <?php else: ?>
                    <div class="post-card" style="text-align:center;color:var(--text-secondary);padding:40px;">
                        <i class="ri-inbox-line" style="font-size:32px;margin-bottom:12px;display:block;color:var(--text-light);"></i>
                        还没有发表文章
                    </div>
                <?php endif; ?>

                
                <?php if ($this->have()): ?>
                    <div class="pagination">
                        <?php $this->pageNav('← 上一页', '下一页 →'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="fade-bottom" id="fadeBottom"></div>
    </div>

<?php $this->need('footer.php'); ?>
