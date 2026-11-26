<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 友情链接
 *
 * @package custom
 * @type page
 * @title 友链
 *
 */
$this->need('header.php');

$allLinks = array();
$db = \Typecho\Db::get();
$prefix = $db->getPrefix();
$tableName = $prefix . 'links';

try {
    $tableExists = false;
    try {
        if (strtolower($db->getAdapterName()) === 'pdomysql' || strpos($db->getAdapterName(), 'Mysql') !== false) {
            $result = $db->query('SHOW TABLES LIKE \'' . $tableName . '\'');
            $tableExists = (bool)$db->fetchRow($result);
        } else {
            $result = $db->query('SELECT name FROM sqlite_master WHERE type=\'table\' AND name=\'' . $tableName . '\'');
            $tableExists = (bool)$db->fetchRow($result);
        }
    } catch (Exception $e2) {
        $tableExists = true;
    }

    if ($tableExists) {
        try {
            $allLinks = $db->fetchAll(
                $db->select()->from($tableName)
                    ->where('state = ?', 1)
                    ->order('`order`', \Typecho\Db::SORT_ASC)
            );
        } catch (Exception $e3) {
            $allLinks = $db->fetchAll(
                $db->select()->from($tableName)
                    ->order('`order`', \Typecho\Db::SORT_ASC)
            );
        }
    } else {
        $allLinks = '__TABLE_MISSING__';
    }
} catch (Exception $e) {
    $allLinks = '__ERROR__';
    $linksError = $e->getMessage();
}

$categories = array();
$linksByCategory = array();
foreach ($allLinks as $link) {
    if (empty($link['url'])) continue;
    $cat = !empty($link['sort']) ? $link['sort'] : '未分类';
    if (!isset($categories[$cat])) {
        $categories[$cat] = 0;
        $linksByCategory[$cat] = array();
    }
    $categories[$cat]++;
    $linksByCategory[$cat][] = $link;
}

