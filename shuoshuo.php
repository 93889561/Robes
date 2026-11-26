<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 说说
 *
 * @package custom
 * @type page
 * @title 说说
 */

$isSSAdmin = $this->user->hasLogin();
$ssDb = \Typecho\Db::get();
$ssTable = $ssDb->getPrefix() . 'shuoshuo';
$ssDebug = array();

/* ===== 数据表维护 ===== */
if (!function_exists('ssEnsureTable')) {
function ssEnsureTable($table, &$dbg) {
    $db = \Typecho\Db::get();
    $schema = "(`id` int unsigned NOT NULL AUTO_INCREMENT, `content` text NOT NULL, `imgs` text, `created` int unsigned NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    try {
        try { $db->query("SELECT `imgs` FROM `{$table}` LIMIT 0"); }
        catch (\Exception $e) {
            $db->query("DROP TABLE IF EXISTS `{$table}`");
            $db->query("CREATE TABLE `{$table}` {$schema}");
        }
    } catch (\Exception $e) {}
}
}

/* ===== 图片上传 ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ss_action']) && $_POST['ss_action'] === 'ss_upload') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$isSSAdmin) { echo json_encode(array('ok' => false, 'msg' => '未登录')); exit; }
    if (empty($_FILES['image'])) { echo json_encode(array('ok' => false, 'msg' => '无文件')); exit; }
    $f = $_FILES['image'];
    if ($f['error'] !== UPLOAD_ERR_OK) { echo json_encode(array('ok' => false, 'msg' => '上传失败')); exit; }
    if ($f['size'] > 5 * 1024 * 1024) { echo json_encode(array('ok' => false, 'msg' => '不能超过 5MB')); exit; }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, array('jpg','jpeg','png','gif','webp','bmp'))) { echo json_encode(array('ok' => false, 'msg' => '格式不支持')); exit; }
    $dir = __TYPECHO_ROOT_DIR__ . '/usr/uploads/' . date('Y') . '/' . date('m');
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = date('Ymd') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) { echo json_encode(array('ok' => false, 'msg' => '保存失败')); exit; }
    echo json_encode(array('ok' => true, 'url' => '/usr/uploads/' . date('Y') . '/' . date('m') . '/' . $name));
    exit;
}

/* ===== 删除 ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ss_action']) && $_POST['ss_action'] === 'ss_delete') {
    $redirectUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    if ($isSSAdmin && isset($_POST['ss_id'])) {
        try { $ssDb->query("DELETE FROM `{$ssTable}` WHERE `id` = " . intval($_POST['ss_id']) . " LIMIT 1"); } catch (\Exception $e) {}
    }
    header('Location: ' . $redirectUrl);
    exit;
}

/* ===== 发布 ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ss_action']) && $_POST['ss_action'] === 'ss_publish') {
    if (!$isSSAdmin) { header('HTTP/1.1 403 Forbidden'); exit('Forbidden'); }
    $content = isset($_POST['ss_content']) ? trim($_POST['ss_content']) : '';
    $redirectUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    if ($content === '') { header('Location: ' . $redirectUrl); exit; }
    try {
        ssEnsureTable($ssTable, $ssDebug);
        $now = time();
        $imgs = array();
        if (preg_match_all('/!\[[^\]]*\]\(([^)]+)\)/', $content, $m)) $imgs = array_merge($imgs, $m[1]);
        if (preg_match_all('/(https?:\/\/[^\s"\'<>\)]+\.(?:jpg|jpeg|png|gif|webp|bmp))/i', $content, $m)) $imgs = array_merge($imgs, $m[0]);
        if (preg_match_all('/(\/usr\/uploads\/[^\s"\'<>\)]+)/i', $content, $m)) $imgs = array_merge($imgs, $m[1]);
        $imgs = array_values(array_unique($imgs));
        try {
            $ssDb->query($ssDb->insert('table.shuoshuo')->rows(array('content' => $content, 'imgs' => implode(',', $imgs), 'created' => $now)));
        } catch (\Exception $e1) {
            $ssDb->query("INSERT INTO `{$ssTable}` (`content`, `imgs`, `created`) VALUES ('" . addslashes($content) . "', '" . addslashes(implode(',', $imgs)) . "', {$now})");
        }
    } catch (\Exception $e) {}
    header('Location: ' . $redirectUrl);
    exit;
}

/* ===== 查询 ===== */
ssEnsureTable($ssTable, $ssDebug);
$ssItems = array();
try {
    $rows = $ssDb->fetchAll($ssDb->select('id','content','imgs','created')->from('table.shuoshuo')->order('created', \Typecho\Db::SORT_DESC)->limit(200));
} catch (\Exception $e) {
    try { $rows = $ssDb->fetchAll("SELECT `id`,`content`,`imgs`,`created` FROM `{$ssTable}` ORDER BY `created` DESC LIMIT 200"); }
    catch (\Exception $e2) { $rows = array(); }
}
foreach ($rows as $row) {
    $imgs = array();
    if (!empty($row['imgs'])) $imgs = array_values(array_filter(explode(',', $row['imgs'])));
    $plain = preg_replace('/!\[[^\]]*\]\([^)]+\)/', '', $row['content']);
    $plain = preg_replace('/(https?:\/\/[^\s"\'<>\)]+\.(?:jpg|jpeg|png|gif|webp|bmp))/i', '', $plain);
    $plain = preg_replace('/(\/usr\/uploads\/[^\s"\'<>\)]+)/i', '', $plain);
    $ssItems[] = array('id'=>(int)$row['id'], 'text'=>trim($plain), 'imgs'=>array_slice($imgs,0,9), 'created'=>(int)$row['created']);
}

