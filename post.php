<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
incrementPostViews($this->cid);
$wordCount = getWordCount($this->content);
$readTime = max(1, ceil($wordCount / 500));
?>

<div class="main-body">
    <?php $this->need('sidebar.php'); ?>
    
    <div class="content-area">
        <div class="fade-top" id="fadeTop"></div>
        <div class="content-inner post-inner">
            <div class="content-scroll" id="feed">
                <article class="article">
                    <?php
                    $useTOC = themeOption('enableTOC', '1') === '1';
                    /* 顶部图片与首页同源：指定文章的图片池（每次随机取一张） → 自定义图片 → 本文首图 */
                    list($heroSource, $heroVal) = heroSourceConfig();
                    $articleCover = '';
                    $heroPool = array();
                    if ($heroSource === 'post' && $heroVal !== '') {
                        /* 缓存池里可能存着旧版本号，统一走 heroThumb，保证和首页显示的地址完全一致 */
                        $heroPool = array_values(array_unique(array_map('heroThumb', getHeroImagePool($heroVal))));
                    }
                    /* 从首页首屏点进来时带上了当时那张图，沿用同一张，视觉上就是一镜到底 */
                    $heroReq = (isset($_GET['hero']) && is_string($_GET['hero'])) ? trim($_GET['hero']) : '';
                    if ($heroReq !== '') {
                        $heroReq = heroThumb($heroReq);
                        $heroAllowed = $heroPool;
                        if ($heroVal !== '') $heroAllowed[] = heroThumb($heroVal);
                        $heroReqPath = (string) parse_url($heroReq, PHP_URL_PATH);
                        $heroReqHost = strtolower((string) parse_url($heroReq, PHP_URL_HOST));
                        $heroHost = strtolower((string) parse_url('http://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''), PHP_URL_HOST));
                        $heroReqLocal = ($heroReqHost === '' || $heroReqHost === $heroHost)
                            && (strpos($heroReqPath, '/img.php') !== false || strpos($heroReqPath, '/usr/uploads/') !== false);
                        if ($heroReqLocal || in_array($heroReq, array_values(array_unique($heroAllowed)), true)) {
                            $articleCover = $heroReq;
                        }
                    }
                    if ($articleCover === '' && !empty($heroPool)) {
                        /* 与首页同一套 cid 哈希：同一篇文章无论从轮播还是列表进来，都是同一张图 */
                        $heroPick = abs((int) crc32('robes-hero-' . (int) $this->cid)) % count($heroPool);
                        $articleCover = $heroPool[$heroPick];
                    }
                    if ($articleCover === '' && $heroSource === 'image') {
                        $articleCover = heroThumb($heroVal);
                    }
                    if ($articleCover === '') {
                        $ownImages = getPostImages((string) $this->text, 1);
                        if (!empty($ownImages)) {
                            $articleCover = heroThumb($ownImages[0]);
                        }
                    }
                    ?>
                    <div class="article-hero">
                        <?php if ($articleCover !== ''): ?>
                        <img class="article-hero-bg" src="<?php echo htmlspecialchars($articleCover); ?>" alt="" loading="eager" decoding="async">
                        <?php endif; ?>
                        <div class="article-hero-scrim"></div>
                        <div class="article-hero-content">
                        <div class="article-hero-kicker"><?php $this->category(' · '); ?></div>
                        <?php if ($useTOC): ?>
                        <div class="article-title-row">
                            <h1 class="article-title"><?php $this->title(); ?></h1>
                            <button class="toc-inline-btn" id="tocInlineBtn" title="展开目录" style="display:none;">
                                <i class="ri-list-unordered"></i>
                            </button>
                        </div>
                        <?php else: ?>
                        <h1 class="article-title"><?php $this->title(); ?></h1>
                        <?php endif; ?>
                        <div class="article-meta">
                        <span class="article-meta-item">
                            <i class="ri-calendar-line"></i>
                            <span class="meta-date-long"><?php $this->date('Y年m月d日'); ?></span>
                            <span class="meta-date-short"><?php $this->date('Y.m.d'); ?></span>
                        </span>
                        <span class="article-meta-item">
                            <i class="ri-eye-line"></i>
                            <?php echo getPostViews($this->cid); ?> 阅读
                        </span>
                        <span class="article-meta-item">
                            <i class="ri-chat-3-line"></i>
                            <?php $this->commentsNum('0', '1', '%d'); ?> 评论
                        </span>
                        <span class="article-meta-item">
                            <i class="ri-time-line"></i>
                            约 <?php echo $readTime; ?> 分钟
                        </span>
                        <span class="article-meta-item">
                            <i class="ri-file-text-line"></i>
                            <?php echo $wordCount; ?> 字
                        </span>
                        </div>
                        </div>
                    </div>
                    <?php if ($useTOC): ?>
                    <div class="toc-wrap" id="articleTOC"></div>
                    <?php endif; ?>

                    
                    
                    <div class="article-content" id="articleContent">
                        <?php ob_start(); $this->content(); $c = ob_get_clean(); echo themePhotoFilter::processShortcodes($c); ?>
                    </div>

                    
                                        
                    
                    <div class="like-wrap">
                        <button class="like-btn" id="likeBtn" data-cid="<?php $this->cid(); ?>" data-liked="<?php echo isset($_COOKIE['like_'.$this->cid]) ? '1' : '0'; ?>">
                            <i class="ri-heart-<?php echo isset($_COOKIE['like_'.$this->cid]) ? 'fill' : 'line'; ?>"></i>
                            <span class="like-text"><?php echo isset($_COOKIE['like_'.$this->cid]) ? '已喜欢' : '喜欢这篇文章'; ?></span>
                            <span class="like-count"><?php
                                try {
                                    $row = $this->db->fetchRow($this->db->select('likes')->from('table.contents')->where('cid = ?', $this->cid));
                                    echo $row ? intval($row['likes']) : 0;
                                } catch (Exception $e) { echo 0; }
                            ?></span>
                        </button>
                    </div>

                    <div class="article-tags" id="articleTags">
                        <?php $this->tags('', true, ''); ?>
                    </div>

                    <?php if (themeOption('enablePostNav', '1') === '1'): ?>
                    <div class="post-nav">
                        <?php $prev = getAdjacentPost($this->cid, 'prev'); if ($prev): ?>
                        <a class="post-nav-item post-nav-prev" href="<?php echo \Typecho\Router::url('post', array('cid' => $prev['cid'], 'slug' => $prev['slug']), $this->options->index); ?>">
                            <span class="post-nav-label"><i class="ri-arrow-left-s-line"></i> 上一篇</span>
                            <span class="post-nav-title"><?php echo htmlspecialchars($prev['title']); ?></span>
                        </a>
                        <?php else: ?>
                        <div class="post-nav-item post-nav-prev post-nav-empty">
                            <span class="post-nav-label"><i class="ri-arrow-left-s-line"></i> 上一篇</span>
                            <span class="post-nav-title">已经是第一篇了</span>
                        </div>
                        <?php endif; ?>
                        <?php $next = getAdjacentPost($this->cid, 'next'); if ($next): ?>
                        <a class="post-nav-item post-nav-next" href="<?php echo \Typecho\Router::url('post', array('cid' => $next['cid'], 'slug' => $next['slug']), $this->options->index); ?>">
                            <span class="post-nav-label">下一篇 <i class="ri-arrow-right-s-line"></i></span>
                            <span class="post-nav-title"><?php echo htmlspecialchars($next['title']); ?></span>
                        </a>
                        <?php else: ?>
                        <div class="post-nav-item post-nav-next post-nav-empty">
                            <span class="post-nav-label">下一篇 <i class="ri-arrow-right-s-line"></i></span>
                            <span class="post-nav-title">已经是最后一篇了</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>


                </article>

                
                <div id="comments">
                    <?php $this->need('comments.php'); ?>
                </div>
            </div>
        </div>
        <div class="fade-bottom" id="fadeBottom"></div>
    </div>

<?php $this->need('footer.php'); ?>
