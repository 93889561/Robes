<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
?>

</div>

  <div class="bottom-bar">
    <span><?php echo themeOption('footerText', '© 2026 ' . $this->options->title); ?></span>
    <?php $icp = themeOption('icpText', ''); if ($icp): ?>
    <div class="dot"></div>
    <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($icp); ?></a>
    <?php endif; ?>
    <!-- ================================================================
      Robes 是一款免费公开的 Typecho 主题：https://robes.xin
      欢迎自由修改、二次开发与使用，但请保留下方的主题署名不要删除
      保留署名是对免费主题作者最好的支持，感谢理解与尊重
    ================================================================ -->
    <div class="dot"></div>
    <span>Theme <a href="https://robes.xin/" target="_blank" rel="noopener noreferrer">Robes</a></span>
  </div>
</div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<?php
$this->widget('Widget_Contents_Page_List')->to($tabPages);
$tabItems = array();
while ($tabPages->next()) {
    ob_start(); $tabPages->permalink(); $tabLink = ob_get_clean();
    $tabItems[] = array('title' => $tabPages->title, 'slug' => $tabPages->slug, 'link' => $tabLink);
}
$tabPrimary  = array_slice($tabItems, 0, 3);
$tabOverflow = array_slice($tabItems, 3);
?>
<nav class="mobile-tabbar">
    <a class="mtab<?php if($this->is('index')) echo ' active'; ?>" href="<?php $this->options->siteUrl(); ?>">
        <i class="ri-home-5-line"></i><span>首页</span>
    </a>
    <?php foreach ($tabPrimary as $tabItem): ?>
        <a class="mtab<?php if($this->is('page', $tabItem['slug'])) echo ' active'; ?>" href="<?php echo $tabItem['link']; ?>">
            <i class="<?php echo pageNavIcon($tabItem['title']); ?>"></i><span><?php echo $tabItem['title']; ?></span>
        </a>
    <?php endforeach; ?>
    <?php if ($tabOverflow): ?>
    <button type="button" class="mtab mtab-more" id="mtabMoreBtn" aria-expanded="false" aria-controls="mtabMorePanel">
        <i class="ri-more-line"></i><span>更多</span>
    </button>
    <?php endif; ?>
</nav>
<?php if ($tabOverflow): ?>
<div class="mtab-more-panel" id="mtabMorePanel">
    <?php foreach ($tabOverflow as $tabItem): ?>
        <a class="mtab-more-item<?php if($this->is('page', $tabItem['slug'])) echo ' active'; ?>" href="<?php echo $tabItem['link']; ?>">
            <i class="<?php echo pageNavIcon($tabItem['title']); ?>"></i>
            <span class="mtab-more-title"><?php echo $tabItem['title']; ?></span>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (themeOption('enableLightbox', '1') === '1'): ?>
<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="图片查看器" aria-hidden="true">
    <div class="lightbox-toolbar">
        <button class="lightbox-tool" id="lightboxZoomOut" title="缩小 ( - )" aria-label="缩小"><i class="ri-zoom-out-line"></i></button>
        <span class="lightbox-zoom-val" id="lightboxZoomVal">100%</span>
        <button class="lightbox-tool" id="lightboxZoomIn" title="放大 ( + )" aria-label="放大"><i class="ri-zoom-in-line"></i></button>
        <button class="lightbox-tool" id="lightboxRotate" title="旋转 ( R )" aria-label="旋转"><i class="ri-refresh-line"></i></button>
        <button class="lightbox-tool" id="lightboxReset" title="还原 ( 0 )" aria-label="还原"><i class="ri-fullscreen-exit-line"></i></button>
        <a class="lightbox-tool" id="lightboxDownload" title="下载 ( D )" aria-label="下载原图" download><i class="ri-download-line"></i></a>
        <button class="lightbox-tool lightbox-close" id="lightboxClose" title="关闭 ( Esc )" aria-label="关闭"><i class="ri-close-line"></i></button>
    </div>
    <button class="lightbox-nav lightbox-prev" id="lightboxPrev" aria-label="上一张"><i class="ri-arrow-left-s-line"></i></button>
    <div class="lightbox-stage" id="lightboxStage">
        <img id="lightboxPreview" class="lightbox-preview" src="" alt="" aria-hidden="true" draggable="false">
        <div class="lightbox-spinner" id="lightboxSpinner">
            <span class="lb-ring"></span>
            <span class="lb-ring-text">加载原图</span>
        </div>
        <div class="lightbox-error" id="lightboxError" hidden>
            <i class="ri-image-line"></i>
            <span>图片加载失败</span>
            <button type="button" id="lightboxRetry">重新加载</button>
        </div>
        <img id="lightboxImg" src="" alt="" draggable="false">
    </div>
    <button class="lightbox-nav lightbox-next" id="lightboxNext" aria-label="下一张"><i class="ri-arrow-right-s-line"></i></button>
    <div class="lightbox-bottom">
        <div class="lightbox-caption" id="lightboxCaption"></div>
        <div class="lightbox-counter" id="lightboxCounter"></div>
        <div class="lightbox-thumbs" id="lightboxThumbs"></div>
        <div class="lightbox-hint"><i class="ri-lightbulb-line"></i> ← → 切换 · 滚轮/双击缩放 · 拖动平移 · Esc 关闭</div>
    </div>
</div>
<?php endif; ?>

<script>
var ROBES = {
    enableLightbox: <?php echo themeOption("enableLightbox", "1") === "1" ? "true" : "false"; ?>,
    enablePJAX: <?php echo themeOption("enablePJAX", "1") === "1" ? "true" : "false"; ?>,
    enableLinkCheck: <?php echo themeOption("enableLinkCheck", "1") === "1" ? "true" : "false"; ?>,
    darkMode: '<?php echo themeOption("darkMode", "auto"); ?>',
    homeUrl: '<?php $this->options->siteUrl(); ?>',
    themeUrl: '<?php $this->options->themeUrl(); ?>',
    assetVer: '<?php echo ROBES_ASSET_VER; ?>'
};
</script>
<?php if (!empty($GLOBALS['robes_view_cookie'])): ?>
<script>document.cookie = <?php echo json_encode($GLOBALS['robes_view_cookie'] . '=1;path=/;max-age=300'); ?>;</script>
<?php endif; ?>
<?php $v = ROBES_ASSET_VER; ?>
<script src="<?php echo $this->options->themeUrl . '/js/main.v2.js?v=' . $v; ?>" defer></script>
<script src="<?php echo $this->options->themeUrl . '/js/vibe.js?v=' . $v; ?>" defer></script>
<?php if ($this->is('index') || $this->is('archive')): ?>
<script src="<?php echo $this->options->themeUrl . '/js/home.v2.js?v=' . $v; ?>" defer></script>
<?php endif; ?>
<?php if (!$this->is('index')): ?>
<script src="<?php echo $this->options->themeUrl . '/js/article.v2.js?v=' . $v; ?>" defer></script>
<?php endif; ?>
<script src="<?php echo $this->options->themeUrl . '/js/comment-del.js?v=' . $v; ?>" defer></script>
<?php
$customJS = themeOption('customJS', '');
if ($customJS) echo '<script>' . $customJS . '</script>';
?>
<?php $this->footer(); ?>
</body>
</html>
