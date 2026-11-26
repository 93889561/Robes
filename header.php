<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?php $this->archiveTitle(' - ', '', ' - '); ?><?php $this->options->title(); ?></title>
<meta name="description" content="<?php $this->options->description(); ?>">
<meta name="theme-color" content="<?php echo themeOption('accentColor', '#839ac9'); ?>" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#1B2230" media="(prefers-color-scheme: dark)">

<?php $favicon = themeOption('faviconUrl', ''); if ($favicon): ?>
<link rel="icon" href="<?php echo htmlspecialchars($favicon); ?>" type="image/png">
<?php endif; ?>

<link rel="alternate" type="application/rss+xml" title="<?php $this->options->title(); ?> RSS" href="<?php $this->options->feedUrl(); ?>">

<?php themeSEO(); ?>
<?php themeJSONLD(); ?>

<link rel="preconnect" href="https://weavatar.com" crossorigin>
<link rel="dns-prefetch" href="//weavatar.com">
<link rel="dns-prefetch" href="//cravatar.cn">
<link rel="dns-prefetch" href="//cdn.bootcdn.net">
<link rel="preconnect" href="https://cdn.bootcdn.net" crossorigin>
<?php
$v = ROBES_ASSET_VER; 
$tv = $this->options->themeUrl; 
?>
<link rel="preload" href="<?php echo $tv . '/css/base.css?v=' . $v; ?>" as="style">
<link rel="preload" href="<?php echo $tv . '/js/main.v2.js?v=' . $v; ?>" as="script">
<link rel="stylesheet" href="<?php echo $tv . '/css/ri.css?v=' . $v; ?>" media="print" onload="this.media='all'">
<?php if ($this->is('post') || $this->is('page')): ?>
<link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/highlight.js/11.9.0/styles/github.min.css" media="print" onload="this.media='all'">
<?php endif; ?>
<link rel="stylesheet" href="<?php echo $tv . '/css/base.css?v=' . $v; ?>">
<link rel="stylesheet" href="<?php echo $tv . '/css/home.css?v=' . $v; ?>">
<link rel="stylesheet" href="<?php echo $tv . '/css/components.css?v=' . $v; ?>">
<link rel="stylesheet" href="<?php echo $tv . '/css/article.css?v=' . $v; ?>">
<link rel="stylesheet" href="<?php echo $tv . '/css/pages.css?v=' . $v; ?>">
<?php themeCSSVars(); ?>
<?php $this->header('atom=&rss2=&commentReply=&pingback=&xmlrpc='); ?>
</head>
<body data-page="<?php echo $this->is('index') ? 'index' : 'inner'; ?>"<?php if ($this->user->hasLogin() && ($this->is('post') || $this->is('page'))): ?> data-edit-url="<?php $this->options->adminUrl($this->is('post') ? 'write-post.php?cid=' . $this->cid : 'write-page.php?cid=' . $this->cid); ?>"<?php endif; ?>>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="page">
  <div class="shell glass">
    
    <header class="topbar">
      <button class="topbar-btn info-btn" id="siteInfoBtn" title="站点信息" aria-label="站点信息">
        <svg class="topbar-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M12 16.4v-5"/><circle cx="12" cy="7.9" r="1" fill="currentColor" stroke="none"/></svg>
      </button>
      <button class="topbar-btn m-search-btn" id="mSearchBtn" aria-label="搜索" title="搜索">
        <svg class="topbar-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="10.6" cy="10.6" r="6.6"/><path d="M15.4 15.4l4.3 4.3"/></svg>
      </button>
      <button class="hamburger" id="hamburgerBtn" aria-label="打开导航" title="导航">
        <svg class="topbar-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7.2h16M4 12h16M4 16.8h16"/></svg>
      </button>
      <div class="topbar-brand">
        <?php $logoImg = themeOption('logoImage', ''); if ($logoImg): ?>
          <img class="brand-logo-img" src="<?php echo $logoImg; ?>" alt="<?php echo htmlspecialchars($this->options->title); ?>">
        <?php else: ?>
          <div class="brand-icon"><?php echo mb_substr($this->options->title, 0, 1); ?></div>
        <?php endif; ?>
        <span class="brand-name"><?php echo htmlspecialchars($this->options->title); ?></span>
      </div>
      <div class="topbar-center">
        <svg class="topbar-ico topbar-search-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="10.6" cy="10.6" r="6.6"/><path d="M15.4 15.4l4.3 4.3"/></svg>
        <form action="<?php $this->options->siteUrl(); ?>" method="get" style="display:contents;">
          <input class="topbar-search" type="text" name="s" placeholder="搜索文章…" value="<?php if($this->is('search')) $this->archiveTitle(' - ', '', ''); ?>">
        </form>
        
        <?php if ($logoImg): ?>
          <img class="m-brand-logo-img" src="<?php echo $logoImg; ?>" alt="">
        <?php else: ?>
          <div class="m-brand-icon"><?php echo mb_substr($this->options->title, 0, 1); ?></div>
        <?php endif; ?>
        <span class="m-brand-name"><?php echo htmlspecialchars($this->options->title); ?></span>
      </div>
      <div class="site-info-pop" id="siteInfoPop">
        <span><?php echo themeOption('footerText', '© 2026 ' . $this->options->title); ?></span>
        <?php $icp = themeOption('icpText', ''); if ($icp): ?>
        <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($icp); ?></a>
        <?php endif; ?>
      </div>
      <div class="topbar-spacer"></div>
      <div class="topbar-actions">
        <?php
        $noticeLogin = $this->user->hasLogin();
        /* 游客身份复用私密评论的签名 Cookie，同一浏览器即可看到自己的评论与通知 */
        if (!class_exists('RobesPrivateComments')) {
            $__privFile = __TYPECHO_ROOT_DIR__ . '/usr/themes/robes/comment-private.php';
            if (is_file($__privFile)) require_once $__privFile;
        }
        $noticeMine = $noticeLogin || (class_exists('RobesPrivateComments') && RobesPrivateComments::hasMine());
        $noticeIdentity = $noticeLogin || (class_exists('RobesPrivateComments') && RobesPrivateComments::guestKey() !== '');
        $noticeBoardRaw = trim((string) themeOption('noticeBoard', ''));
        $noticeBoardItems = array();
        if ($noticeBoardRaw !== '') {
            foreach (preg_split('/\r\n|\r|\n/', $noticeBoardRaw) as $noticeLine) {
                $noticeLine = trim($noticeLine);
                if ($noticeLine === '') continue;
                $noticeDate = '';
                if (preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2})\s*[|｜]\s*(.+)$/u', $noticeLine, $noticeMatch)) {
                    $noticeDate = $noticeMatch[1];
                    $noticeLine = trim($noticeMatch[2]);
                }
                $noticeBoardItems[] = array('date' => $noticeDate, 'text' => $noticeLine, 'idx' => count($noticeBoardItems));
            }
            /* 最新发布的公告排在最前：有日期的按日期倒序，无日期的按编辑顺序排在后面 */
            usort($noticeBoardItems, function ($a, $b) {
                if ($a['date'] === '' && $b['date'] !== '') return 1;
                if ($a['date'] !== '' && $b['date'] === '') return -1;
                $cmp = strcmp((string) $b['date'], (string) $a['date']);
                return $cmp !== 0 ? $cmp : ((int) $a['idx'] - (int) $b['idx']);
            });
        }
        /* 站长心情：存在 usr/cache/notice/mood.php，站长在「漂流瓶」面板里直接改 */
        $vibeMood = '';
        $vibeMoodEmoji = '';
        $vibeMoodStatus = '';
        $vibeMoodTime = '';
        $vibeSongName = '';
        $vibeSongArtist = '';
        $vibeSongCover = '';
        $vibeSongPreview = '';
        $vibeSongList = array();
        $vibeSongsJson = '[]';
        $vibeMoodFile = dirname(__DIR__, 2) . '/cache/notice/mood.php';
        if (is_file($vibeMoodFile)) {
            $vibeRaw = (string) @file_get_contents($vibeMoodFile);
            if (strpos($vibeRaw, '<?php') === 0) {
                $vibePos = strpos($vibeRaw, "\n");
                $vibeRaw = $vibePos === false ? '' : substr($vibeRaw, $vibePos + 1);
            }
            $vibeJson = json_decode(trim($vibeRaw), true);
            if (is_array($vibeJson)) {
                $vibeMood = isset($vibeJson['text']) ? trim((string) $vibeJson['text']) : '';
                $vibeMoodEmoji = isset($vibeJson['emoji']) ? trim((string) $vibeJson['emoji']) : '';
                $vibeMoodStatus = isset($vibeJson['status']) ? trim((string) $vibeJson['status']) : '';
                if (!empty($vibeJson['time'])) {
                    $vibeMoodTime = date('n月j日 H:i', (int) $vibeJson['time']);
                }
                /* 歌单优先；只有旧的 song 字段时也当成一首歌 */
                if (!empty($vibeJson['songs']) && is_array($vibeJson['songs'])) {
                    $vibeSongRaw = $vibeJson['songs'];
                } elseif (!empty($vibeJson['song']) && is_array($vibeJson['song'])) {
                    $vibeSongRaw = array($vibeJson['song']);
                } else {
                    $vibeSongRaw = array();
                }
                foreach ($vibeSongRaw as $vibeSongItem) {
                    if (!is_array($vibeSongItem)) continue;
                    $vibeName = isset($vibeSongItem['name']) ? trim((string) $vibeSongItem['name']) : '';
                    if ($vibeName === '') continue;
                    $vibeSongList[] = array(
                        'name' => $vibeName,
                        'artist' => isset($vibeSongItem['artist']) ? trim((string) $vibeSongItem['artist']) : '',
                        'cover' => isset($vibeSongItem['cover']) ? trim((string) $vibeSongItem['cover']) : '',
                        'preview' => isset($vibeSongItem['preview']) ? trim((string) $vibeSongItem['preview']) : '',
                        'link' => isset($vibeSongItem['link']) ? trim((string) $vibeSongItem['link']) : '',
                    );
                    if (count($vibeSongList) >= 5) break;
                }
                if ($vibeSongList) {
                    $vibeSong = $vibeSongList[0];
                    $vibeSongName = $vibeSong['name'];
                    $vibeSongArtist = $vibeSong['artist'];
                    $vibeSongCover = $vibeSong['cover'];
                    $vibeSongPreview = $vibeSong['preview'];
                    $vibeSongsJson = (string) json_encode($vibeSongList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
        }
        if ($vibeMood === '') $vibeMood = '今天也在认真生活，慢慢来，别着急。';

        /* 编辑入口与文章右上角的编辑按钮同一套判断：只要当前已登录就输出。
           写入是否合法由 mood.php 的 HMAC 令牌校验兜底。 */
        $vibeAdmin = false;
        $vibeMoodToken = '';
        $vibeUser = null;
        try {
            $vibeUser = \Widget\User::alloc();
        } catch (\Throwable $vibeErr) {
            $vibeUser = null;
        }
        if ($vibeUser && $vibeUser->hasLogin()) {
            $vibeAdmin = true;
            try {
                $vibeDb = \Typecho\Db::get();
                $vibeAuth = $vibeDb->fetchObject($vibeDb->select('authCode')->from('table.users')->where('uid = ?', (int) $vibeUser->uid));
                $vibeMoodToken = hash_hmac('sha256', 'robes-mood|' . (int) $vibeUser->uid, $vibeAuth ? (string) $vibeAuth->authCode : '');
            } catch (\Throwable $vibeErr) {}
        }
        $vibeWeek = array('日', '一', '二', '三', '四', '五', '六');
        $vibeDate = date('n月j日') . ' · 周' . $vibeWeek[(int) date('w')];
        ?>
        <button class="topbar-btn notice-btn" id="noticeBtn" type="button" title="通知与公告" aria-label="通知与公告" aria-haspopup="true" aria-expanded="false">
          <svg class="notice-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M3.2 7.4a2.4 2.4 0 0 1 2.4-2.4h12.8a2.4 2.4 0 0 1 2.4 2.4v9.2a2.4 2.4 0 0 1-2.4 2.4H5.6a2.4 2.4 0 0 1-2.4-2.4V7.4Z"/>
            <path d="M3.6 7.2l8.4 5.5 8.4-5.5"/>
          </svg>
          <span class="notice-island-vinyl" aria-hidden="true"></span>
          <span class="notice-island-label" aria-hidden="true"><span class="notice-island-eq"><i></i><i></i><i></i><i></i></span><span class="notice-island-song" id="noticeIslandSong" hidden></span><span class="notice-island-title">通知中心</span><span class="notice-island-count" id="noticeIslandCount" hidden></span></span>
          <span class="notice-badge" id="noticeBadge" aria-live="polite" hidden></span>
        </button>
        <a class="topbar-btn" id="editPostBtn" href="#" title="编辑文章" target="_blank" style="display:none"><i class="ri-edit-line"></i></a>
        <div class="notice-pop" id="noticePop" aria-hidden="true" data-identity="<?php echo $noticeIdentity ? '1' : '0'; ?>" data-mine="<?php echo $noticeMine ? '1' : '0'; ?>" data-endpoint="<?php echo htmlspecialchars($tv, ENT_QUOTES, 'UTF-8'); ?>/notice.php">
          <div class="notice-head">
            <div class="notice-head-top">
              <span class="notice-head-title"><i class="ri-mail-line"></i>通知中心<span class="notice-head-count" id="noticeHeadCount" hidden></span></span>
              <div class="notice-head-actions">
                <button type="button" class="notice-markall" id="noticeMarkAll" title="全部标为已读" hidden>全部已读</button>
                <button type="button" class="notice-refresh" id="noticeRefresh" title="刷新" aria-label="刷新">
                  <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M17.65 6.35A7.95 7.95 0 0 0 12 4a8 8 0 1 0 7.75 10h-2.08A6 6 0 1 1 12 6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35Z"/></svg>
                </button>
                <button type="button" class="notice-close" id="noticeClose" title="收起" aria-label="收起"><i class="ri-close-line"></i></button>
              </div>
            </div>
            <div class="notice-tabs" role="tablist" aria-label="通知分类">
              <span class="notice-tab-thumb" aria-hidden="true"></span>
              <button type="button" class="notice-tab is-active" data-tab="board" role="tab"><span class="notice-tab-label">公告</span><span class="notice-new-dot" id="noticeBoardDot" hidden></span></button>
              <button type="button" class="notice-tab" data-tab="mood" role="tab"><span class="notice-tab-label">心情</span></button>
              <button type="button" class="notice-tab" data-tab="vibe" role="tab"><span class="notice-tab-label">漂流瓶</span></button>
              <button type="button" class="notice-tab" data-tab="mine" role="tab"><span class="notice-tab-label">我的评论</span></button>
              <button type="button" class="notice-tab" data-tab="latest" role="tab"><span class="notice-tab-label">最新评论</span><span class="notice-tab-count" id="noticeTabCount" hidden></span></button>
            </div>
          </div>
          <div class="notice-board-view" id="noticeBoardView">
            <?php if ($noticeBoardItems): ?>
            <div class="notice-board-list">
              <?php foreach ($noticeBoardItems as $noticeItem): ?>
              <div class="notice-board-item" data-date="<?php echo htmlspecialchars($noticeItem['date'], ENT_QUOTES, 'UTF-8'); ?>" data-key="<?php echo substr(md5($noticeItem['date'] . '|' . $noticeItem['text']), 0, 12); ?>">
                <span class="notice-board-bullet"></span>
                <div class="notice-board-main">
                  <p class="notice-board-text"><?php echo htmlspecialchars($noticeItem['text'], ENT_QUOTES, 'UTF-8'); ?></p>
                  <?php if ($noticeItem['date'] !== ''): ?><span class="notice-board-date"><?php echo htmlspecialchars($noticeItem['date'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="notice-empty"><i class="ri-information-line"></i><p>暂无站内公告</p></div>
            <?php endif; ?>
          </div>
          <div class="notice-list" id="noticeList" hidden></div>
          <div class="notice-mood" id="noticeMood" hidden data-mood="<?php echo htmlspecialchars($tv, ENT_QUOTES, 'UTF-8'); ?>/mood.php" data-song="<?php echo htmlspecialchars($tv, ENT_QUOTES, 'UTF-8'); ?>/song.php" data-mood-icons="<?php echo htmlspecialchars(json_encode(robesMoodIconMap(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="mood-card<?php echo $vibeMoodStatus !== '' ? ' has-status' : ''; ?>">
              <span class="mood-ava-wrap">
                <span class="mood-ava"><?php $logoImg = themeOption('logoImage', ''); if ($logoImg): ?><img src="<?php echo htmlspecialchars($logoImg, ENT_QUOTES, 'UTF-8'); ?>" alt=""><?php else: ?><?php echo htmlspecialchars(mb_substr($this->options->title, 0, 1), ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></span>
                <span class="mood-online" title="在线" aria-label="在线"></span>
              </span>
              <div class="mood-card-body">
                <div class="mood-card-top">
                  <span class="mood-owner"><?php echo htmlspecialchars($this->options->title, ENT_QUOTES, 'UTF-8'); ?></span>
                  <span class="mood-badge">站长</span>
                  <span class="mood-card-date"><?php echo htmlspecialchars($vibeDate, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <span class="mood-status-pill" id="vibeStatusChip"<?php if ($vibeMoodStatus === '') echo ' hidden'; ?>><span class="mood-status-emoji" id="vibeStatusEmoji"><?php $vibeIconSvg = robesMoodIcon($vibeMoodEmoji); echo $vibeIconSvg !== '' ? $vibeIconSvg : htmlspecialchars($vibeMoodEmoji !== '' ? $vibeMoodEmoji : '🌿', ENT_QUOTES, 'UTF-8'); ?></span><span class="mood-status-text" id="vibeStatusText"><?php echo htmlspecialchars($vibeMoodStatus, ENT_QUOTES, 'UTF-8'); ?></span></span>
                <p class="mood-text" id="vibeMoodText"><?php echo htmlspecialchars($vibeMood, ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="mood-song" id="vibeMoodSongCard" data-playlist="<?php echo htmlspecialchars($vibeSongsJson, ENT_QUOTES, 'UTF-8'); ?>"<?php if ($vibeSongName === '') echo ' hidden'; ?>>
                  <span class="mood-song-cover" id="vibeSongCover"><?php if ($vibeSongCover !== ''): ?><img src="<?php echo htmlspecialchars($vibeSongCover, ENT_QUOTES, 'UTF-8'); ?>" alt="" loading="lazy"><?php else: ?><i class="ri-music-2-line"></i><?php endif; ?></span>
                  <span class="mood-song-meta">
                    <span class="mood-song-name" id="vibeSongName"><?php echo htmlspecialchars($vibeSongName, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="mood-song-artist" id="vibeSongArtist"><?php echo htmlspecialchars($vibeSongArtist, ENT_QUOTES, 'UTF-8'); ?></span>
                  </span>
                  <span class="mood-song-nav" id="vibeSongNav"<?php if (count($vibeSongList) < 2) echo ' hidden'; ?>>
                    <button type="button" class="mood-song-skip" id="vibeSongPrev" title="上一首" aria-label="上一首"><i class="ri-arrow-left-s-line"></i></button>
                    <button type="button" class="mood-song-skip" id="vibeSongNext" title="下一首" aria-label="下一首"><i class="ri-arrow-right-s-line"></i></button>
                  </span>
                  <button type="button" class="mood-song-play" id="vibeMoodPlay" data-src="<?php echo htmlspecialchars($vibeSongPreview, ENT_QUOTES, 'UTF-8'); ?>"<?php if ($vibeSongPreview === '') echo ' hidden'; ?> aria-label="播放试听"><span class="mood-play-glyph" aria-hidden="true"></span></button>
                  <span class="mood-song-bar" aria-hidden="true"><span id="vibeSongProgress"></span></span>
                </div>
                <div class="mood-card-foot" id="vibeMoodFoot">
                  <span class="mood-updated" id="vibeMoodUpdated"><?php echo $vibeMoodTime !== '' ? '更新于 ' . htmlspecialchars($vibeMoodTime, ENT_QUOTES, 'UTF-8') : ''; ?></span>
                  <?php if ($vibeAdmin): ?>
                  <button type="button" class="vibe-mood-edit" id="vibeMoodEdit" title="设置我的状态" aria-label="设置我的状态"><i class="ri-edit-line"></i>编辑</button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php if ($vibeAdmin): ?>
            <form class="vibe-drop-form vibe-mood-form" id="vibeMoodForm" hidden data-token="<?php echo htmlspecialchars($vibeMoodToken, ENT_QUOTES, 'UTF-8'); ?>">
              <div class="vibe-mood-head"><i class="ri-emotion-happy-line"></i>设置我的状态</div>
              <div class="vibe-mood-presets" id="vibeMoodPresets">
                <button type="button" class="vibe-mood-chip" data-emoji="🐟" data-status="摸鱼"><?php echo robesMoodIcon('🐟'); ?>摸鱼</button>
                <button type="button" class="vibe-mood-chip" data-emoji="☕" data-status="喝咖啡"><?php echo robesMoodIcon('☕'); ?>喝咖啡</button>
                <button type="button" class="vibe-mood-chip" data-emoji="💻" data-status="写代码"><?php echo robesMoodIcon('💻'); ?>写代码</button>
                <button type="button" class="vibe-mood-chip" data-emoji="🎧" data-status="听歌"><?php echo robesMoodIcon('🎧'); ?>听歌</button>
                <button type="button" class="vibe-mood-chip" data-emoji="🌙" data-status="想静静"><?php echo robesMoodIcon('🌙'); ?>想静静</button>
                <button type="button" class="vibe-mood-chip" data-emoji="🚶" data-status="出门走走"><?php echo robesMoodIcon('🚶'); ?>出门走走</button>
              </div>
              <div class="vibe-mood-fields">
                <input type="text" id="vibeMoodEmojiInput" maxlength="4" placeholder="表情" autocomplete="off" value="<?php echo htmlspecialchars($vibeMoodEmoji, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="text" id="vibeMoodStatusInput" maxlength="8" placeholder="状态，比如：喝咖啡" autocomplete="off" value="<?php echo htmlspecialchars($vibeMoodStatus, ENT_QUOTES, 'UTF-8'); ?>">
              </div>
              <div class="vibe-mood-song">
                <div class="vibe-song-search">
                  <i class="ri-music-2-line"></i>
                  <input type="text" id="vibeSongInput" placeholder="搜索歌曲，最多添加 5 首" autocomplete="off">
                </div>
                <div class="vibe-song-results" id="vibeSongResults" hidden></div>
                <div class="vibe-song-picked" id="vibeSongPicked" hidden></div>
                <div class="vibe-song-hint">按顺序循环播放，每次打开随机从其中一首开始；点列表里的歌可设为当前播放</div>
              </div>
              <textarea id="vibeMoodTextarea" maxlength="60" rows="2" placeholder="此刻的心情，一句话就好…"><?php echo htmlspecialchars($vibeMood === '今天也在认真生活，慢慢来，别着急。' ? '' : $vibeMood, ENT_QUOTES, 'UTF-8'); ?></textarea>
              <div class="vibe-mood-foot">
                <span class="vibe-mood-hint">状态和心情会写在上面的便签里</span>
                <span class="vibe-mood-actions">
                  <button type="button" class="vibe-mood-cancel" id="vibeMoodCancel">取消</button>
                  <button type="submit" class="vibe-mood-save" id="vibeMoodSave"><i class="ri-check-line"></i>保存</button>
                </span>
              </div>
            </form>
            <?php endif; ?>
          </div>
          <div class="notice-vibe" id="noticeVibe" hidden data-bottle="<?php echo htmlspecialchars($tv, ENT_QUOTES, 'UTF-8'); ?>/bottle.php">
            <div class="vibe-chat-head">
              <span class="vibe-hero-ico"><i class="ri-message-3-line"></i></span>
              <span class="vibe-chat-txt">
                <span class="vibe-chat-title">漂流瓶</span>
                <span class="vibe-chat-sub">匿名信笺，捞起陌生人的心事</span>
              </span>
              <button type="button" class="vibe-pick" id="vibePickBtn"><i class="ri-shuffle-line"></i>捞一个</button>
            </div>
            <div class="vibe-bottle-body" id="vibeBottleBody">
              <div class="vibe-bottle-empty">
                <span class="vibe-empty-ico"><i class="ri-mail-line"></i></span>
                <strong>海里漂着陌生人的心事</strong>
                <span class="vibe-empty-desc">点「捞一个」，随机打捞一只漂流瓶</span>
              </div>
            </div>
            <form class="vibe-drop-form vibe-composer" id="vibeDropForm">
              <div class="vibe-nick-row" id="vibeNickRow" hidden>
                <i class="ri-user-line"></i>
                <input type="text" id="vibeNick" maxlength="12" placeholder="昵称，留空则匿名" autocomplete="off">
              </div>
              <div class="vibe-composer-bar">
                <button type="button" class="vibe-nick-btn" id="vibeNickToggle" title="设置昵称" aria-label="设置昵称" aria-expanded="false"><i class="ri-user-line"></i></button>
                <textarea id="vibeText" maxlength="120" rows="1" placeholder="写点什么，让它漂向远方…"></textarea>
                <span class="vibe-drop-count" id="vibeDropCount">0/120</span>
                <button type="submit" class="vibe-send" id="vibeSend" title="扔进海里" aria-label="扔进海里"><i class="ri-share-forward-line"></i></button>
              </div>
            </form>
          </div>
          <div class="notice-foot" id="noticeFoot" hidden><button type="button" class="notice-more" id="noticeMore">加载更多</button></div>
        </div>
        <button class="topbar-btn scroll-progress-btn" id="scrollTopBtn" title="回到顶部" aria-label="回到顶部">
          <svg class="topbar-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 19V5"/><path d="M5.6 11.4L12 5l6.4 6.4"/></svg>
        </button>
        <button class="topbar-btn" id="themeToggle" title="切换深色模式" aria-label="切换深色模式">
          <span class="theme-ico theme-ico-moon"><svg class="topbar-ico topbar-ico-ri" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 7C10 10.866 13.134 14 17 14C18.9584 14 20.729 13.1957 21.9995 11.8995C22 11.933 22 11.9665 22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C12.0335 2 12.067 2 12.1005 2.00049C10.8043 3.27098 10 5.04157 10 7ZM4 12C4 16.4183 7.58172 20 12 20C15.0583 20 17.7158 18.2839 19.062 15.7621C18.3945 15.9187 17.7035 16 17 16C12.0294 16 8 11.9706 8 7C8 6.29648 8.08133 5.60547 8.2379 4.938C5.71611 6.28423 4 8.9417 4 12Z"/></svg></span>
          <span class="theme-ico theme-ico-sun"><svg class="topbar-ico topbar-ico-ri" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 18C8.68629 18 6 15.3137 6 12C6 8.68629 8.68629 6 12 6C15.3137 6 18 8.68629 18 12C18 15.3137 15.3137 18 12 18ZM12 16C14.2091 16 16 14.2091 16 12C16 9.79086 14.2091 8 12 8C9.79086 8 8 9.79086 8 12C8 14.2091 9.79086 16 12 16ZM11 1H13V4H11V1ZM11 20H13V23H11V20ZM3.51472 4.92893L4.92893 3.51472L7.05025 5.63604L5.63604 7.05025L3.51472 4.92893ZM16.9497 18.364L18.364 16.9497L20.4853 19.0711L19.0711 20.4853L16.9497 18.364ZM19.0711 3.51472L20.4853 4.92893L18.364 7.05025L16.9497 5.63604L19.0711 3.51472ZM5.63604 16.9497L7.05025 18.364L4.92893 20.4853L3.51472 19.0711L5.63604 16.9497ZM23 11V13H20V11H23ZM4 11V13H1V11H4Z"/></svg></span>
        </button>
      </div>
      <div class="m-search-pop" id="mSearchPop" aria-hidden="true">
        <form action="<?php $this->options->siteUrl(); ?>" method="get" class="m-search-form">
          <svg class="topbar-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="10.6" cy="10.6" r="6.6"/><path d="M15.4 15.4l4.3 4.3"/></svg>
          <input type="text" name="s" placeholder="搜索文章…" value="<?php if($this->is('search')) $this->archiveTitle(' - ', '', ''); ?>">
        </form>
        <button class="m-search-close" id="mSearchClose" aria-label="关闭搜索"><svg class="topbar-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6.4 6.4l11.2 11.2M17.6 6.4L6.4 17.6"/></svg></button>
      </div>
    </header>
