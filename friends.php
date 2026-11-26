<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 朋友圈
 *
 * @package custom
 * @type page
 * @title 朋友圈
 */
$this->need('header.php');

$friends = array();
$db = \Typecho\Db::get();
$prefix = $db->getPrefix();
try {
    $friends = $db->fetchAll($db->select()->from($prefix . 'links'));
} catch (Exception $e) {
    try {
        $friends = $db->fetchAll($db->select()->from('table.links'));
    } catch (Exception $e2) {
        $friends = array();
    }
}
$friends = array_values(array_filter($friends, function($f) {
    if (empty($f['url'])) return false;
    /* 「已失效」分类的友链不再参与动态抓取 */
    $sort = isset($f['sort']) ? trim((string) $f['sort']) : '';
    if ($sort !== '' && mb_strpos($sort, '已失效') !== false) return false;
    return true;
}));

function _friends_fetchUrl($url, $timeout = 6) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; GlassBlog/1.0)',
        ));
        $data = curl_exec($ch);
        curl_close($ch);
        return $data ? $data : '';
    }
    if (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(array(
            'http' => array('timeout' => $timeout, 'user_agent' => 'Mozilla/5.0'),
            'ssl'  => array('verify_peer' => false, 'verify_peer_name' => false),
        ));
        $data = @file_get_contents($url, false, $ctx);
        return $data ? $data : '';
    }
    return '';
}

/**
 * 并发抓取一批 URL（curl_multi）。串行抓取时，一个超时站点就会拖住整页，
 * 这里把所有站点合并成一批，整体耗时约等于最慢的那一个。
 */
function _friends_multiFetch($urls, $timeout = 5, $deadline = 0.0) {
    $urls = array_values(array_unique(array_filter($urls)));
    if (!$urls) return array();
    $out = array();

    if (function_exists('curl_multi_init') && function_exists('curl_init')) {
        $mh = curl_multi_init();
        $chs = array();
        foreach ($urls as $u) {
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => $u, CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3,
                CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; GlassBlog/1.0)',
            ));
            curl_multi_add_handle($mh, $ch);
            $chs[$u] = $ch;
        }
        /* 整体预算封顶，避免个别站点超时拖长整页 */
        $hardStop = microtime(true) + $timeout + 1;
        if ($deadline > 0 && $deadline < $hardStop) $hardStop = $deadline;
        do {
            $st = curl_multi_exec($mh, $active);
            if (!$active) break;
            if (microtime(true) > $hardStop) break;
            curl_multi_select($mh, 0.2);
        } while (true);
        foreach ($chs as $u => $ch) {
            $body = curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            if (is_string($body) && $body !== '') {
                $out[$u] = $body;
            } elseif ($deadline <= 0) {
                /* 个别站点在 multi 里会空响应，后台刷新时单独补抓一次 */
                $one = _friends_fetchUrl($u, $timeout);
                if ($one !== '') $out[$u] = $one;
            }
        }
        curl_multi_close($mh);
        return $out;
    }

    foreach ($urls as $u) {
        $body = _friends_fetchUrl($u, $timeout);
        if ($body) $out[$u] = $body;
    }
    return $out;
}

function _friends_cacheDir() {
    $dir = __TYPECHO_ROOT_DIR__ . '/usr/cache/friends';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir;
}

function _friends_feedPaths() {
    return array('/feed', '/feed.xml', '/rss', '/rss.xml', '/atom.xml', '/index.xml');
}

function _friends_feedLooksValid($body) {
    return $body && (stripos($body, '<rss') !== false || stripos($body, '<feed') !== false || stripos($body, '<channel') !== false);
}

/* 订阅地址缓存：7 天内有效；空字符串表示「探测过但没有订阅地址」 */
function _friends_readFeedCache($siteUrl) {
    $file = _friends_cacheDir() . '/rss_' . md5($siteUrl) . '.txt';
    if (!file_exists($file)) return null;
    $raw = trim((string) file_get_contents($file));
    /* 探测失败只保留 1 小时：首次访问超时截断不应把站点锁死一周 */
    $ttl = $raw === '' ? 3600 : 604800;
    if (time() - filemtime($file) > $ttl) return null;
    return $raw;
}

function _friends_writeFeedCache($siteUrl, $feedUrl) {
    @file_put_contents(_friends_cacheDir() . '/rss_' . md5($siteUrl) . '.txt', $feedUrl);
}

function _friends_postsCacheFile($siteUrl) {
    return _friends_cacheDir() . '/posts_' . md5($siteUrl) . '.json';
}

/* 每 2 小时刷新一次，但按域名错峰，避免所有友链同时过期、整页一起变慢 */
function _friends_postsTtl($siteUrl) {
    return 7200 + (crc32($siteUrl) % 2400);
}

