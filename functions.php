<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

if (!defined('ROBES_ASSET_VER')) define('ROBES_ASSET_VER', '2.7.36');

function themeConfig($form) {
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('logoImage', null, '', _t('Logo图片'), _t('填写图片URL，留空显示首字')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('footerText', null, '', _t('底部版权')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('icpText', null, '', _t('备案信息'), _t('如：粤ICP备12345678号')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('accentColor', null, '#839ac9', _t('强调色'), _t('如 #839ac9')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('bgColor', null, '#cfcfcf', _t('背景色')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('glassBlur', null, '20px', _t('模糊度'), _t('如 16px、20px')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('glassOpacity', null, '0.45', _t('透明度'), _t('0-1之间')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('sidebarWidth', null, '230', _t('侧边栏宽度'), _t('单位px')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Select('enablePJAX', array('1' => '开启', '0' => '关闭'), '1', _t('PJAX无刷新')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Select('enableCommentVerify', array('1' => '开启', '0' => '关闭'), '1', _t('评论验证'), _t('前台无感人机验证，增加机器人提交成本')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Select('enableLightbox', array('1' => '开启', '0' => '关闭'), '1', _t('图片灯箱')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('galleryCategoryId', null, '6', _t('相册分类ID'), _t('相册页面显示哪个分类下的文章')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('shuoshuoCategoryId', null, '', _t('说说分类ID'), _t('说说页面显示哪个分类下的文章，留空不显示')));

    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Select('heroSource', array('image' => '自定义图片', 'post' => '指定文章', 'cover' => '文章封面'), 'image', _t('图片位来源'), _t('自定义图片＝使用下面的图片地址；指定文章＝随机取该文章内的图片；文章封面＝使用各文章封面。都为空时显示主题色渐变')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('heroImage', null, '', _t('图片地址 / 文章ID'), _t('来源为「自定义图片」时填图片URL，来源为「指定文章」时填文章ID')));

    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Select('enableTOC', array('1' => '开启', '0' => '关闭'), '1', _t('文章目录')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Select('darkMode', array('auto' => '跟随系统', 'light' => '默认浅色', 'dark' => '默认深色', 'switch' => '用户切换'), 'auto', _t('深色模式'), _t('auto=跟随系统偏好, light=默认浅色, dark=默认深色, switch=允许用户手动切换')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Select('showSidebarCategories', array('1' => '显示', '0' => '隐藏'), '1', _t('侧边栏分类'), _t('是否在侧边栏显示分类列表')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Select('showSidebarTags', array('1' => '显示', '0' => '隐藏'), '1', _t('侧边栏标签'), _t('是否在侧边栏显示标签云')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Select('borderRadius', array('small' => '小 (8px)', 'medium' => '中 (14px)', 'large' => '大 (20px)'), 'medium', _t('圆角大小')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Select('enableFont', array('1' => '开启', '0' => '关闭'), '1', _t('自定义字体'), _t('开启后使用主题内置资源圆体（Resource Han Rounded），关闭回退系统字体')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Select('enablePostNav', array('1' => '显示', '0' => '隐藏'), '1', _t('上下篇导航'), _t('文章底部的上一篇/下一篇')));

    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Textarea('noticeBoard', null, '', _t('站内公告'), _t('显示在铃铛面板的「公告」里，每行一条。需要日期时写成「2026-11-05|公告内容」。留空则显示暂无公告')));

    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('socialGithub', null, '', _t('GitHub'), _t('填写用户名或完整链接')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('socialTwitter', null, '', _t('Twitter/X'), _t('填写用户名或完整链接')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('socialWeibo', null, '', _t('微博'), _t('填写主页链接')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('socialWechat', null, '', _t('微信'), _t('填写微信号')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('socialBilibili', null, '', _t('Bilibili'), _t('填写UID或主页链接')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('socialTelegram', null, '', _t('Telegram'), _t('填写用户名')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('socialEmail', null, '', _t('邮箱'), _t('填写邮箱地址')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('socialRss', null, '', _t('RSS'), _t('留空使用默认')));

    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('siteStartDate', null, '', _t('建站日期'), _t('格式：2024-01-01，运行时间自动计算；留空则不显示建站信息')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('faviconUrl', null, '', _t('Favicon'), _t('填写图标URL，留空使用默认')));

    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Select('enableOrb', array('1' => '开启', '0' => '关闭'), '1', _t('背景光晕'), _t('页面背景的动态光晕装饰')));

    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Select('showLinksCategory', array('1' => '显示', '0' => '隐藏'), '1', _t('友链分类'), _t('是否在友链页面显示分类筛选')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Select('enableLinkCheck', array('1' => '开启', '0' => '关闭'), '1', _t('友链检测'), _t('是否检测友情链接的在线状态')));

    /* 评论邮箱通知 comment-mail 模块配置 */
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Select('cmEnable', array('1' => '开启', '0' => '关闭'), '0', _t('评论邮件通知'), _t('需在 config.inc.php 引入主题 comment-mail/bootstrap.php 后开启')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('cmMail', null, '', _t('通知邮箱'), _t('留空则发送到管理员邮箱，多个用逗号分隔')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('cmSmtpHost', null, '', _t('SMTP服务器'), _t('留空使用服务器本机 mail() 发信，如 smtp.qq.com')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('cmSmtpPort', null, '465', _t('SMTP端口'), _t('SSL 通常为 465，TLS 通常为 587')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Select('cmSmtpSecure', array('ssl' => 'SSL', 'tls' => 'TLS', 'none' => '不加密'), 'ssl', _t('SMTP加密方式')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Text('cmSmtpUser', null, '', _t('SMTP账号'), _t('通常是完整邮箱地址')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Password('cmSmtpPass', null, '', _t('SMTP密码/授权码'), _t('QQ/163 等邮箱请填写授权码')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Textarea('customCSS', null, '', _t('自定义CSS')));
    $form->addInput(new \Typecho\Widget\Helper\Form\Element\Textarea('customJS', null, '', _t('自定义JS')));

    $tabsFile = dirname(__FILE__) . '/admin/config-tabs.html';
    if (file_exists($tabsFile)) {
        echo file_get_contents($tabsFile);
    }
}


function themeOption($key, $default = '') {
    $val = \Helper::options()->{$key};
    return ($val !== null && $val !== '') ? $val : $default;
}

/* 图片位来源：image=自定义图片 / post=指定文章 / cover=文章封面
   未保存过 heroSource 时按旧配置推断，保持原样 */
function heroSourceConfig() {
    $source = themeOption('heroSource', '');
    $val = trim(themeOption('heroImage', ''));
    if ($source === '') {
        $pool = trim(themeOption('heroPoolPostId', ''));
        if ($val === '' && $pool !== '') return array('post', $pool);
        return array('image', $val);
    }
    return array($source, $val);
}

function pageNavIcon($title) {
    $iconMap = array(
        '关于' => 'ri-user-line', 'about' => 'ri-user-line',
        '友链' => 'ri-link', '友情链接' => 'ri-link', 'link' => 'ri-link', 'links' => 'ri-link',
        '留言' => 'ri-chat-1-line', '留言板' => 'ri-chat-1-line', 'guestbook' => 'ri-chat-1-line',
        '相册' => 'ri-gallery-line', 'photo' => 'ri-gallery-line', 'gallery' => 'ri-gallery-line',
        '归档' => 'ri-archive-line', 'archive' => 'ri-archive-line',
        '友圈' => 'ri-group-line',
        '朋友圈' => 'ri-rss-line', '动态' => 'ri-rss-line',
        '标签' => 'ri-price-tag-3-line', 'tag' => 'ri-price-tag-3-line', 'tags' => 'ri-price-tag-3-line',
        '项目' => 'ri-code-s-slash-line', 'portfolio' => 'ri-code-s-slash-line',
        '日志' => 'ri-book-open-line', 'log' => 'ri-book-open-line',
        '音乐' => 'ri-music-2-line', 'music' => 'ri-music-2-line',
        '说说' => 'ri-chat-1-line', 'shuoshuo' => 'ri-chat-1-line',
        '书签' => 'ri-bookmark-line', 'bookmark' => 'ri-bookmark-line',
        '追剧' => 'ri-film-line', 'movie' => 'ri-film-line',
        '建站' => 'ri-hammer-line', '工具' => 'ri-tools-line',
    );
    foreach ($iconMap as $key => $ic) {
        if (mb_stripos($title, $key) !== false) return $ic;
    }
    return 'ri-file-text-line';
}

function themeCSSVars() {
    $accent = themeOption('accentColor', '#839ac9');
    $bg = themeOption('bgColor', '#cfcfcf');
    $blur = themeOption('glassBlur', '20px');
    $opacity = floatval(themeOption('glassOpacity', '0.45'));
    $sidebarW = intval(themeOption('sidebarWidth', '230'));
    if ($sidebarW < 160) $sidebarW = 230;
    $accentHover = adjustBrightness($accent, -20);
    $accentStrong = adjustBrightness($accent, -38);
    $accentStrongHover = adjustBrightness($accent, -48);
    $glassBg = hexToRGBA('#ffffff', $opacity);
    $glassBgStrong = hexToRGBA('#ffffff', min(1, $opacity + 0.17));
    $glassBgSolid = hexToRGBA('#ffffff', min(0.96, $opacity + 0.55));

    $radiusMap = array('small' => '8', 'medium' => '14', 'large' => '20');
    $radiusKey = themeOption('borderRadius', 'medium');
    $radius = isset($radiusMap[$radiusKey]) ? $radiusMap[$radiusKey] : '14';

    $topbarH = '90';

    echo "<style>:root{--accent:{$accent};--accent-hover:{$accentHover};--accent-strong:{$accentStrong};--accent-strong-hover:{$accentStrongHover};--accent-soft:" . hexToRGBA($accent, 0.1) . ";--accent-glow:" . hexToRGBA($accent, 0.25) . ";--bg:{$bg};--glass-blur:{$blur};--glass-bg:{$glassBg};--glass-bg-strong:{$glassBgStrong};--glass-bg-solid:{$glassBgSolid};--sidebar-w:{$sidebarW}px;--radius:{$radius}px;--radius-sm:" . max(6, $radius - 4) . "px;--radius-xs:" . max(4, $radius - 8) . "px;--topbar-h:{$topbarH}px}\n";

    if (themeOption('enableFont', '1') === '0') {
        echo "body{font-family:-apple-system,BlinkMacSystemFont,\"SF Pro Display\",\"PingFang SC\",\"Hiragino Sans GB\",\"Microsoft YaHei\",\"Helvetica Neue\",Arial,sans-serif}\n";
    }

    if (themeOption('enableOrb', '1') === '0') {
        echo ".orb-1,.orb-2,.orb-3{display:none!important}\n";
    }

    $customCSS = themeOption('customCSS', '');
    if ($customCSS) echo $customCSS . "\n";
    echo "</style>\n";
}

function adjustBrightness($hex, $steps) {
    $hex = ltrim($hex, '#');
    return sprintf('#%02x%02x%02x',
        max(0, min(255, hexdec(substr($hex, 0, 2)) + $steps)),
        max(0, min(255, hexdec(substr($hex, 2, 2)) + $steps)),
        max(0, min(255, hexdec(substr($hex, 4, 2)) + $steps))
    );
}

function hexToRGBA($hex, $alpha) {
    $hex = ltrim($hex, '#');
    return "rgba(" . hexdec(substr($hex, 0, 2)) . "," . hexdec(substr($hex, 2, 2)) . "," . hexdec(substr($hex, 4, 2)) . ",{$alpha})";
}

function getPostImages($content, $limit = 9) {
    $urls = array();
    if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/', $content, $m)) $urls = array_merge($urls, $m[1]);
    if (preg_match_all('/<img[^>]+data-src=["\']([^"\']+)["\']/', $content, $m)) $urls = array_merge($urls, $m[1]);
    if (preg_match_all('/\[img\]\s*(https?:\/\/[^\s\[\]]+?)\s*\[\/img\]/i', $content, $m)) $urls = array_merge($urls, $m[1]);
    if (preg_match_all('/!\[[^\]]*\]\(([^)]+)\)/', $content, $m)) $urls = array_merge($urls, $m[1]);
    if (preg_match_all('/<a[^>]+href=["\']([^"\']+?\.(?:jpg|jpeg|png|gif|webp|bmp))["\'][^>]*>/i', $content, $m)) $urls = array_merge($urls, $m[1]);
    if (preg_match_all('/https?:\/\/[^\s"\'<>\)]+\.(?:jpg|jpeg|png|gif|webp|bmp)/i', $content, $m)) $urls = array_merge($urls, $m[0]);
    if (preg_match_all('/\/usr\/uploads\/[^\s"\'<>\)]+/i', $content, $m)) $urls = array_merge($urls, $m[0]);
    $urls = array_filter($urls, function($u) {
        return strpos($u, 'data:') !== 0 && !preg_match('/spacer|blank|placeholder|pixel/i', $u);
    });
    $siteUrl = rtrim(\Helper::options()->siteUrl, '/');
    $urls = array_map(function($u) use ($siteUrl) {
        if (strpos($u, $siteUrl) === 0) return substr($u, strlen($siteUrl));
        return $u;
    }, $urls);
    $urls = array_values(array_unique($urls));
    return $limit > 0 ? array_slice($urls, 0, $limit) : $urls;
}
function getExcerpt($content, $length = 200) {
    $text = preg_replace('/\s+/', ' ', strip_tags($content));
    return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '...' : $text;
}

/**
 * 取远程 JSON（网易云音乐元信息用，失败静默返回 null）
 */
function widgetHttpJson($url) {
    if (!function_exists('curl_init')) return null;
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 3,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => array('Referer: https://music.163.com/', 'User-Agent: Mozilla/5.0'),
    ));
    $body = curl_exec($ch);
    curl_close($ch);
    if (!$body) return null;
    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}

/**
 * 网易云音乐元信息（标题/副标题/封面），带 7 天本地缓存
 */
function widgetMusicMeta($id, $type) {
    $id = preg_replace('/[^0-9]/', '', (string) $id);
    if ($id === '') return array();
    if (!in_array($type, array('song', 'playlist', 'album'), true)) $type = 'song';

    $root = defined('__TYPECHO_ROOT_DIR__') ? __TYPECHO_ROOT_DIR__ : dirname(__FILE__) . '/../..';
    $dir = $root . '/usr/uploads/.cache';
    $file = $dir . '/music-' . $type . '-' . $id . '.json';

    if (is_file($file) && time() - filemtime($file) < 604800) {
        $cached = json_decode(file_get_contents($file), true);
        if (is_array($cached) && !empty($cached['title']) && isset($cached['hot'])) return $cached;
    }

    $meta = array();
    if ($type === 'song') {
        $d = widgetHttpJson('https://music.163.com/api/song/detail/?id=' . $id . '&ids=%5B' . $id . '%5D');
        if (!empty($d['songs'][0])) {
            $s = $d['songs'][0];
            $artists = array();
            if (!empty($s['artists'])) foreach ($s['artists'] as $a) $artists[] = $a['name'];
            $meta = array(
                'title' => $s['name'],
                'sub'   => implode(' / ', $artists) . (!empty($s['album']['name']) ? ' · ' . $s['album']['name'] : ''),
                'cover' => !empty($s['album']['picUrl']) ? str_replace('http://', 'https://', $s['album']['picUrl']) : '',
            );
        }
    } elseif ($type === 'playlist') {
        $d = widgetHttpJson('https://music.163.com/api/playlist/detail?id=' . $id);
        if (!empty($d['result'])) {
            $p = $d['result'];
            $meta = array(
                'title' => $p['name'],
                'sub'   => (!empty($p['creator']['nickname']) ? $p['creator']['nickname'] . ' · ' : '') . (isset($p['trackCount']) ? $p['trackCount'] . ' 首' : '歌单'),
                'cover' => !empty($p['coverImgUrl']) ? str_replace('http://', 'https://', $p['coverImgUrl']) : '',
            );
        }
    } else {
        $d = widgetHttpJson('https://music.163.com/api/album/' . $id);
        if (!empty($d['album'])) {
            $a = $d['album'];
            $meta = array(
                'title' => $a['name'],
                'sub'   => (!empty($a['artist']['name']) ? $a['artist']['name'] . ' · ' : '') . '专辑',
                'cover' => !empty($a['picUrl']) ? str_replace('http://', 'https://', $a['picUrl']) : '',
            );
        }
    }

    if (!empty($meta['title'])) {
        $meta['hot'] = widgetMusicHotComment($id, $type);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents($file, json_encode($meta, JSON_UNESCAPED_UNICODE));
    }
    return $meta;
}

/**
 * 网易云音乐热门评论（取点赞最高的一条）
 */
function widgetMusicHotComment($id, $type) {
    $thread = $type === 'playlist' ? 'A_PL_0_' : ($type === 'album' ? 'R_AL_3_' : 'R_SO_4_');
    $d = widgetHttpJson('https://music.163.com/api/v1/resource/comments/' . $thread . $id . '?limit=1&offset=0');
    if (empty($d)) return array();
    $list = !empty($d['hotComments']) ? $d['hotComments'] : (!empty($d['comments']) ? $d['comments'] : array());
    if (empty($list[0])) return array();
    $c = $list[0];
    return array(
        'user'    => !empty($c['user']['nickname']) ? $c['user']['nickname'] : '网易云用户',
        'content' => !empty($c['content']) ? trim($c['content']) : '',
        'likes'   => isset($c['likedCount']) ? (int) $c['likedCount'] : 0,
    );
}

/**
 * [music id="歌曲/歌单/专辑 ID" type="song|playlist|album"]
 */
function widgetMusicCard($id, $type = 'song') {
    $id = preg_replace('/[^0-9]/', '', (string) $id);
    if ($id === '') return '';
    if (!in_array($type, array('song', 'playlist', 'album'), true)) $type = 'song';

    $typeName = array('song' => '单曲', 'playlist' => '歌单', 'album' => '专辑');
    $meta = widgetMusicMeta($id, $type);

    $title = !empty($meta['title']) ? $meta['title'] : '网易云音乐';
    $sub = !empty($meta['sub']) ? $meta['sub'] : $typeName[$type];
    $cover = !empty($meta['cover']) ? $meta['cover'] : '';
    $link = 'https://music.163.com/#/' . ($type === 'playlist' ? 'playlist' : ($type === 'album' ? 'album' : 'song')) . '?id=' . $id;
    $hot = !empty($meta['hot']) ? $meta['hot'] : array();

    $html = '<div class="post-music music-' . $type . '">';
    $html .= '<div class="post-music-main">';
    if ($cover !== '') {
        $html .= '<div class="post-music-cover"><img src="' . htmlspecialchars($cover, ENT_QUOTES, 'UTF-8') . '" alt="" loading="lazy"></div>';
    } else {
        $html .= '<div class="post-music-cover post-music-cover-empty"><i class="ri-music-2-line"></i></div>';
    }
    $html .= '<div class="post-music-info"><div class="post-music-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</div>';
    $html .= '<div class="post-music-sub"><span class="post-music-tag">' . $typeName[$type] . '</span>' . htmlspecialchars($sub, ENT_QUOTES, 'UTF-8') . '</div></div>';
    $html .= '<a class="post-music-link" href="' . $link . '" target="_blank" rel="noopener nofollow" aria-label="在网易云音乐打开" title="在网易云音乐打开"><i class="ri-link"></i></a>';
    $html .= '</div>';
    if (!empty($hot['content'])) {
        $html .= '<div class="post-music-hot">';
        $html .= '<div class="post-music-hot-head"><i class="ri-double-quotes-l"></i><span>热门评论</span>';
        if (!empty($hot['likes'])) {
            $html .= '<em><i class="ri-thumb-up-line"></i>' . number_format((int) $hot['likes']) . '</em>';
        }
        $html .= '</div>';
        $html .= '<p class="post-music-hot-text">' . htmlspecialchars($hot['content'], ENT_QUOTES, 'UTF-8') . '</p>';
        if (!empty($hot['user'])) {
            $html .= '<div class="post-music-hot-user">' . htmlspecialchars($hot['user'], ENT_QUOTES, 'UTF-8') . '</div>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * [post id="文章 ID"] 引用文章卡片
 */
function widgetPostCard($cid) {
    $cid = (int) $cid;
    if ($cid <= 0) return '';

    $db = \Typecho\Db::get();
    $row = null;
    try {
        $row = $db->fetchRow(
            $db->select('cid', 'title', 'slug', 'created', 'text', 'commentsNum')
                ->from('table.contents')
                ->where('cid = ?', $cid)
                ->where('type = ?', 'post')
                ->where('status = ?', 'publish')
                ->limit(1)
        );
    } catch (Exception $e) {
        $row = null;
    }
    if (!$row) return '';

    $url = \Typecho\Router::url('post', array('cid' => $row['cid'], 'slug' => $row['slug']), \Helper::options()->index);

    $plain = preg_replace('/!\[[^\]]*\]\([^)]*\)/', '', $row['text']);
    $plain = preg_replace('/\[[^\]]*\]\([^)]*\)/', '', $plain);
    /* 卡片摘要里不能漏出短代码和 HTML 标签 */
    $plain = preg_replace('~\[/?(?:tip|collapse|btn|photo|photos|music|post|dl)\b[^\]]*\]~i', ' ', $plain);
    $plain = strip_tags($plain);
    $excerpt = getExcerpt($plain, 64);

    $cover = '';
    $imgs = getPostImages($row['text'], 1);
    if (!empty($imgs)) $cover = heroThumb($imgs[0], 320);

    $catName = '';
    try {
        $cat = $db->fetchRow(
            $db->select('table.metas.name')
                ->from('table.metas')
                ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                ->where('table.relationships.cid = ?', $cid)
                ->where('table.metas.type = ?', 'category')
                ->limit(1)
        );
        if (!empty($cat['name'])) $catName = $cat['name'];
    } catch (Exception $e) {
    }

    $meta = date('Y-m-d', $row['created']);
    if ($catName !== '') $meta = $catName . ' · ' . $meta;

    $html = '<a class="post-ref" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
    $html .= '<div class="post-ref-body"><div class="post-ref-meta">' . htmlspecialchars($meta, ENT_QUOTES, 'UTF-8') . '</div>';
    $html .= '<div class="post-ref-title">' . htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') . '</div>';
    if ($excerpt !== '') $html .= '<div class="post-ref-ex">' . htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8') . '</div>';
    $html .= '</div>';
    if ($cover !== '') {
        $html .= '<div class="post-ref-thumb"><img src="' . htmlspecialchars($cover, ENT_QUOTES, 'UTF-8') . '" alt="" loading="lazy"></div>';
    }
    $html .= '</a>';
    return $html;
}

/**
 * [dl href="下载地址" name="文件名" size="12.4 MB" code="提取码"]
 * 也可写成 [dl href="下载地址"]文件名[/dl]，卡片整体可点击
 */
function widgetDownloadCard($attrs, $inner = '')
{
    $url = '';
    foreach (array('href', 'url', 'link') as $k) {
        if (!empty($attrs[$k])) { $url = trim($attrs[$k]); break; }
    }
    if ($url === '') return '';
    if (!preg_match('~^(?:https?:)?//|^/|^\./~i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }

    $name = '';
    foreach (array('name', 'title', 'file') as $k) {
        if (!empty($attrs[$k])) { $name = trim($attrs[$k]); break; }
    }
    if ($name === '') $name = trim(strip_tags($inner));
    if ($name === '') $name = '点击下载';

    $size = !empty($attrs['size']) ? trim($attrs['size']) : '';
    $code = !empty($attrs['code']) ? trim($attrs['code']) : '';
    $desc = !empty($attrs['desc']) ? trim($attrs['desc']) : '';

    $ext = '';
    if (preg_match('~\.([a-z0-9]{1,6})(?:$|[?#])~i', $name, $m)) $ext = strtoupper($m[1]);

    if (preg_match('~\.(zip|rar|7z|tar|gz|bz2)$~i', $name)) {
        $icon = 'ri-archive-line';
    } elseif (preg_match('~\.(pdf|doc|docx|xls|xlsx|ppt|pptx|txt|md)$~i', $name)) {
        $icon = 'ri-file-text-line';
    } elseif (preg_match('~\.(apk|exe|dmg|msi|ipa|deb|rpm)$~i', $name)) {
        $icon = 'ri-inbox-line';
    } elseif (preg_match('~\.(js|css|php|json|html|py|sh|yml|yaml)$~i', $name)) {
        $icon = 'ri-code-s-slash-line';
    } else {
        $icon = 'ri-download-line';
    }

    $meta = '';
    if ($size !== '') $meta .= '<span>' . htmlspecialchars($size, ENT_QUOTES, 'UTF-8') . '</span>';
    if ($ext !== '') $meta .= '<span>' . $ext . '</span>';
    if ($code !== '') $meta .= '<span class="post-dl-code">提取码 ' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</span>';

    $html  = '<a class="post-dl" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener nofollow">';
    $html .= '<span class="post-dl-icon"><i class="' . $icon . '"></i></span>';
    $html .= '<span class="post-dl-body">';
    $html .= '<span class="post-dl-name">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</span>';
    if ($meta !== '') $html .= '<span class="post-dl-meta">' . $meta . '</span>';
    if ($desc !== '') $html .= '<span class="post-dl-desc">' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '</span>';
    $html .= '</span>';
    $html .= '<span class="post-dl-btn"><i class="ri-download-line"></i>下载</span>';
    $html .= '</a>';
    return $html;
}

function humanTimeDiff($timestamp) {
    $diff = time() - $timestamp;
    if ($diff < 60) return '刚刚';
    if ($diff < 3600) return floor($diff / 60) . '分钟前';
    if ($diff < 86400) return floor($diff / 3600) . '小时前';
    if ($diff < 2592000) return floor($diff / 86400) . '天前';
    return date('Y-m-d', $timestamp);
}

function getPostViews($cid) {
    static $cache = array();
    if (isset($cache[$cid])) return $cache[$cid];
    $db = \Typecho\Db::get();
    $row = $db->fetchRow($db->select('str_value')->from('table.fields')->where('cid = ?', $cid)->where('name = ?', 'views'));
    $cache[$cid] = $row ? intval($row['str_value']) : 0;
    return $cache[$cid];
}

function incrementPostViews($cid) {
    $cookieKey = 'robes_views_' . $cid;
    if (isset($_COOKIE[$cookieKey])) return;
    $db = \Typecho\Db::get();
    $row = $db->fetchRow($db->select('str_value')->from('table.fields')->where('cid = ?', $cid)->where('name = ?', 'views'));
    $views = $row ? intval($row['str_value']) : 0;
    if ($row) {
        $db->query($db->update('table.fields')->rows(array('str_value' => $views + 1))->where('cid = ?', $cid)->where('name = ?', 'views'));
    } else {
        $db->query($db->insert('table.fields')->rows(array('cid' => $cid, 'name' => 'views', 'type' => 'str', 'str_value' => 1)));
    }
    /* 用前端脚本写 cookie：避免响应头带 Set-Cookie，否则 CDN 不会缓存 HTML */
    $GLOBALS['robes_view_cookie'] = $cookieKey;
}

function getBatchViews($cids) {
    if (empty($cids)) return array();
    $db = \Typecho\Db::get();
    $rows = $db->fetchAll(
        $db->select('cid', 'str_value')->from('table.fields')
            ->where('name = ?', 'views')
            ->where('cid IN ?', $cids)
    );
    $result = array();
    foreach ($rows as $row) $result[intval($row['cid'])] = intval($row['str_value']);
    foreach ($cids as $cid) if (!isset($result[$cid])) $result[$cid] = 0;
    return $result;
}
function themeSEO() {
    $options = \Helper::options();
    $request = \Typecho\Request::getInstance();
    $isPost = $request->is('post');
    $isPage = $request->is('page');

    $title = htmlspecialchars($options->title);
    $siteUrl = rtrim($options->siteUrl, '/');
    $description = htmlspecialchars($options->description ?: $title);

    if ($isPost || $isPage) {
        $db = \Typecho\Db::get();
        $cid = $request->get('cid');
        if ($cid) {
            $row = $db->fetchRow($db->select('title', 'text', 'created', 'modified')->from('table.contents')->where('cid = ?', intval($cid)));
            if ($row) {
                $title = htmlspecialchars($row['title']) . ' - ' . $title;
                $desc = getExcerpt($row['text'], 160);
                $description = htmlspecialchars($desc);
                $images = getPostImages($row['text'], 1);
                $image = !empty($images) ? $images[0] : '';
                $url = $siteUrl . $request->getRequestUrl();
                $published = date('c', $row['created']);
                $modified = date('c', $row['modified'] ?: $row['created']);

                echo '<meta property="og:type" content="article">' . "\n";
                echo '<meta property="og:title" content="' . $title . '">' . "\n";
                echo '<meta property="og:description" content="' . $description . '">' . "\n";
                echo '<meta property="og:url" content="' . $url . '">' . "\n";
                if ($image) echo '<meta property="og:image" content="' . htmlspecialchars($image) . '">' . "\n";
                echo '<meta property="article:published_time" content="' . $published . '">' . "\n";
                echo '<meta property="article:modified_time" content="' . $modified . '">' . "\n";
                echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
                echo '<meta name="twitter:title" content="' . $title . '">' . "\n";
                echo '<meta name="twitter:description" content="' . $description . '">' . "\n";
                if ($image) echo '<meta name="twitter:image" content="' . htmlspecialchars($image) . '">' . "\n";
                return;
            }
        }
    }

    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:title" content="' . $title . '">' . "\n";
    echo '<meta property="og:description" content="' . $description . '">' . "\n";
    echo '<meta property="og:url" content="' . $siteUrl . '/">' . "\n";
    echo '<meta name="twitter:card" content="summary">' . "\n";
    echo '<meta name="twitter:title" content="' . $title . '">' . "\n";
    echo '<meta name="twitter:description" content="' . $description . '">' . "\n";
}

function themeJSONLD() {
    $options = \Helper::options();
    $request = \Typecho\Request::getInstance();
    $siteUrl = rtrim($options->siteUrl, '/');
    $isPost = $request->is('post');

    if ($isPost) {
        $db = \Typecho\Db::get();
        $cid = $request->get('cid');
        if (!$cid) return;
        $row = $db->fetchRow($db->select('title', 'text', 'created', 'modified', 'author')->from('table.contents')->where('cid = ?', intval($cid)));
        if (!$row) return;
        $author = $db->fetchRow($db->select('screenName')->from('table.users')->where('uid = ?', intval($row['author'])));
        $images = getPostImages($row['text'], 1);
        $image = !empty($images) ? $images[0] : '';

        $ld = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $row['title'],
            'description' => getExcerpt($row['text'], 160),
            'datePublished' => date('c', $row['created']),
            'dateModified' => date('c', $row['modified'] ?: $row['created']),
            'mainEntityOfPage' => array('@type' => 'WebPage', '@id' => $siteUrl . $request->getRequestUrl()),
            'author' => array('@type' => 'Person', 'name' => $author ? $author['screenName'] : $options->title),
            'publisher' => array('@type' => 'Organization', 'name' => $options->title),
        );
        if ($image) $ld['image'] = $image;

        echo '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    } else {
        $ld = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $options->title,
            'url' => $siteUrl . '/',
            'description' => $options->description ?: $options->title,
        );
        echo '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }
}



function getWordCount($content) {
    $text = strip_tags($content);
    $text = preg_replace('/\s+/', '', $text);
    return mb_strlen($text);
}

function getAdjacentPost($cid, $direction) {
    $db = \Typecho\Db::get();
    if ($direction === 'prev') {
        $row = $db->fetchRow($db->select('cid', 'slug', 'title')->from('table.contents')->where('type = ?', 'post')->where('status = ?', 'publish')->where('cid < ?', intval($cid))->order('cid', \Typecho\Db::SORT_DESC)->limit(1));
    } else {
        $row = $db->fetchRow($db->select('cid', 'slug', 'title')->from('table.contents')->where('type = ?', 'post')->where('status = ?', 'publish')->where('cid > ?', intval($cid))->order('cid', \Typecho\Db::SORT_ASC)->limit(1));
    }
    return $row;
}

/**
 * 图片统一走缩略图：本地 usr/uploads 下的图片转成 img.php 预览地址
 * 已经是 img.php 地址、外链或文件不存在时原样返回
 */
function heroThumb($url, $w = 1000) {
    $url = trim((string) $url);
    if ($url === '') return $url;
    if (strpos($url, '/img.php?') !== false) {
        /* 缓存池里可能存着旧版本号，渲染时统一升级，避免继续命中旧的大图缓存 */
        return preg_replace('/&v=\d+/', '&v=6', $url);
    }

    $path = (string) parse_url($url, PHP_URL_PATH);
    if (strpos($path, '/usr/uploads/') !== 0) return $url;

    $root = defined('__TYPECHO_ROOT_DIR__') ? __TYPECHO_ROOT_DIR__ : dirname(__FILE__) . '/../..';
    if (!is_file($root . $path)) return $url;

    $themeUrl = '';
    if (class_exists('\\Widget\\Options')) {
        $themeUrl = rtrim(\Widget\Options::alloc()->themeUrl, '/');
    }
    if ($themeUrl === '') return $url;

    return $themeUrl . '/img.php?s=' . rawurlencode(ltrim($path, '/')) . '&w=' . (int) $w . '&v=6';
}

/* 生成 srcset（仅站内图片走 img.php 时才有意义；外链返回空串，保持原样） */
function thumbSrcset($url, $widths = array(320, 640, 1000)) {
    $probe = heroThumb($url, $widths[0]);
    if (strpos($probe, '/img.php?') === false) return '';
    $parts = array();
    foreach ($widths as $w) {
        $parts[] = htmlspecialchars(heroThumb($url, $w), ENT_QUOTES, 'UTF-8') . ' ' . (int) $w . 'w';
    }
    return implode(', ', $parts);
}

function getHeroImagePool($cid) {
    $cid = (int) $cid;
    if ($cid <= 0) return array();

    $db = \Typecho\Db::get();
    $row = null;
    try {
        $row = $db->fetchRow(
            $db->select('text', 'modified')->from('table.contents')
                ->where('cid = ?', $cid)
                ->where('status = ?', 'publish')
        );
    } catch (Exception $e) {
    }
    if (!$row) return array();

    /* 按文章最后修改时间缓存，后台保存后下一次访问即刷新 */
    $stamp = (int) $row['modified'];
    $cacheFile = sys_get_temp_dir() . '/robes-hero-pool-v3-' . $cid . '.json';
    if (is_file($cacheFile)) {
        $data = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($data) && isset($data['stamp'], $data['images']) && (int) $data['stamp'] === $stamp) {
            return (array) $data['images'];
        }
    }

    $root = defined('__TYPECHO_ROOT_DIR__') ? __TYPECHO_ROOT_DIR__ : dirname(__FILE__) . '/../..';
    $themeUrl = '';
    if (class_exists('\\Widget\\Options')) {
        $themeUrl = rtrim(\Widget\Options::alloc()->themeUrl, '/');
    }

    /* 只取本地 usr/uploads 下真实存在的图片，保证能走 img.php 生成预览图 */
    $paths = array();
    foreach (getPostImages((string) $row['text'], 0) as $u) {
        $u = strtok($u, '#');
        $path = (string) parse_url($u, PHP_URL_PATH);
        if (strpos($path, '/usr/uploads/') !== 0) continue;
        if (!preg_match('/\.(jpe?g|png|gif|webp|bmp|avif)$/i', $path)) continue;
        if (!is_file($root . $path)) continue;
        $paths[$path] = 1;
    }

    $out = array();
    foreach (array_keys($paths) as $path) {
        $out[] = $themeUrl !== ''
            ? $themeUrl . '/img.php?s=' . rawurlencode(ltrim($path, '/')) . '&w=1000&v=6'
            : $path;
    }

    @file_put_contents($cacheFile, json_encode(array('stamp' => $stamp, 'images' => $out)));
    return $out;
}

function getHeroRandomPosts() {
    $db = \Typecho\Db::get();
    try {
        $rows = $db->fetchAll(
            $db->select('table.contents.cid', 'table.contents.title', 'table.contents.slug', 'table.contents.created', 'table.contents.text', 'table.users.screenName')
                ->from('table.contents')
                ->join('table.users', 'table.contents.authorId = table.users.uid', 'LEFT')
                ->where('table.contents.type = ?', 'post')
                ->where('table.contents.status = ?', 'publish')
        );
    } catch (Exception $e) {
        $rows = array();
    }
    shuffle($rows);
    /* 首屏轮播控制在 8 篇以内，圆点与背景图数量适中 */
    $rows = array_slice($rows, 0, 8);

    $cats = array();
    if (!empty($rows)) {
        try {
            $rel = $db->fetchAll(
                $db->select('table.relationships.cid', 'table.metas.name')
                    ->from('table.relationships')
                    ->join('table.metas', 'table.relationships.mid = table.metas.mid')
                    ->where('table.metas.type = ?', 'category')
            );
            foreach ($rel as $x) {
                $rcid = intval($x['cid']);
                if (!isset($cats[$rcid])) $cats[$rcid] = $x['name'];
            }
        } catch (Exception $e) {
        }
    }

    $out = array();
    $heroThemeUrl = '';
    if (!empty($rows) && class_exists('\\Widget\\Options')) {
        $heroThemeUrl = rtrim(\Widget\Options::alloc()->themeUrl, '/');
    }
    foreach ($rows as $r) {
        $cid = intval($r['cid']);
        $plain = preg_replace('/\s+/u', '', strip_tags($r['text']));
        $chars = function_exists('mb_strlen') ? mb_strlen($plain, 'UTF-8') : strlen($plain);
        $imgs = getPostImages((string) $r['text'], 5);
        /* 只取真实图片：跳过视频/附件，跳过本地已不存在的文件 */
        $cover = '';
        foreach ($imgs as $u) {
            $u = strtok($u, '#');
            $pathOnly = (string) parse_url($u, PHP_URL_PATH);
            if (!preg_match('/\.(jpe?g|png|gif|webp|bmp|avif)$/i', $pathOnly)) continue;
            if (strpos($u, '/usr/uploads/') === 0 && !is_file(__TYPECHO_ROOT_DIR__ . $pathOnly)) continue;
            $cover = $u;
            break;
        }
        /* 本地封面走 img.php 生成预览图，避免首屏直接拉几 MB 原图 */
        if ($cover !== '' && $heroThemeUrl !== '' && strpos($cover, '/usr/uploads/') === 0) {
            $cover = $heroThemeUrl . '/img.php?s=' . rawurlencode(ltrim($cover, '/')) . '&w=1000&v=6';
        }
        $out[] = array(
            'cid'    => $cid,
            'cover'  => $cover,
            'title'  => $r['title'],
            'author' => isset($r['screenName']) && $r['screenName'] !== '' ? $r['screenName'] : '',
            'date'   => date('Y-m-d', intval($r['created'])),
            'cat'    => isset($cats[$cid]) ? $cats[$cid] : '',
            'read'   => max(1, (int) ceil($chars / 350)),
            'url'    => \Typecho\Router::url('post', array('cid' => $cid, 'slug' => $r['slug']), \Helper::options()->index),
        );
    }
    return $out;
}

function getRandomPosts($limit = 4) {
    $db = \Typecho\Db::get();
    return $db->fetchAll(
        $db->select('cid', 'title', 'slug', 'text', 'created')
            ->from('table.contents')
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish')
            ->order('RAND()')
            ->limit($limit)
    );
}



/* 让 CDN 缓存匿名访客的 HTML：浏览器不缓存（max-age=0），边缘缓存 5 分钟。
   登录用户 / POST / AJAX 一律不加，避免把后台态缓存出去。 */
function robes_cache_headers() {
    static $done = false;
    if ($done) return;
    $done = true;
    $req = \Typecho\Request::getInstance();
    if ($req->isPost() || $req->isAjax()) return;
    if (!empty($_COOKIE['__typecho_uid']) && !empty($_COOKIE['__typecho_authCode'])) return;
    if (!headers_sent()) {
        header('Cache-Control: public, max-age=0, s-maxage=300, stale-while-revalidate=60');
    }
}

function themeInit($form) {
    robes_cache_headers();
    $request = \Typecho\Request::getInstance();
    if ($request->isPost() && $request->get('likeup') && $request->get('action')) {
        likeup($request->get('likeup'), $request->get('action'));
    }
    if ($request->isPost() && $request->get('cmtup') && $request->get('action')) {
        cmtlikeup($request->get('cmtup'), $request->get('action'));
    }
    \Typecho\Plugin::factory('Widget_Abstract_Contents')->contentEx = array('themePhotoFilter', 'parsePhotos');
    if (class_exists('\\Typecho\\Widget\\Base\\Contents')) {
        \Typecho\Plugin::factory('Typecho_Widget_Base_Contents')->contentEx = array('themePhotoFilter', 'parsePhotos');
    }
}

/** 从 JPEG 的 APP1(Exif) 段读 Orientation；服务器没装 exif 扩展，所以手写解析 */
function robesJpegOrientation($file)
{
    $fp = @fopen($file, 'rb');
    if (!$fp) return 1;
    $head = (string) @fread($fp, 65536);
    @fclose($fp);
    if (strlen($head) < 12 || substr($head, 0, 2) !== "\xFF\xD8") return 1;
    $pos = 2;
    $len = strlen($head);
    while ($pos + 4 <= $len) {
        if (ord($head[$pos]) !== 0xFF) { $pos++; continue; }
        $marker = ord($head[$pos + 1]);
        $pos += 2;
        if ($marker === 0xD8 || $marker === 0x01 || ($marker >= 0xD0 && $marker <= 0xD7)) continue;
        if ($marker === 0xD9 || $marker === 0xDA) break;
        if ($pos + 2 > $len) break;
        $seg = unpack('n', substr($head, $pos, 2));
        $segLen = (int) $seg[1];
        if ($segLen < 2) break;
        if ($marker === 0xE1 && substr($head, $pos + 2, 6) === "Exif\0\0") {
            return robesTiffOrientation(substr($head, $pos + 8, $segLen - 8));
        }
        $pos += $segLen;
    }
    return 1;
}

function robesTiffOrientation($tiff)
{
    $len = strlen($tiff);
    if ($len < 8) return 1;
    $bo = substr($tiff, 0, 2);
    if ($bo === 'II') {
        $fmt = 'v';
        $long = 'V';
    } elseif ($bo === 'MM') {
        $fmt = 'n';
        $long = 'N';
    } else {
        return 1;
    }
    $magic = unpack($fmt, substr($tiff, 2, 2));
    if ((int) $magic[1] !== 42) return 1;
    $off = unpack($long, substr($tiff, 4, 4));
    $ifd = (int) $off[1];
    if ($ifd < 8 || $ifd + 2 > $len) return 1;
    $cnt = unpack($fmt, substr($tiff, $ifd, 2));
    $count = (int) $cnt[1];
    $p = $ifd + 2;
    for ($i = 0; $i < $count; $i++) {
        if ($p + 12 > $len) break;
        $tag = unpack($fmt, substr($tiff, $p, 2));
        if ((int) $tag[1] === 0x0112) {
            $val = unpack($fmt, substr($tiff, $p + 8, 2));
            $o = (int) $val[1];
            return ($o >= 1 && $o <= 8) ? $o : 1;
        }
        $p += 12;
    }
    return 1;
}

class themePhotoFilter {
    public static function parsePhotos($content, $widget, $last) {
        return self::processShortcodes($content);
    }

    public static function processShortcodes($content) {
        if (strpos($content, '[') !== false) {
            $content = preg_replace_callback('/\[photos\](.*?)\[\/photos\]/si', function($m) {
            $inner = strip_tags(trim($m[1]), '<img>');
            $urls = array();
            if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/', $inner, $im)) $urls = $im[1];
            if (empty($urls)) { preg_match_all('/https?:\/\/[^\s"\'<>]+\.(?:jpg|jpeg|png|gif|webp|svg|bmp)/i', $inner, $um); $urls = $um[0]; }
            if (empty($urls)) return $m[0];
            $h = '<div class="photo-grid g' . min(count($urls), 9) . '">';
            foreach ($urls as $u) $h .= '<div class="photo-item"><img src="' . htmlspecialchars($u) . '" alt="" loading="lazy"></div>';
            return $h . '</div>';
        }, $content);

        $content = preg_replace_callback('/\[photo\](.*?)\[\/photo\]/si', function($m) {
            $inner = trim($m[1]);
            if (empty($inner)) return '';
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $inner, $im)) return '<div class="photo-single"><img src="' . htmlspecialchars($im[1]) . '" alt="" loading="lazy"></div>';
            if (preg_match('/^https?:\/\/[^\s]+$/i', $inner)) return '<div class="photo-single"><img src="' . htmlspecialchars($inner) . '" alt="" loading="lazy"></div>';
            return '<div class="photo-single">' . $inner . '</div>';
        }, $content);

        $content = self::parseBlocks($content);
        }

        return self::rewriteImages(self::groupConsecutiveImages($content));
    }

    /**
     * 提示框 / 折叠 / 按钮 三种短代码
     * 语法：[tip]…[/tip]、[collapse title="标题"]…[/collapse]、[btn href="链接"]文字[/btn]
     */
    public static function parseBlocks($content) {
        /* 段落换行会留下首尾 <br>，块内会多出一行空白 */
        $clean = function ($html) {
            $html = preg_replace('~^(?:\s|(?:<br\s*/?>))+~i', '', $html);
            $html = preg_replace('~(?:\s|(?:<br\s*/?>))+$~i', '', $html);
            return trim($html);
        };
        /* 短代码里会出现中文引号，按 UTF-8 匹配；万一内容不是合法 UTF-8 就退回原文 */
        $run = function ($pattern, $callback, $subject) {
            $out = preg_replace_callback($pattern, $callback, $subject);
            return $out === null ? $subject : $out;
        };

        /* 网易云音乐卡片： [music id="歌曲ID" type="song|playlist|album"] */
        $content = $run('/\[music\s+id=["\'“”‘’]?([0-9]+)["\'“”‘’]?(?:\s+type=["\'“”‘’]?(song|playlist|album)["\'“”‘’]?)?[^\]]*\]/iu', function($m) {
            return widgetMusicCard($m[1], isset($m[2]) && $m[2] !== '' ? strtolower($m[2]) : 'song');
        }, $content);

        /* 引用文章卡片： [post id="文章ID"] */
        $content = $run('/\[post\s+id=["\'“”‘’]?([0-9]+)["\'“”‘’]?[^\]]*\]/iu', function($m) {
            return widgetPostCard($m[1]);
        }, $content);

        /* 下载卡片： [dl href="地址" name="文件名" size="12.4 MB" code="提取码"]，也可写成 [dl href="地址"]文件名[/dl] */
        $content = $run('/\[dl\b([^\]]*)\](?:\s*([\s\S]*?)\s*\[\/dl\])?/iu', function($m) use ($clean) {
            $attrs = array();
            if (preg_match_all('/([a-zA-Z-]+)\s*=\s*["\'“”‘’]([^"\'“”‘’]*)["\'“”‘’]/u', $m[1], $am, PREG_SET_ORDER)) {
                foreach ($am as $a) $attrs[strtolower($a[1])] = $a[2];
            }
            return widgetDownloadCard($attrs, isset($m[2]) ? $clean($m[2]) : '');
        }, $content);

        $content = $run('/\[collapse(?:\s+title=["\'“”‘’]([^"\'“”‘’]*)["\'“”‘’])?\s*\](.*?)\[\/collapse\]/siu', function($m) use ($clean) {
            $title = isset($m[1]) && trim($m[1]) !== '' ? trim($m[1]) : '点击展开';
            return '<details class="post-collapse"><summary class="post-collapse-head">'
                . '<span class="post-collapse-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</span>'
                . '<span class="post-collapse-arrow"></span>'
                . '</summary><div class="post-collapse-body">' . $clean($m[2]) . '</div></details>';
        }, $content);

        $content = $run('/\[tip\](.*?)\[\/tip\]/s', function($m) use ($clean) {
            return '<div class="post-tip"><i class="ri-lightbulb-line post-tip-icon"></i>'
                . '<div class="post-tip-body">' . $clean($m[1]) . '</div></div>';
        }, $content);

        $content = $run('/\[btn\s+(?:href|url)=["\'“”‘’]([^"\'“”‘’]*)["\'“”‘’](?:\s+(blank))?\s*\](.*?)\[\/btn\]/siu', function($m) use ($clean) {
            $url = trim($m[1]);
            $text = $clean(preg_replace('~</?p>~i', ' ', trim($m[3])));
            if ($text === '') $text = $url;
            $blank = !empty($m[2]) ? ' target="_blank" rel="noopener noreferrer"' : '';
            return '<a class="post-btn" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"' . $blank . '>' . $text . '</a>';
        }, $content);

        /* Markdown 会把整段块级短代码包进 <p>，块级标签不能待在段落里，去掉只包裹卡片的那层段落 */
        $content = preg_replace(
            '~<p>\s*((?:<(?:div|details)\b[^>]*class="(?:photo-single|photo-grid|post-tip|post-collapse|post-music)\b[^>]*>.*?</(?:div|details)>)+)\s*</p>~isu',
            '$1',
            $content
        );
        $content = preg_replace(
            '~<p>\s*(<a\b[^>]*class="(?:post-ref|post-dl)\b[^>]*>.*?</a>)\s*</p>~isu',
            '$1',
            $content
        );

        /* 短代码没能渲染（ID 失效、地址为空）时会留下空段落，清掉免得出现空行 */
        $content = preg_replace('~<p>\s*(?:<br\s*/?>\s*)*</p>~i', '', $content);

        return $content;
    }

    public static function groupConsecutiveImages($content) {
        $pattern = '/(?:\s*<p>\s*<img[^>]+>\s*<\/p>\s*|\s*<img[^>]+>\s*){2,}/si';
        return preg_replace_callback($pattern, function($m) {
            $block = $m[0];
            $urls = array();
            if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/', $block, $im)) $urls = $im[1];
            if (count($urls) < 2) return $block;
            $urls = array_values(array_unique($urls));
            $count = min(count($urls), 9);
            $h = '<div class="photo-grid g' . $count . '">';
            foreach (array_slice($urls, 0, 9) as $u) {
                $h .= '<div class="photo-item"><img src="' . htmlspecialchars($u) . '" alt="" loading="lazy"></div>';
            }
            return $h . '</div>';
        }, $content);
    }

    /**
     * 把文章内的本地图片换成体积更小的预览图
     * 原图地址写入 data-original（灯箱用），小图写入 data-thumb（灯箱缩略条用）
     */
    public static function rewriteImages($content)
    {
        if (strpos($content, '<img') === false) {
            return $content;
        }

        static $base = null, $site = null;
        if ($base === null) {
            $opts     = class_exists('\\Widget\\Options') ? \Widget\Options::alloc() : null;
            $themeUrl = $opts ? $opts->themeUrl : '/usr/themes/robes/';
            $site     = $opts ? rtrim($opts->siteUrl, '/') : '';
            $base     = rtrim($themeUrl, '/') . '/img.php';
        }

        return preg_replace_callback('/<img\b[^>]*>/i', function ($m) use ($base, $site) {
            $tag = $m[0];
            if (stripos($tag, 'data-original=') !== false) {
                return $tag;
            }
            if (!preg_match('/\bsrc\s*=\s*(["\'])([^"\']+)\1/i', $tag, $sm)) {
                return $tag;
            }

            $src  = html_entity_decode($sm[2], ENT_QUOTES, 'UTF-8');
            $path = $src;

            if ($site !== '' && strpos($path, $site . '/') === 0) {
                $path = substr($path, strlen($site));
            } elseif (preg_match('#^https?://#i', $path) || strpos($path, '//') === 0) {
                return $tag; // 外站图片保持原样
            }

            $path = strtok($path, '?');
            if (strpos($path, '/usr/uploads/') !== 0) {
                return $tag;
            }
            $rel = ltrim($path, '/');

            /* 占位尺寸：优先用 src 里的 #vwid/#vhei，其次读取本地图片真实宽高，
               让懒加载占位在图片到达前就有正确比例，避免高度塌陷后跳动 */
            $dims = null;
            if (preg_match('/[#&]vwid=(\d+)[&]?vhei=(\d+)/i', $src, $dm)) {
                $dims = array((int) $dm[1], (int) $dm[2]);
            } else {
                $file = __TYPECHO_ROOT_DIR__ . '/' . ltrim(strtok($path, '#'), '/');
                if (is_file($file)) {
                    $info = @getimagesize($file);
                    if ($info && $info[0] > 0 && $info[1] > 0) {
                        $dims = array((int) $info[0], (int) $info[1]);
                        /* 竖拍照片像素是横着存的，真实方向写在 EXIF 里：
                           5~8 需要把占位宽高对调，否则 height:auto 会按错比例撑开 */
                        if (isset($info[2]) && $info[2] === IMAGETYPE_JPEG) {
                            $oriVal = robesJpegOrientation($file);
                            if ($oriVal >= 5 && $oriVal <= 8) {
                                $dims = array($dims[1], $dims[0]);
                            }
                        }
                    }
                }
            }

            $thumb = $base . '?s=' . rawurlencode($rel) . '&w=1000&v=6';
            $small = $base . '?s=' . rawurlencode($rel) . '&w=320&v=6';
            /* 响应式：正文宽 760px，手机上按视口宽度取 480；原图足够大时补一张 1600 */
            $mid = $base . '?s=' . rawurlencode($rel) . '&w=480&v=6';
            $big = $base . '?s=' . rawurlencode($rel) . '&w=1600&v=6';
            $srcset = htmlspecialchars($mid, ENT_QUOTES, 'UTF-8') . ' 480w, '
                . htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8') . ' 1000w';
            if ($dims && $dims[0] >= 1600) {
                $srcset .= ', ' . htmlspecialchars($big, ENT_QUOTES, 'UTF-8') . ' 1600w';
            }
            $sizes = '(max-width: 640px) 100vw, (max-width: 920px) 92vw, 760px';

            $new = preg_replace(
                '/\bsrc\s*=\s*(["\'])[^"\']*\1/i',
                'src="' . htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8') . '"',
                $tag,
                1
            );
            $new = preg_replace(
                '/<img\b/i',
                '<img data-original="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8')
                    . '" data-thumb="' . htmlspecialchars($small, ENT_QUOTES, 'UTF-8')
                    . '" srcset="' . $srcset . '" sizes="' . $sizes
                    . '" loading="lazy" decoding="async"',
                $new,
                1
            );

            if ($dims && !preg_match('/\bwidth\s*=/i', $new)) {
                $new = preg_replace(
                    '/\s*(\/?)>$/',
                    ' width="' . $dims[0] . '" height="' . $dims[1] . '"${1}>',
                    $new
                );
            }

            return $new;
        }, $content);
    }
}

function likeup($cid, $action) {
    $cid = intval($cid);
    if ($cid <= 0) return;
    $db = \Typecho\Db::get();
    $prefix = $db->getPrefix();
    try {
        $row = $db->fetchRow($db->select('likes')->from('table.contents')->where('cid = ?', $cid));
    } catch (Exception $e) {
        $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `likes` INT(10) DEFAULT 0');
        $row = array('likes' => 0);
    }
    $likes = isset($row['likes']) ? intval($row['likes']) : 0;
    if ($action === 'do') {
        $likes++;
        $db->query($db->update('table.contents')->rows(array('likes' => $likes))->where('cid = ?', $cid));
        setcookie('like_' . $cid, '1', time() + 31536000, '/');
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('likes' => $likes));
    exit;
}

/**
 * 评论点赞：同一浏览器按 cookie 去重
 */
function cmtlikeup($coid, $action) {
    $coid = intval($coid);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    if ($coid <= 0) {
        echo json_encode(array('ok' => false, 'likes' => 0, 'message' => 'bad coid'));
        exit;
    }
    $db = \Typecho\Db::get();
    try {
        $row = $db->fetchRow($db->select('coid', 'likes', 'status')->from('table.comments')->where('coid = ?', $coid)->limit(1));
    } catch (\Exception $e) {
        try {
            $db->query('ALTER TABLE ' . $db->getPrefix() . 'comments ADD COLUMN likes INT(11) DEFAULT 0');
            $row = $db->fetchRow($db->select('coid', 'likes', 'status')->from('table.comments')->where('coid = ?', $coid)->limit(1));
        } catch (\Exception $e2) {
            echo json_encode(array('ok' => false, 'likes' => 0, 'message' => '点赞字段初始化失败，请手动为评论表添加 likes 字段'));
            exit;
        }
    }
    if (!$row || $row['status'] !== 'approved') {
        echo json_encode(array('ok' => false, 'likes' => 0, 'message' => '评论不存在'));
        exit;
    }
    $likes = intval($row['likes']);
    $key = 'cmt_like_' . $coid;
    $liked = !empty($_COOKIE[$key]);

    if ($action === 'do' && !$liked) {
        $likes++;
        $db->query($db->update('table.comments')->rows(array('likes' => $likes))->where('coid = ?', $coid));
        setcookie($key, '1', time() + 31536000, '/');
        $liked = true;
    } elseif ($action === 'undo' && $liked) {
        if ($likes > 0) {
            $likes--;
            $db->query($db->update('table.comments')->rows(array('likes' => $likes))->where('coid = ?', $coid));
        }
        setcookie($key, '', time() - 3600, '/');
        $liked = false;
    }

    echo json_encode(array('ok' => true, 'likes' => $likes, 'liked' => $liked));
    exit;
}

/** 站长状态预设图标：Remix Icon 官方线性图标（fill: currentColor） */
function robesMoodIconMap() {
    static $map = null;
    if ($map === null) {
        $map = array(
            '🐟' => '<path d="M17 4C20.3137 4 23 6.68629 23 10V14C23 17.3137 20.3137 20 17 20H7C3.68629 20 1 17.3137 1 14V10C1 6.68629 3.68629 4 7 4H17ZM17 6H7C4.8578 6 3.10892 7.68397 3.0049 9.80036L3 10V14C3 16.1422 4.68397 17.8911 6.80036 17.9951L7 18H17C19.1422 18 20.8911 16.316 20.9951 14.1996L21 14V10C21 7.8578 19.316 6.10892 17.1996 6.0049L17 6ZM10 9V11H12V13H9.999L10 15H8L7.999 13H6V11H8V9H10ZM18 13V15H16V13H18ZM16 9V11H14V9H16Z"/>',
            '☕' => '<path d="M16 13V5H6V13C6 14.1046 6.89543 15 8 15H14C15.1046 15 16 14.1046 16 13ZM5 3H20C21.1046 3 22 3.89543 22 5V8C22 9.10457 21.1046 10 20 10H18V13C18 15.2091 16.2091 17 14 17H8C5.79086 17 4 15.2091 4 13V4C4 3.44772 4.44772 3 5 3ZM18 5V8H20V5H18ZM2 19H20V21H2V19Z"/>',
            '💻' => '<path d="M4 5V16H20V5H4ZM3 3H21C21.5523 3 22 3.44772 22 4V17C22 17.5523 21.5523 18 21 18H16V21H18V23H6V21H8V18H3C2.44772 18 2 17.5523 2 17V4C2 3.44772 2.44772 3 3 3ZM14 18H10V21H14V18Z"/>',
            '🎧' => '<path d="M12 4C7.58172 4 4 7.58172 4 12H7C8.10457 12 9 12.8954 9 14V19C9 20.1046 8.10457 21 7 21H4C2.89543 21 2 20.1046 2 19V12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12V19C22 20.1046 21.1046 21 20 21H17C15.8954 21 15 20.1046 15 19V14C15 12.8954 15.8954 12 17 12H20C20 7.58172 16.4183 4 12 4ZM4 14V19H7V14H4ZM17 14V19H20V14H17Z"/>',
            '🌙' => '<path d="M10 7C10 10.866 13.134 14 17 14C18.9584 14 20.729 13.1957 21.9995 11.8995C22 11.933 22 11.9665 22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C12.0335 2 12.067 2 12.1005 2.00049C10.8043 3.27098 10 5.04157 10 7ZM4 12C4 16.4183 7.58172 20 12 20C15.0583 20 17.7158 18.2839 19.062 15.7621C18.3945 15.9187 17.7035 16 17 16C12.0294 16 8 11.9706 8 7C8 6.29648 8.08133 5.60547 8.2379 4.938C5.71611 6.28423 4 8.9417 4 12Z"/>',
            '🚶' => '<path d="M7.61713 8.71233L10.8222 6.38373C11.174 6.12735 11.6087 5.98543 12.065 6.0008C13.1764 6.02813 14.1524 6.75668 14.4919 7.82036C14.6782 8.40431 14.8481 8.79836 15.0017 9.0025C15.914 10.2155 17.3655 11 19.0002 11V13C16.8255 13 14.8825 12.0083 13.5986 10.4526L12.901 14.4085L14.9621 16.138L17.1853 22.246L15.3059 22.93L13.266 17.3256L9.87576 14.4808C9.32821 14.0382 9.03139 13.3192 9.16231 12.5767L9.67091 9.6923L8.99407 10.1841L6.86706 13.1116L5.24902 11.9361L7.60016 8.7L7.61713 8.71233ZM13.5002 5.5C12.3956 5.5 11.5002 4.60457 11.5002 3.5C11.5002 2.39543 12.3956 1.5 13.5002 1.5C14.6047 1.5 15.5002 2.39543 15.5002 3.5C15.5002 4.60457 14.6047 5.5 13.5002 5.5ZM10.5286 18.6813L7.31465 22.5116L5.78257 21.226L8.75774 17.6803L9.50426 15.5L11.2954 17L10.5286 18.6813Z"/>',
        );
    }
    return $map;
}

/** 取某个状态对应的图标 SVG，未收录时返回空串，由调用方退回 emoji */
function robesMoodIcon($emoji) {
    $map = robesMoodIconMap();
    if (!isset($map[$emoji])) return '';
    return '<svg class="mood-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $map[$emoji] . '</svg>';
}

