<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
?>
<aside class="sidebar">
    <div class="sidebar-mobile-header">
        <button class="sidebar-close" id="sidebarCloseBtn"><i class="ri-close-line"></i></button>
    </div>

    
    <div class="nav-group">
        <a class="nav-item<?php if($this->is('index') && !$this->is('archive')) echo ' active'; ?>" href="<?php $this->options->siteUrl(); ?>">
            <i class="ri-home-5-line"></i>首页
        </a>
        <?php $this->widget('Widget_Contents_Page_List')->to($pages); ?>
        <?php while ($pages->next()): ?>
            <?php
            $pageTitle = $pages->title;
            $icon = pageNavIcon($pageTitle);
            ?>
            <a class="nav-item<?php if($this->is('page', $pages->slug)) echo ' active'; ?>" href="<?php $pages->permalink(); ?>">
                <i class="<?php echo $icon; ?>"></i><?php echo $pageTitle; ?>
            </a>
        <?php endwhile; ?>
    </div>

    <?php if (themeOption('showSidebarCategories', '1') === '1'): ?>
    <div class="nav-divider"></div>
    <div class="nav-group">
        <div class="nav-group-title">
            <span>分类</span>
            <button class="section-toggle-btn" data-target="categoryList" title="收起/展开">
                <i class="ri-arrow-up-s-line"></i>
            </button>
        </div>
        <div class="section-list" id="categoryList">
        <?php $this->widget('Widget_Metas_Category_List')->to($categorys); ?>
        <?php while ($categorys->next()): ?>
            <?php if ($categorys->count == 0) continue; ?>
            <a class="nav-item<?php if ($this->is('category', $categorys->slug)) echo ' active'; ?>" href="<?php $categorys->permalink(); ?>">
                <i class="ri-folder-line"></i><?php $categorys->name(); ?>
                <span class="nav-count"><?php $categorys->count(); ?></span>
            </a>
        <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (themeOption('showSidebarTags', '1') === '1'): ?>
    <div class="nav-divider"></div>
    <div class="nav-group">
        <div class="nav-group-title">
            <span>标签</span>
            <button class="section-toggle-btn" data-target="tagList" title="收起/展开">
                <i class="ri-arrow-up-s-line"></i>
            </button>
        </div>
        <div class="section-list" id="tagList">
            <div class="sidebar-tags">
            <?php $this->widget('Widget_Metas_Tag_Cloud')->to($tags); ?>
            <?php while ($tags->next()): ?>
                <a class="sidebar-tag" href="<?php $tags->permalink(); ?>" title="<?php $tags->count(); ?> 篇文章">
                    <?php $tags->name(); ?>
                </a>
            <?php endwhile; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="sidebar-footer">
        <span><?php echo themeOption('footerText', '© 2026 ' . $this->options->title); ?></span>
        <?php $icp = themeOption('icpText', ''); if ($icp): ?>
        <div class="dot"></div>
        <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($icp); ?></a>
        <?php endif; ?>
    </div>
</aside>