function _friends_parseFeed($body, $limit = 3) {
    libxml_use_internal_errors(true);
    $xml = @simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
    libxml_clear_errors();
    if (!$xml) return null;
    $posts = array();
    if (isset($xml->channel->item)) {
        foreach ($xml->channel->item as $item) {
            $desc = isset($item->description) ? (string) $item->description : '';
            $posts[] = array('title' => (string) $item->title, 'link' => (string) $item->link, 'desc' => mb_substr(strip_tags($desc), 0, 100), 'time' => (string) $item->pubDate);
            if (count($posts) >= $limit) break;
        }
    } elseif (isset($xml->entry)) {
        foreach ($xml->entry as $item) {
            $link = isset($item->link['href']) ? (string) $item->link['href'] : '';
            $content = isset($item->summary) ? (string) $item->summary : (isset($item->content) ? (string) $item->content : '');
            $time = isset($item->published) ? (string) $item->published : (isset($item->updated) ? (string) $item->updated : '');
            $posts[] = array('title' => (string) $item->title, 'link' => $link, 'desc' => mb_substr(strip_tags($content), 0, 100), 'time' => $time);
            if (count($posts) >= $limit) break;
        }
    }
    return $posts;
}

/**
 * 刷新一批友链的订阅缓存，写入缓存文件并返回 url => posts
 * $budgetMs > 0 时限制总耗时（毫秒），用于首屏同步兜底
 */
function _friends_refresh($pending, $budgetMs = 0) {
    if (!$pending) return array();
    $deadline = $budgetMs > 0 ? microtime(true) + $budgetMs / 1000 : 0.0;

    $feedMap = array();
    $needDetect = array();
    foreach ($pending as $p) {
        $known = _friends_readFeedCache($p['url']);
        if ($known === null) $needDetect[] = $p['url'];
        else $feedMap[$p['url']] = $known;
    }

    if ($needDetect) {
        $candidates = array();
        foreach ($needDetect as $site) {
            foreach (_friends_feedPaths() as $path) $candidates[] = rtrim($site, '/') . $path;
        }
        $probeTimeout = 5;
        if ($deadline > 0) $probeTimeout = max(1, min(5, (int) ceil($deadline - microtime(true))));
        $probes = _friends_multiFetch($candidates, $probeTimeout, $deadline);
        foreach ($needDetect as $site) {
            $found = '';
            foreach (_friends_feedPaths() as $path) {
                $tu = rtrim($site, '/') . $path;
                if (isset($probes[$tu]) && _friends_feedLooksValid($probes[$tu])) { $found = $tu; break; }
            }
            /* 首屏有时间预算，可能只是被截断，先不落空缓存，交给后台刷新重试 */
            if ($found !== '' || $deadline <= 0) _friends_writeFeedCache($site, $found);
            $feedMap[$site] = $found;
        }
    }

    $feedUrls = array();
    foreach ($pending as $p) {
        if (!empty($feedMap[$p['url']])) $feedUrls[] = $feedMap[$p['url']];
    }
    $fetchTimeout = 8;
    if ($deadline > 0) $fetchTimeout = max(1, min(8, (int) ceil($deadline - microtime(true))));
    $bodies = $feedUrls ? _friends_multiFetch($feedUrls, $fetchTimeout, $deadline) : array();

    $done = array();
    foreach ($pending as $p) {
        $feedUrl = isset($feedMap[$p['url']]) ? $feedMap[$p['url']] : '';
        if ($feedUrl && !empty($bodies[$feedUrl])) {
            $parsed = _friends_parseFeed($bodies[$feedUrl], 3);
            if ($parsed !== null) {
                @file_put_contents($p['cacheFile'], json_encode($parsed));
                $done[$p['url']] = $parsed;
                continue;
            }
        }
        if ($p['stale'] !== null) {
            /* 抓取失败保留旧数据，推迟到下一个 TTL 再试，避免每次访问都重试死站 */
            if (file_exists($p['cacheFile'])) @touch($p['cacheFile']);
        } else {
            /* 从没成功过的站点也落一个空缓存，避免首屏反复同步等待 */
            @file_put_contents($p['cacheFile'], '[]');
        }
    }
    return $done;
}

$feedItems = array();
$pending = array();
$now = time();

foreach ($friends as $f) {
    $url = $f['url'] ?? '';
    if (!$url) continue;
    $item = array(
        'name'   => $f['name'] ?? $f['title'] ?? '好友',
        'url'    => $url,
        'avatar' => $f['image'] ?? $f['logo'] ?? '',
        'desc'   => $f['description'] ?? $f['desc'] ?? '',
        'posts'  => array(),
    );

    $cacheFile = _friends_postsCacheFile($url);
    $stale = null;
    if (file_exists($cacheFile)) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            /* 有缓存就先用旧内容渲染，过期也只标记待刷新，不阻塞首屏 */
            $item['posts'] = $cached;
            $feedItems[] = $item;
            /* 空缓存（抓取失败/暂无文章）半小时后就重试，正常内容才用长 TTL */
            $ttl = empty($cached) ? 1800 : _friends_postsTtl($url);
            if ($now - filemtime($cacheFile) >= $ttl) {
                $pending[] = array('url' => $url, 'cacheFile' => $cacheFile, 'stale' => $cached);
            }
            continue;
        }
    }
    $feedItems[] = $item;
    $pending[] = array('url' => $url, 'cacheFile' => $cacheFile, 'stale' => null);
}

