<?php
/**
 * Robes 私密评论 - 主题内实现（不修改 Typecho 核心文件）
 *
 * 任何访客或登录用户勾选“私密”后：
 *   - 站长（administrator）始终可见完整内容
 *   - 评论作者本人可见完整内容（登录用户按 authorId，游客按签名 Cookie）
 *   - 其他访问者只能看到马赛克占位，内容不会输出到页面
 *
 * 由 comment-mail/bootstrap.php 加载，并随邮箱提醒注册的
 * Widget_Feedback:finishComment 钩子一起执行。
 * 私密标记写入 comments.private 字段，前台由 comments.php 按视角遮挡。
 */
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class RobesPrivateComments
{
    /** 记录游客私密评论归属的 Cookie 名（不含前缀） */
    const COOKIE = 'robes_priv';

    /** 记录游客全部评论归属的 Cookie 名（用于「我的评论」与通知） */
    const MINE_COOKIE = 'robes_mine';

    /** 单个浏览器最多记住的评论条数 */
    const COOKIE_MAX = 120;

    /** 当前浏览器已确认归属的评论缓存 */
    private static $owned = null;

    /**
     * 确保 comments 表存在 private 字段（全新安装时自动补列，无需改核心或手动执行 SQL）
     */
    public static function ensureColumn()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            $db = \Typecho\Db::get();
            $table = $db->getPrefix() . 'comments';
            $cols = $db->fetchAll("SHOW COLUMNS FROM `{$table}` LIKE 'private'");
            if (empty($cols)) {
                $db->query("ALTER TABLE `{$table}` ADD COLUMN `private` TINYINT(1) NOT NULL DEFAULT 0");
            }
        } catch (\Throwable $e) {
        }
    }

    /** 签名密钥（优先使用 Typecho 安装时生成的 secret） */
    public static function secret()
    {
        try {
            $options = \Widget\Options::alloc();
            $secret = (string) $options->secret;
            return $secret !== '' ? $secret : md5((string) $options->siteUrl);
        } catch (\Throwable $e) {
            return 'robes-private-comments';
        }
    }

    /** 单条评论的归属签名 */
    public static function token($coid)
    {
        return substr(hash_hmac('sha256', 'robes-private|' . (int) $coid, self::secret()), 0, 16);
    }

    /** 当前浏览器是否为该私密评论的作者（游客） */
    public static function owns($coid)
    {
        if (self::$owned === null) {
            self::$owned = self::parseAll(self::COOKIE);
        }

        $coid = (int) $coid;
        return isset(self::$owned[$coid]) && hash_equals(self::token($coid), self::$owned[$coid]);
    }

    /** 游客「我的评论」签名（与私密评论分开的盐，避免两类 Cookie 互串） */
    public static function mineToken($coid)
    {
        return substr(hash_hmac('sha256', 'robes-mine|' . (int) $coid, self::secret()), 0, 16);
    }

    /** 当前浏览器发过的评论 id（已验签，最多 COOKIE_MAX 条） */
    public static function mineIds()
    {
        $ids = array();
        foreach (self::parseAll(self::MINE_COOKIE) as $coid => $mac) {
            if ($coid > 0 && hash_equals(self::mineToken($coid), $mac)) {
                $ids[] = (int) $coid;
            }
        }
        return $ids;
    }

    /** 当前浏览器在站点上有评论记录（游客身份） */
    public static function hasMine()
    {
        return (bool) self::mineIds();
    }

    /** 游客通知状态的隔离 key（同一个浏览器稳定不变） */
    public static function guestKey()
    {
        /* 用合并后的规范串，避免浏览器里同时存在多个同名后缀 Cookie 时
           取到哪一个不同、key 就跟着变，导致未读状态反复重置 */
        $map = self::parseAll(self::MINE_COOKIE);
        if (!$map) {
            return '';
        }
        ksort($map);
        $raw = '';
        foreach ($map as $id => $mac) {
            $raw .= $id . '.' . $mac . '|';
        }
        return 'g' . substr(md5($raw . self::secret()), 0, 16);
    }

    /** 读取原始 Cookie（按后缀匹配，兼容不同入口 rootUrl 前缀不一致的情况） */
    private static function rawCookie($name = self::COOKIE)
    {
        return self::rawCookieList($name)[0] ?? '';
    }

    /**
     * 取所有后缀匹配的 Cookie 值。
     * 同一个名字理论上只有一个，但历史上不同入口写入的前缀可能不同、
     * 浏览器里会同时留下多个同名后缀的 Cookie，这里全部取回来合并验签。
     */
    private static function rawCookieList($name = self::COOKIE)
    {
        $suffix = (string) $name;
        $list = array();
        foreach ($_COOKIE as $key => $val) {
            if (is_string($key) && strpos($key, $suffix) === strlen($key) - strlen($suffix)) {
                if (is_string($val) && $val !== '') {
                    $list[] = $val;
                }
            }
        }
        return $list;
    }

    /** coid => 签名 的映射 */
    private static function parseMap($raw)
    {
        $map = array();
        if ($raw === '') {
            return $map;
        }
        foreach (explode('|', $raw) as $item) {
            $parts = explode('.', $item);
            if (count($parts) !== 2) {
                continue;
            }
            $map[(int) $parts[0]] = (string) $parts[1];
        }
        return $map;
    }

    /** 合并所有同名后缀 Cookie 的映射（后写入的覆盖先写入的） */
    private static function parseAll($name = self::MINE_COOKIE)
    {
        $map = array();
        foreach (self::rawCookieList($name) as $raw) {
            foreach (self::parseMap($raw) as $coid => $mac) {
                $map[$coid] = $mac;
            }
        }
        return $map;
    }

    /** 记住一条评论归属（默认记「我的评论」，$name 传 COOKIE 时用于私密评论） */
    private static function remember($coid, $name = self::MINE_COOKIE)
    {
        $coid = (int) $coid;
        if ($coid <= 0) {
            return;
        }

        $map = self::parseAll($name);
        $map[$coid] = ($name === self::COOKIE) ? self::token($coid) : self::mineToken($coid);
        while (count($map) > self::COOKIE_MAX) {
            array_shift($map);
        }

        $value = array();
        foreach ($map as $id => $mac) {
            $value[] = $id . '.' . $mac;
        }
        $raw = implode('|', $value);

        /* 不用 Typecho 的 rootUrl 前缀：评论 POST、主题目录脚本等入口算出的
           rootUrl 不同，会写出多个同名后缀但路径不同的 Cookie。
           这里固定用裸名 + path=/ 写一个规范 Cookie，读取时按后缀匹配即可。 */
        if (!headers_sent()) {
            setcookie($name, $raw, array(
                'expires'  => time() + 400 * 86400,
                'path'     => '/',
                'secure'   => \Typecho\Request::getInstance()->isSecure(),
                'httponly' => false,
                'samesite' => 'Lax',
            ));
        }

        /* 当前请求里也同步一份，保证同一请求内的后续判断可见 */
        $_COOKIE[$name] = $raw;
    }

    /**
     * 评论完成回调：任意用户勾选“私密”时标记该评论
     *
     * @param object $widget Widget\Feedback 实例（评论已 push 进 row）
     */
    public static function onFinish($widget)
    {
        try {
            $request = \Typecho\Request::getInstance();
            $coid = (int) $widget->coid;
            if ($coid <= 0) {
                return;
            }

            if ((int) $request->get('private')) {
                self::ensureColumn();
                $db = \Typecho\Db::get();
                $db->query(
                    $db->update('table.comments')
                        ->rows(array('private' => 1))
                        ->where('coid = ?', $coid)
                );

                /* 游客没有账号 id，用签名 Cookie 记住这条私密评论归他本人所有 */
                if ((int) $widget->authorId === 0) {
                    self::remember($coid, self::COOKIE);
                }
            }

            /* 所有游客评论都记进「我的评论」Cookie，铃铛据此判断身份 */
            if ((int) $widget->authorId === 0) {
                self::remember($coid, self::MINE_COOKIE);
            }
        } catch (\Throwable $e) {
            /* 归属记录失败不影响评论本身 */
        }
    }
}