/* 每个友链域名在本站的最近一次评论时间 */
$linkLastComment = array();
try {
    $cmtRows = $db->fetchAll(
        $db->select()->from($prefix . 'comments')
            ->where('status = ?', 'approved')
            ->where('url <> ?', '')
    );
    foreach ($cmtRows as $row) {
        $host = strtolower((string) parse_url((string) $row['url'], PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);
        if ($host === '') continue;
        $ts = (int) $row['created'];
        if (!isset($linkLastComment[$host]) || $ts > $linkLastComment[$host]) $linkLastComment[$host] = $ts;
    }
} catch (Exception $e) {
    $linkLastComment = array();
}
$GLOBALS['linkLastComment'] = $linkLastComment;

function _link_last_comment_text($ts) {
    if (!$ts) return '';
    $days = (int) floor((time() - $ts) / 86400);
    if ($days <= 0) return '今天';
    if ($days === 1) return '昨天';
    return $days . ' 天前';
}

function _renderLinkCard($link) {
    $name  = $link['name'] ?? '好友';
    $url   = $link['url'] ?? '#';
    $img   = $link['image'] ?? '';
    $desc  = $link['description'] ?? '';
    $email = $link['email'] ?? '';
    if (empty($img) && !empty($email))
        $img = 'https://cravatar.cn/avatar/' . md5(strtolower(trim($email))) . '?s=80&d=mp';
    ?>
    <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener noreferrer" class="link-card" data-url="<?php echo htmlspecialchars($url); ?>">
        <div class="link-avatar">
            <?php if ($img): ?>
                <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($name); ?>" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'link-avatar-text\'>' + '<?php echo mb_substr($name, 0, 1); ?>' + '</div>'">
            <?php else: ?>
                <div class="link-avatar-text"><?php echo mb_substr($name, 0, 1); ?></div>
            <?php endif; ?>
        </div>
        <div class="link-info">
            <div class="link-name"><?php echo htmlspecialchars($name); ?></div>
            <?php if ($desc): ?><div class="link-desc"><?php echo mb_substr($desc, 0, 50); ?></div><?php endif; ?>
            <div class="link-meta">
                <?php if (!empty($link['sort'])): ?>
                    <span class="link-category-tag"><i class="ri-price-tag-3-line"></i> <?php echo htmlspecialchars($link['sort']); ?></span>
                <?php endif; ?>
                <?php
                $linkHost = preg_replace('/^www\./', '', strtolower((string) parse_url($url, PHP_URL_HOST)));
                $lastTs = ($linkHost !== '' && isset($GLOBALS['linkLastComment'][$linkHost])) ? $GLOBALS['linkLastComment'][$linkHost] : 0;
                ?>
                <?php if ($lastTs): ?>
                    <span class="link-last-comment" title="该友链在本站的最近一次评论：<?php echo date('Y-m-d H:i', $lastTs); ?>"><i class="ri-chat-1-line"></i> <?php echo _link_last_comment_text($lastTs); ?></span>
                <?php else: ?>
                    <span class="link-last-comment is-none" title="该友链还没有在本站留下评论"><i class="ri-chat-1-line"></i> 暂无评论</span>
                <?php endif; ?>
                <span class="link-status" title="检测中"></span>
            </div>
        </div>
    </a>
    <?php
}
?>

<div class="main-body">
    <?php $this->need('sidebar.php'); ?>

    <div class="content-area">
        <div class="fade-top" id="fadeTop"></div>
        <div class="content-inner">
            <div class="content-scroll" id="feed">
                <article class="article standalone-page">
                    <?php if ($this->content): ?>
                        <div class="article-content"><?php $this->content(); ?></div>
                    <?php endif; ?>

                    <?php if ($allLinks === '__TABLE_MISSING__'): ?>
                        <div class="links-empty">
                            <i class="ri-plug-line" style="font-size:32px;margin-bottom:12px;display:block;color:var(--text-light);"></i>
                            友情链接数据表不存在，请先启用寒泥友情链接插件
                        </div>
                    <?php elseif ($allLinks === '__ERROR__'): ?>
                        <div class="links-empty">
                            <i class="ri-error-warning-line" style="font-size:32px;margin-bottom:12px;display:block;color:#ef4444;"></i>
                            查询友情链接出错：<?php echo htmlspecialchars($linksError ?? '未知错误'); ?>
                        </div>
                    <?php elseif (empty($allLinks)): ?>
                        <div class="links-empty">
                            <i class="ri-link-unlink" style="font-size:32px;margin-bottom:12px;display:block;color:var(--text-light);"></i>
                            暂无友情链接，请在后台添加
                        </div>
                    <?php else: ?>
                        <?php $showCat = themeOption('showLinksCategory', '1') == '1' && !empty($categories); ?>
                        <?php
                        $catNames = $showCat ? array_keys($categories) : array();
                        $firstCat = !empty($catNames) ? $catNames[0] : '';
                        ?>

                        <?php if ($showCat): ?>
                        <div class="links-category-tabs" id="linksCategoryTabs">
                            <?php foreach ($categories as $catName => $catCount): ?>
                                <button class="links-cat-tab<?php echo $catName === $firstCat ? ' active' : ''; ?>" data-category="<?php echo htmlspecialchars($catName); ?>">
                                    <?php echo htmlspecialchars($catName); ?>
                                    <span class="links-cat-count"><?php echo $catCount; ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <div class="links-category-group" data-category="all" style="display:none">
                            <div class="links-grid">
                                <?php foreach ($allLinks as $link): if (empty($link['url'])) continue; _renderLinkCard($link); endforeach; ?>
                            </div>
                        </div>

                        <?php foreach ($linksByCategory as $catName => $catLinks): ?>
                        <div class="links-category-group" data-category="<?php echo htmlspecialchars($catName); ?>"<?php echo $catName === $firstCat ? '' : ' style="display:none"'; ?>>
                            <div class="links-grid">
                                <?php foreach ($catLinks as $link): _renderLinkCard($link); endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <?php else: ?>
                        <div class="links-category-group">
                            <div class="links-grid">
                                <?php foreach ($allLinks as $link): if (empty($link['url'])) continue; _renderLinkCard($link); endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php $applySiteName = $this->options->title; ?>
                    <section class="link-apply" id="link-apply">
                        <i class="ri-link link-apply-deco" aria-hidden="true"></i>
                        <div class="link-apply-head">
                            <div class="link-apply-head-text">
                                <h3 class="link-apply-title">申请友链<span class="link-apply-badge"><i class="ri-link"></i> Friend Link</span></h3>
                                <p class="link-apply-sub">欢迎交换友情链接，点击「立即申请」填写资料即可</p>
                            </div>
                            <a class="link-apply-btn" href="javascript:;" data-cmt-apply-open>立即申请<i class="ri-arrow-right-s-line"></i></a>
                        </div>

                        <div class="link-apply-body">
                            <div class="link-apply-panel">
                                <div class="link-apply-col-title">本站信息</div>
                                <ul class="link-apply-list">
                                    <li>
                                        <span class="lk">名称</span>
                                        <span class="lv"><?php echo htmlspecialchars($applySiteName); ?></span>
                                        <button type="button" class="lk-copy" data-copy="<?php echo htmlspecialchars($applySiteName, ENT_QUOTES, 'UTF-8'); ?>" aria-label="复制名称"><i class="ri-file-copy-line"></i></button>
                                    </li>
                                    <li>
                                        <span class="lk">地址</span>
                                        <a class="lv" href="<?php $this->options->siteUrl(); ?>" target="_blank" rel="noopener"><?php $this->options->siteUrl(); ?></a>
                                        <button type="button" class="lk-copy" data-copy="<?php echo htmlspecialchars($this->options->siteUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="复制地址"><i class="ri-file-copy-line"></i></button>
                                    </li>
                                    <li>
                                        <span class="lk">头像地址</span>
                                        <a class="lv" href="https://robes.xin/tx.jpg" target="_blank" rel="noopener">https://robes.xin/tx.jpg</a>
                                        <button type="button" class="lk-copy" data-copy="https://robes.xin/tx.jpg" aria-label="复制头像地址"><i class="ri-file-copy-line"></i></button>
                                    </li>
                                    <li>
                                        <span class="lk">简介</span>
                                        <span class="lv"><?php echo htmlspecialchars($this->options->description); ?></span>
                                        <button type="button" class="lk-copy" data-copy="<?php echo htmlspecialchars($this->options->description, ENT_QUOTES, 'UTF-8'); ?>" aria-label="复制简介"><i class="ri-file-copy-line"></i></button>
                                    </li>
                                </ul>
                            </div>
                            <div class="link-apply-panel">
                                <div class="link-apply-col-title">申请要求</div>
                                <ul class="link-apply-rules">
                                    <li><span class="rule-dot"><i class="ri-check-line"></i></span><span>内容原创或有一定质量，无违法违规内容</span></li>
                                    <li><span class="rule-dot"><i class="ri-check-line"></i></span><span>已在贵站添加本站友链</span></li>
                                    <li><span class="rule-dot"><i class="ri-check-line"></i></span><span>保持更新，拒绝纯采集 / 长期停更站点</span></li>
                                </ul>
                            </div>
                        </div>
                    </section>

                </article>

                <?php /* 友链申请弹窗：独立卡片，直接 POST 到主题 links-apply.php（发邮件通知，不走评论区） */ ?>
                <div class="cmt-modal lap-modal" id="cmt-apply-modal" aria-hidden="true">
                    <div class="cmt-modal-mask" data-cmt-apply-close></div>
                    <div class="cmt-modal-card lap-card" role="dialog" aria-modal="true" aria-labelledby="cmt-apply-modal-title">
                        <button type="button" class="cmt-modal-close lap-close" data-cmt-apply-close aria-label="关闭"><i class="ri-close-line"></i></button>

                        <header class="lap-hero">
                            <i class="ri-link lap-deco" aria-hidden="true"></i>
                            <div class="lap-hero-body">
                                <h3 class="lap-hero-title" id="cmt-apply-modal-title">申请友情链接<span class="lap-badge"><i class="ri-link"></i> Friend Link</span></h3>
                                <p class="lap-hero-sub">填写资料提交，新申请会邮件直达站长</p>
                            </div>
                        </header>

                        <div class="lap-body">
                            <form method="post" action="<?php echo $this->options->themeUrl; ?>/links-apply.php" id="comment-form" class="apply-form lap-form" data-apply-mode="1">
                                <div class="lap-grid">
                                    <label class="lap-field">
                                        <span class="lap-label"><i class="ri-home-5-line"></i>站点名称<b>*</b></span>
                                        <input type="text" name="name" maxlength="40" placeholder="我的小站" required autocomplete="organization" />
                                    </label>
                                    <label class="lap-field">
                                        <span class="lap-label"><i class="ri-mail-line"></i>联系邮箱<b>*</b></span>
                                        <input type="email" name="email" maxlength="100" placeholder="you@example.com" required autocomplete="email" />
                                    </label>
                                    <label class="lap-field">
                                        <span class="lap-label"><i class="ri-link"></i>站点地址<b>*</b></span>
                                        <input type="url" name="url" maxlength="200" placeholder="https://robes.xin" required />
                                    </label>
                                    <label class="lap-field">
                                        <span class="lap-label"><i class="ri-image-line"></i>头像地址<span class="lap-opt">选填</span></span>
                                        <input type="url" name="avatar" maxlength="200" placeholder="留空自动使用站点图标" />
                                    </label>
                                    <label class="lap-field lap-span">
                                        <span class="lap-label"><i class="ri-file-text-line"></i>站点简介<b>*</b></span>
                                        <textarea name="desc" rows="3" maxlength="200" placeholder="一句话介绍你的站点，并附上你的友链地址…" required></textarea>
                                        <span class="lap-hint">200 字以内，通过后会展示在上方友链列表</span>
                                    </label>
                                </div>
                                <div class="lap-hp" aria-hidden="true"><input type="text" name="hp" tabindex="-1" autocomplete="off" /></div>
                                <div class="lap-bar">
                                    <span class="lap-bar-note"><i class="ri-shield-check-line"></i>仅用于邮件通知站长，不公开、不写评论</span>
                                    <button type="submit" class="lap-submit" id="comment-submit">提交申请<i class="ri-arrow-up-s-line"></i></button>
                                </div>
                            </form>

                            <div class="lap-done" id="applyDone" hidden>
                                <span class="lap-done-ico"><i class="ri-check-line"></i></span>
                                <h4>申请已提交</h4>
                                <p>已通过邮件发送给站长，审核通过后即可在上方友链列表看到你的站点。</p>
                                <button type="button" class="lap-done-btn" data-cmt-apply-close>知道了</button>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                .lap-card { padding: 0; overflow-x: hidden; }
                .lap-card .lap-close { background: rgba(0,0,0,.05); color: var(--text); backdrop-filter: blur(6px); }
                .lap-card .lap-close:hover { background: rgba(0,0,0,.1); color: var(--text); }
                html.dark .lap-card .lap-close { background: rgba(255,255,255,.1); color: var(--text); }
                html.dark .lap-card .lap-close:hover { background: rgba(255,255,255,.18); }

                /* 头部：与留言板 .gb-hero 同款浅色玻璃卡 */
                .lap-hero {
                    position: relative; z-index: 1; overflow: hidden;
                    padding: 22px 54px 22px 22px;
                    border-radius: 20px 20px 0 0;
                    border-bottom: 1px solid var(--border);
                    background:
                        radial-gradient(120% 130% at 100% 0%, var(--accent-glow), transparent 58%),
                        linear-gradient(120deg, var(--accent-soft), rgba(255,255,255,.34) 62%, transparent),
                        var(--glass-bg);
                }
                html.dark .lap-hero {
                    background:
                        radial-gradient(120% 130% at 100% 0%, rgba(148,200,216,.2), transparent 58%),
                        linear-gradient(120deg, rgba(148,200,216,.12), rgba(255,255,255,.04) 62%, transparent),
                        var(--glass-bg);
                }
                .lap-deco {
                    position: absolute; right: 10px; top: 2px;
                    font-size: 96px; line-height: 1;
                    color: var(--accent); opacity: .13;
                    transform: rotate(-8deg);
                    pointer-events: none; user-select: none;
                }
                html.dark .lap-deco { opacity: .1; }
                .lap-hero-body { position: relative; z-index: 1; }
                .lap-badge {
                    display: inline-flex; align-items: center; gap: 5px;
                    padding: 3px 10px; border-radius: 999px;
                    background: rgba(255,255,255,.5);
                    border: 1px solid var(--glass-border);
                    font-size: 10px; letter-spacing: .08em; text-transform: uppercase;
                    color: var(--accent-hover);
                }
                html.dark .lap-badge { background: rgba(255,255,255,.06); color: var(--accent); }
                .lap-badge i { font-size: 10px; }
                .lap-hero-title {
                    display: flex; align-items: center; gap: 9px;
                    margin: 0;
                    font-size: 23px; font-weight: 700; letter-spacing: -.02em; line-height: 1.3;
                    color: var(--text);
                }
                .lap-badge { flex: none; }
                .lap-hero-sub { margin: 6px 0 0; font-size: 13px; color: var(--text-secondary); }

                .lap-body { position: relative; z-index: 1; padding: 20px 22px 22px; }
                .lap-form[hidden] { display: none; }
                .lap-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
                .lap-span { grid-column: 1 / -1; }
                .lap-field { display: block; min-width: 0; }
                .lap-label {
                    display: flex; align-items: center; gap: 5px;
                    font-size: 13px; font-weight: 600; color: var(--text-secondary, var(--text-light));
                    margin-bottom: 7px;
                }
                .lap-label i { font-size: 13px; color: var(--accent); }
                .lap-label b { color: var(--accent); font-weight: 700; }
                .lap-opt {
                    margin-left: auto; padding: 2px 7px; border-radius: 999px;
                    font-size: 11px; font-weight: 500; color: var(--text-light);
                    background: var(--accent-soft); letter-spacing: .2px;
                }
                .lap-field input, .lap-field textarea {
                    width: 100%; box-sizing: border-box;
                    padding: 11px 13px; font-size: 14px; line-height: 1.5;
                    color: var(--text);
                    background: rgba(0,0,0,.02);
                    border: 1px solid var(--border); border-radius: 11px;
                    outline: none;
                    transition: border-color .18s, box-shadow .18s, background .18s;
                    -webkit-appearance: none; appearance: none;
                }
                html.dark .lap-field input, html.dark .lap-field textarea { background: rgba(255,255,255,.04); }
                .lap-field textarea { resize: vertical; min-height: 78px; }
                .lap-field input::placeholder, .lap-field textarea::placeholder { color: var(--text-light); opacity: .75; }
                .lap-field input:focus, .lap-field textarea:focus {
                    background: var(--content-bg, transparent);
                    border-color: var(--accent);
                    box-shadow: 0 0 0 3px var(--accent-soft);
                }
                .lap-hint { display: block; margin-top: 6px; font-size: 12px; color: var(--text-light); }
                .lap-hp { position: absolute; left: -9999px; top: auto; width: 1px; height: 1px; overflow: hidden; }

                .lap-bar {
                    display: flex; align-items: center; justify-content: space-between; gap: 12px;
                    margin-top: 18px; padding-top: 16px;
                    border-top: 1px dashed var(--border);
                }
                .lap-bar-note { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-light); min-width: 0; }
                .lap-bar-note i { color: var(--accent); font-size: 14px; flex-shrink: 0; }
                .lap-submit {
                    flex-shrink: 0;
                    display: inline-flex; align-items: center; gap: 7px;
                    padding: 11px 24px; font-size: 14px; font-weight: 600; color: #fff;
                    background: linear-gradient(135deg, var(--accent), var(--accent-hover));
                    border: none; border-radius: 999px; cursor: pointer;
                    box-shadow: 0 6px 18px var(--accent-glow), inset 0 1px 0 rgba(255,255,255,.3);
                    transition: transform .18s var(--ease-spring-soft), box-shadow .2s, filter .2s;
                }
                .lap-submit:hover { transform: translateY(-1px); filter: brightness(1.05); box-shadow: 0 9px 22px var(--accent-glow); }
                .lap-submit:active { transform: translateY(0); }
                .lap-submit:disabled { opacity: .7; cursor: not-allowed; transform: none; }

                .lap-done { position: relative; z-index: 1; text-align: center; padding: 14px 6px 6px; }
                .lap-done[hidden] { display: none; }
                .lap-done-ico {
                    width: 62px; height: 62px; margin: 0 auto 14px;
                    border-radius: 50%; display: flex; align-items: center; justify-content: center;
                    font-size: 28px; color: #fff;
                    background: linear-gradient(135deg, #22c55e, #16a34a);
                    box-shadow: 0 10px 26px rgba(34,197,94,.4), inset 0 1px 0 rgba(255,255,255,.4);
                    animation: lapPop .4s var(--ease-spring-soft) both;
                }
                @keyframes lapPop { from { transform: scale(.5); opacity: 0; } to { transform: none; opacity: 1; } }
                .lap-done h4 { margin: 0; font-size: 16px; color: var(--text); }
                .lap-done p { margin: 8px auto 18px; max-width: 330px; font-size: 13px; line-height: 1.7; color: var(--text-light); }
                .lap-done-btn {
                    padding: 9px 30px; font-size: 13px; font-weight: 600; color: var(--text);
                    background: transparent; border: 1px solid var(--border); border-radius: 999px;
                    cursor: pointer; transition: background .18s, border-color .18s, color .18s;
                }
                .lap-done-btn:hover { background: var(--accent-soft); border-color: var(--accent); color: var(--accent); }

                /* 展开动画：卡片弹出 → 头部滑入 → 表单项逐条浮现 → 底部提交条收尾 */
                .cmt-modal.lap-modal.active .lap-card {
                    animation: lapCardUnfold .52s cubic-bezier(.16,1,.3,1) both;
                }
                .cmt-modal.lap-modal.active .lap-hero {
                    animation: lapLayerIn .44s cubic-bezier(.16,1,.3,1) .07s both;
                }
                .cmt-modal.lap-modal.active .lap-field,
                .cmt-modal.lap-modal.active .lap-bar {
                    animation: lapLayerIn .42s cubic-bezier(.16,1,.3,1) both;
                }
                .cmt-modal.lap-modal.active .lap-field:nth-child(1) { animation-delay: .16s; }
                .cmt-modal.lap-modal.active .lap-field:nth-child(2) { animation-delay: .20s; }
                .cmt-modal.lap-modal.active .lap-field:nth-child(3) { animation-delay: .24s; }
                .cmt-modal.lap-modal.active .lap-field:nth-child(4) { animation-delay: .28s; }
                .cmt-modal.lap-modal.active .lap-field:nth-child(5) { animation-delay: .32s; }
                .cmt-modal.lap-modal.active .lap-bar { animation-delay: .38s; }
                .cmt-modal.lap-modal.closing .lap-card { animation: modalOut .2s var(--ease-in-out) both; }
                @keyframes lapCardUnfold {
                    0%   { opacity: 0; transform: translateY(22px) scale(.93); }
                    60%  { opacity: 1; }
                    100% { opacity: 1; transform: none; }
                }
                @keyframes lapLayerIn {
                    from { opacity: 0; transform: translateY(10px); }
                    to   { opacity: 1; transform: none; }
                }
                @media (prefers-reduced-motion: reduce) {
                    .cmt-modal.lap-modal.active .lap-card,
                    .cmt-modal.lap-modal.active .lap-hero,
                    .cmt-modal.lap-modal.active .lap-field,
                    .cmt-modal.lap-modal.active .lap-bar { animation: none; }
                }

                @media (max-width: 640px) {
                    .cmt-modal.lap-modal { padding: 10px; }
                    .cmt-modal.lap-modal .cmt-modal-card { max-height: calc(100vh - 20px); padding: 0; border-radius: 16px; }
                    .lap-hero { padding: 16px 44px 16px 16px; }
                    .lap-deco { font-size: 72px; right: 2px; top: 0; }
                    .lap-badge { font-size: 10px; padding: 2px 8px; }
                    .lap-hero-title { font-size: 19px; }
                    .lap-hero-sub { font-size: 12px; margin-top: 4px; }
                    .lap-body { padding: 12px 12px calc(12px + env(safe-area-inset-bottom)); }
                    .lap-grid { grid-template-columns: 1fr 1fr; gap: 8px 9px; }
                    .lap-label { font-size: 11px; gap: 4px; margin-bottom: 5px; white-space: nowrap; }
                    .lap-label i { font-size: 12px; }
                    .lap-opt { padding: 1px 4px; font-size: 10px; }
                    .lap-field input, .lap-field textarea { font-size: 14px; padding: 8px 10px; }
                    .lap-field textarea { min-height: 54px; }
                    .lap-hint { font-size: 11px; margin-top: 4px; }
                    .lap-bar { flex-direction: column; align-items: stretch; gap: 8px; margin-top: 12px; padding-top: 10px; }
                    .lap-bar-note { justify-content: center; text-align: center; font-size: 11px; }
                    .lap-bar-note i { font-size: 13px; }
                    .lap-submit { justify-content: center; width: 100%; padding: 10px 20px; font-size: 13px; }
                    .lap-done { padding: 8px 4px 4px; }
                    .lap-done-ico { width: 48px; height: 48px; font-size: 22px; margin-bottom: 10px; }
                    .lap-done h4 { font-size: 15px; }
                    .lap-done p { font-size: 12px; line-height: 1.65; margin: 6px auto 12px; }
                    .lap-done-btn { padding: 7px 24px; font-size: 13px; }
                    /* iOS Safari：聚焦 <16px 输入框会自动放大页面，故恢复 16px 防缩放 */
                    @supports (-webkit-touch-callout: none) {
                        .lap-field input, .lap-field textarea { font-size: 16px; }
                    }
                }
                </style>
                <script>
                (function () {
                    function done(btn) {
                        var icon = btn.querySelector('i');
                        var old = icon.className;
                        btn.classList.add('is-done');
                        icon.className = 'ri-check-line';
                        setTimeout(function () {
                            btn.classList.remove('is-done');
                            icon.className = old;
                        }, 1200);
                    }
                    function fallback(text, btn) {
                        var ta = document.createElement('textarea');
                        ta.value = text;
                        ta.style.position = 'fixed';
                        ta.style.opacity = '0';
                        document.body.appendChild(ta);
                        ta.select();
                        try { document.execCommand('copy'); done(btn); } catch (e) {}
                        document.body.removeChild(ta);
                    }
                    document.addEventListener('click', function (e) {
                        var btn = e.target.closest('.lk-copy');
                        if (!btn) return;
                        var text = btn.getAttribute('data-copy') || '';
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(text).then(function () { done(btn); }, function () { fallback(text, btn); });
                        } else {
                            fallback(text, btn);
                        }
                    });
                })();
                </script>
            </div>
        </div>
        <div class="fade-bottom" id="fadeBottom"></div>
    </div>

<?php $this->need('footer.php'); ?>