if ($pending) {
    $allCold = true;
    foreach ($pending as $p) { if ($p['stale'] !== null) { $allCold = false; break; } }

    /* 完全没有缓存（首次访问）：同步抓一小会儿，别让页面空着 */
    if ($allCold) {
        $done = _friends_refresh($pending, 3000);
        foreach ($feedItems as $i => $it) {
            if (isset($done[$it['url']])) $feedItems[$i]['posts'] = $done[$it['url']];
        }
        $left = array();
        foreach ($pending as $p) { if (!isset($done[$p['url']])) $left[] = $p; }
        $pending = $left;
    }

    /* 其余过期项：页面先吐给浏览器，再在后台刷新缓存 */
    if ($pending) {
        $GLOBALS['__friends_pending'] = $pending;
        if (function_exists('fastcgi_finish_request')) {
            register_shutdown_function(function() {
                if (empty($GLOBALS['__friends_pending'])) return;
                @set_time_limit(0);
                @ignore_user_abort(true);
                @fastcgi_finish_request();
                _friends_refresh($GLOBALS['__friends_pending'], 0);
            });
        } else {
            /* 非 php-fpm 环境没有后台刷新能力，退回同步刷新 */
            _friends_refresh($pending, 5000);
        }
    }
}

usort($feedItems, function($a, $b) {
    $ta = !empty($a['posts']) ? strtotime($a['posts'][0]['time']) : 0;
    $tb = !empty($b['posts']) ? strtotime($b['posts'][0]['time']) : 0;
    if ($ta === false) $ta = 0;
    if ($tb === false) $tb = 0;
    return $tb - $ta;
});
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

                    <div class="friends-feed">
                        <?php if (empty($friends)): ?>
                            <div class="friends-empty">
                                <i class="ri-link-unlink" style="font-size:32px;margin-bottom:12px;display:block;color:var(--text-light);"></i>
                                <p>暂无友情链接</p>
                                <p style="font-size:12px;color:var(--text-light);margin-top:4px;">请在后台「友情链接」插件中添加好友链接</p>
                            </div>
                        <?php elseif (empty($feedItems)): ?>
                            <div class="friends-empty">
                                <i class="ri-rss-line" style="font-size:32px;margin-bottom:12px;display:block;color:var(--text-light);"></i>
                                <p>暂无好友动态</p>
                                <p style="font-size:12px;color:var(--text-light);margin-top:4px;">已收录 <?php echo count($friends); ?> 位好友，暂时没有获取到最新文章</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($feedItems as $friend): ?>
                                <div class="friend-feed-card">
                                    <div class="friend-feed-header">
                                        <div class="friend-feed-avatar">
                                            <?php if ($friend['avatar']): ?>
                                                <img src="<?php echo htmlspecialchars($friend['avatar']); ?>" class="friend-feed-avatar-img" loading="lazy" onerror="this.style.display='none'">
                                            <?php else: ?>
                                                <div class="friend-feed-avatar-text"><?php echo mb_substr($friend['name'], 0, 1); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="friend-feed-info">
                                            <span class="friend-feed-name"><?php echo htmlspecialchars($friend['name']); ?></span>
                                            <?php if ($friend['desc']): ?>
                                                <div class="friend-feed-desc"><?php echo mb_substr($friend['desc'], 0, 40); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <a class="friend-feed-visit" href="<?php echo htmlspecialchars($friend['url']); ?>" target="_blank" rel="noopener noreferrer" title="访问站点" aria-label="访问站点">
                                            <i class="ri-arrow-right-line"></i>
                                        </a>
                                    </div>

                                    <?php if (!empty($friend['posts'])): ?>
                                        <div class="friend-feed-posts">
                                            <?php foreach ($friend['posts'] as $j => $p): ?>
                                                <a class="friend-feed-item" href="<?php echo htmlspecialchars($p['link']); ?>" target="_blank" rel="noopener noreferrer">
                                                    <div class="friend-feed-item-head">
                                                        <span class="friend-feed-item-title"><?php echo htmlspecialchars($p['title']); ?></span>
                                                        <?php if (!empty($p['time'])): ?>
                                                            <?php $d = strtotime($p['time']); ?>
                                                            <?php if ($d): ?>
                                                                <span class="friend-feed-item-date"><?php echo date('n/j', $d); ?></span>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!empty($p['desc'])): ?>
                                                        <span class="friend-feed-item-desc"><?php echo htmlspecialchars($p['desc']); ?></span>
                                                    <?php endif; ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="friend-feed-no-posts">暂无文章</div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>
            </div>
        </div>
        <div class="fade-bottom" id="fadeBottom"></div>
    </div>

<?php $this->need('footer.php'); ?>