if (!function_exists('ssTimeAgo')) {
function ssTimeAgo($ts) {
    $d = time() - $ts;
    if ($d < 60) return '刚刚';
    if ($d < 3600) return floor($d/60) . ' 分钟前';
    if ($d < 86400) return floor($d/3600) . ' 小时前';
    if ($d < 2592000) return floor($d/86400) . ' 天前';
    if ($d < 31536000) return floor($d/2592000) . ' 个月前';
    return date('Y年n月j日', $ts);
}
}

$ssCount = count($ssItems);
$ssLatest = $ssCount ? ssTimeAgo($ssItems[0]['created']) : '暂无';
$ssTotalImgs = 0;
foreach ($ssItems as $it) $ssTotalImgs += count($it['imgs']);

$this->need('header.php');
?>

<div class="main-body">
    <?php $this->need('sidebar.php'); ?>

    <div class="content-area">
        <div class="fade-top" id="fadeTop"></div>
        <div class="content-inner">
            <div class="content-scroll" id="feed">
                <article class="article standalone-page shuoshuo-page">

                    <!-- 头部横幅 -->
                    <section class="gb-hero shuoshuo-hero">
                        <span class="gb-deco"><i class="ri-chat-1-line"></i></span>
                        <div class="gb-hero-body">
                            <span class="gb-badge"><i class="ri-edit-line"></i>Moments</span>
                            <h1 class="gb-title">说说</h1>
                            <p class="gb-sub">随手记下的碎碎念，一两句话的小情绪。</p>
                        </div>
                        <div class="gb-stats">
                            <div class="gb-stat"><b><?php echo $ssCount; ?></b><span>条说说</span></div>
                            <div class="gb-stat"><b><?php echo $ssTotalImgs; ?></b><span>张配图</span></div>
                            <div class="gb-stat"><b><?php echo $ssLatest; ?></b><span>最近发布</span></div>
                        </div>
                    </section>

                    <!-- 发布框 -->
                    <?php if ($isSSAdmin): ?>
                    <div class="ss-composer">
                        <div class="ss-composer-top">
                            <span class="ss-ava">
                                <?php if (themeOption('logoImage')): ?><img src="<?php echo htmlspecialchars(themeOption('logoImage')); ?>" alt=""><?php else: ?><?php echo mb_substr($this->options->title, 0, 1, 'UTF-8'); ?><?php endif; ?>
                            </span>
                            <div class="ss-composer-body">
                                <textarea id="ssInput" class="ss-textarea" rows="3" placeholder="此刻，想留下点什么…" maxlength="2000"></textarea>
                                <div class="ss-preview" id="ssPreview"></div>
                            </div>
                        </div>
                        <div class="ss-composer-bar">
                            <div class="ss-bar-left">
                                <label class="ss-icon-btn" title="插入图片">
                                    <i class="ri-image-line"></i>
                                    <input type="file" id="ssFile" accept="image/*" multiple hidden>
                                </label>
                                <span class="ss-tip">支持配图 · 换行分段</span>
                            </div>
                            <button type="button" class="ss-publish-btn" id="ssPublishBtn"><i class="ri-arrow-up-line"></i> 发布</button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 说说列表 -->
                    <?php if (!empty($ssItems)): ?>
                    <div class="ss-feed-head">
                        <span class="ss-feed-title"><i class="ri-chat-1-line"></i>全部说说</span>
                        <span class="ss-feed-count"><?php echo $ssCount; ?> 条</span>
                    </div>
                    <div class="ss-feed">
                        <?php foreach ($ssItems as $idx => $item): ?>
                        <article class="ss-post" data-id="<?php echo $item['id']; ?>" style="animation-delay:<?php echo min($idx*45,450); ?>ms">
                            <div class="ss-post-head">
                                <span class="ss-ava ss-ava-sm">
                                    <?php if (themeOption('logoImage')): ?><img src="<?php echo htmlspecialchars(themeOption('logoImage')); ?>" alt=""><?php else: ?><?php echo mb_substr($this->options->title, 0, 1, 'UTF-8'); ?><?php endif; ?>
                                </span>
                                <div class="ss-post-meta">
                                    <span class="ss-post-name"><?php echo htmlspecialchars($this->options->title); ?><?php if ($idx === 0): ?><em class="ss-post-new">最新</em><?php endif; ?></span>
                                    <span class="ss-post-time" title="<?php echo date('Y-m-d H:i', $item['created']); ?>"><?php echo ssTimeAgo($item['created']); ?></span>
                                </div>
                                <?php if ($isSSAdmin): ?>
                                <form method="post" class="ss-del-form" onsubmit="return confirm('确定删除这条说说吗？')">
                                    <input type="hidden" name="ss_action" value="ss_delete">
                                    <input type="hidden" name="ss_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="ss-del-btn" title="删除"><i class="ri-delete-bin-line"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <div class="ss-post-body">
                                <?php if ($item['text'] !== ''): ?>
                                <div class="ss-post-text"><?php echo nl2br(htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8')); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item['imgs'])): ?>
                                <div class="ss-grid cols-<?php echo min(count($item['imgs']), 9); ?>">
                                    <?php foreach ($item['imgs'] as $img): ?>
                                    <div class="ss-grid-cell"><img src="<?php echo htmlspecialchars($img); ?>" alt="" loading="lazy"></div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                    <?php elseif (!$isSSAdmin): ?>
                    <div class="ss-empty">
                        <div class="ss-empty-ico"><i class="ri-chat-1-line"></i></div>
                        <p>还没有说说</p>
                    </div>
                    <?php endif; ?>

                </article>
            </div>
        </div>
        <div class="fade-bottom" id="fadeBottom"></div>
    </div>

<?php if ($isSSAdmin): ?>
<script>
(function() {
    var input = document.getElementById('ssInput');
    var fileInput = document.getElementById('ssFile');
    var preview = document.getElementById('ssPreview');
    var publishBtn = document.getElementById('ssPublishBtn');
    if (!publishBtn) return;

    publishBtn.addEventListener('click', function() {
        var txt = input.value.trim();
        if (!txt) { input.focus(); return; }
        publishBtn.disabled = true;
        publishBtn.innerHTML = '发布中…';
        var fd = new FormData();
        fd.append('ss_action', 'ss_publish');
        fd.append('ss_content', txt);
        fetch(location.href, { method: 'POST', body: fd }).then(function() { location.reload(); }).catch(function() { location.reload(); });
    });

    fileInput.addEventListener('change', function() {
        var files = fileInput.files;
        if (!files) return;
        for (var i = 0; i < files.length; i++) uploadImg(files[i]);
        fileInput.value = '';
    });

    function uploadImg(file) {
        var fd = new FormData();
        fd.append('ss_action', 'ss_upload');
        fd.append('image', file);
        var th = document.createElement('div');
        th.className = 'ss-preview-item is-loading';
        var im = document.createElement('img');
        im.src = URL.createObjectURL(file);
        th.appendChild(im);
        preview.appendChild(th);
        fetch(location.href, { method: 'POST', body: fd }).then(function(r) { return r.json(); }).then(function(d) {
            if (d.ok && d.url) {
                th.classList.remove('is-loading');
                var tag = '![' + file.name.replace(/\.[^.]+$/, '') + '](' + d.url + ')';
                var pos = input.selectionStart || input.value.length;
                input.value = input.value.slice(0, pos) + tag + input.value.slice(pos);
                th.addEventListener('click', function() { input.value = input.value.replace(tag, ''); th.remove(); });
            } else { th.remove(); alert(d.msg || '上传失败'); }
        }).catch(function() { th.remove(); });
    }
})();
</script>
<?php endif; ?>

<?php $this->need('footer.php'); ?>
