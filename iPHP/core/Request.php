<?php

// namespace iPHP\core;

/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
class Request
{
    public static function validate_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    public static function isUrl($url, $strict = false)
    {
        if (is_null($url)) {
            return;
        }

        $url = trim($url);
        if ($strict) {
            return (stripos($url, 'http://') === 0 || stripos($url, 'https://') === 0);
        }

        return !(stripos($url, 'http://') === false && stripos($url, 'https://') === false);
    }

    public static function getMethod()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public static function has($key, $flag = 1)
    {
        return $flag ? isset($_GET[$key]) : isset($_POST[$key]);
    }

    public static function isSSL()
    {
        return $_SERVER['SERVER_PORT'] == 443;
    }

    public static function isPost()
    {
        return (self::getMethod() == 'POST');
    }
    public static function isPut()
    {
        return (self::getMethod() == 'PUT');
    }
    public static function isDelete()
    {
        return (self::getMethod() == 'DELETE');
    }
    public static function isHead()
    {
        return (self::getMethod() == 'HEAD');
    }
    public static function isOptions()
    {
        return (self::getMethod() == 'OPTIONS');
    }
    public static function isTrace()
    {
        return (self::getMethod() == 'TRACE');
    }
    public static function isData()
    {
        return (self::getMethod() == 'DATA');
    }

    public static function isGet()
    {
        return (self::getMethod() == 'GET');
    }

    public static function hasKey($key)
    {
        $param = self::param();
        return isset($param[$key]);
    }

    public static function isAjax()
    {
        return (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') ||
            (isset($_SERVER['X-Requested-With']) && $_SERVER['X-Requested-With'] == 'XMLHttpRequest') ||
            (isset($_SERVER['HTTP_CONTENT_TYPE']) && $_SERVER['HTTP_CONTENT_TYPE'] == 'application/json') ||
            isset($_GET['ajax']) || isset($_POST['ajax']) ||
            isset($_GET['is_ajax']) || isset($_POST['is_ajax']) ||
            (isset($_GET['format']) && ($_GET['format'] == 'json' || $_POST['format'] == 'json')));
    }

    /**
     * 全局变量过滤
     */
    public static function bootstrap()
    {
        $allowed = [
            'GLOBALS' => 1,
            '_GET'    => 1, '_POST' => 1,
            '_COOKIE' => 1, '_FILES' => 1, '_SERVER' => 1,
            '_APP'    => 1, 'page' => 1
        ];
        foreach ($GLOBALS as $key => $value) {
            if (!isset($allowed[$key])) {
                unset($GLOBALS[$key]);
            }
        }
        self::initServer([
            'HTTP_REFERER', 'HTTP_SCHEME', 'HTTP_HOST', 'HTTP_URL', 'HTTP_X_FORWARDED_FOR',
            'HTTP_USER_AGENT', 'HTTP_ACCEPT_LANGUAGE',
            'HTTP_CLIENT_IP', 'HTTP_SCHEME', 'HTTPS', 'PHP_SELF', 'REMOTE_ADDR',
            'REQUEST_HOST', 'REQUEST_URI', 'REQUEST_URL', 'REQUEST_METHOD', 'SCRIPT_NAME', 'REQUEST_TIME',
            'SERVER_SOFTWARE', 'SERVER_ADDR', 'SERVER_PORT',
            'X-Requested-With', 'HTTP_X_REQUESTED_WITH',
            'QUERY_STRING', 'argv', 'argc',
            'Authorization', 'HTTP_AUTHORIZATION',
            'Token', 'HTTP_TOKEN',
        ]);
    }

    /**
     * 初始化$_GET/$_POST为全局变量
     * @param $keys
     * @param $method
     * @param $cvtype
     */
    public static function filter($keys, $method = null, $cvtype = 1, $istrim = true)
    {
        !is_array($keys) && $keys = [$keys];
        foreach ($keys as $key) {
            if ($key == 'GLOBALS') {
                continue;
            }
            $GLOBALS[$key] = null;
            if ($method != 'P' && isset($_GET[$key])) {
                $GLOBALS[$key] = $_GET[$key];
            } elseif ($method != 'G' && isset($_POST[$key])) {
                $GLOBALS[$key] = $_POST[$key];
            }
            if (isset($GLOBALS[$key]) && !empty($cvtype) || $cvtype == 2) {
                $GLOBALS[$key] = Security::escapeChar($GLOBALS[$key], $cvtype == 2, $istrim);
            }
        }
    }

    /**
     * 获取$_GET和$_POST变量
     * @param $key
     * @param $method
     */
    public static function param($key = null, $flag = false)
    {
        $get = (array)self::get();
        $post = (array)self::post();
        $param = array_merge($get, $post);
        if ($flag) {
            $input = (array)self::input();
            $param = array_merge($param, $input);
        }
        $key && $param = isset($param[$key]) ? $param[$key] : null;
        return $param;
    }

    public static function sparam($key = null, $default = null)
    {
        $get = (array)self::sget();
        $post = (array)self::spost();
        $param = array_merge($get, $post);
        $key && $param = $param[$key];
        empty($param) && $param = $default;
        return Security::safeStr($param);
    }

    public static function put($value = null)
    {
        $value === null && $value = self::input();
        if ($value) {
            if (strpos($value, '<xml>') !== false) {
                $data = Utils::xmlToArray($value);
            } else {
                $data = json_decode($value, true);
                if (empty($data) && strpos($value, '&') !== false) {
                    parse_str($value, $data);
                }
            }
            Security::addslash($data);
            // Security::wafFilter($data);
            return $data;
        } else {
            return false;
        }
    }

    public static function args($data = null)
    {
        is_null($data) && $data = self::get('_args');
        $array = [];
        $dA = explode(',', $data);
        foreach ((array) $dA as $d) {
            list($f, $v) = explode('=', $d);
            $v == 'now' && $v = time();
            $v = (int) $v;
            $array[$f] = $v;
        }
        return $array;
    }

    public static function input($input = null)
    {
        $input === null && $input = file_get_contents('php://input');
        if ($input) {
            if (strpos($input, '<xml>') !== false) {
                $data = Utils::xmlToArray($input);
            } else {
                $data = json_decode($input, true);
                if (empty($data) && strpos($input, '&') !== false) {
                    parse_str($input, $data);
                }
            }
            return $data;
        } else {
            return false;
        }
    }

    public static function file($key = null)
    {
        $value = $key === null ? $_FILES : $_FILES[$key];
        // $key === null && $_FILES = $value;
        return $value;
    }

    public static function sget($key = null, $default = null)
    {
        $value = self::get($key, $default);
        return Security::safeStr($value);
    }

    public static function get($key = null, $default = null)
    {
        if ($key && !isset($_GET[$key]) && !is_null($default)) {
            return $default;
        }
        $value = $key === null ? $_GET : (isset($_GET[$key]) ? $_GET[$key] : null);
        if (is_null($value)) {
            return null;
        }
        return Security::escapeStr($value);
    }

    public static function spost($key = null, $default = null)
    {
        $value = self::post($key, $default);
        return Security::safeStr($value);
    }

    public static function post($key = null, $default = null)
    {
        if ($key && !isset($_POST[$key]) && !is_null($default)) {
            return $default;
        }
        $value = $key === null ? $_POST : (isset($_POST[$key]) ? $_POST[$key] : null);
        if (is_null($value)) {
            return null;
        }
        return Security::escapeStr($value);
    }

    public static function server($key = null)
    {
        $value = Security::escapeStr($key === null ? $_SERVER : $_SERVER[$key]);
        // $key === null ? $_SERVER = $value : $_SERVER[$key] = $value;
        return $value;
    }

    // 获取客户端IP
    public static function userAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    public static function ip($format = 0)
    {
        $ip = '0.0.0.0'; // 默认IP

        if (!empty($_SERVER['HTTP_CLIENT_IP']) && strcasecmp($_SERVER['HTTP_CLIENT_IP'], 'unknown')) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], 'unknown')) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]; // 获取第一个IP
        } elseif (!empty($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // IP地址合法验证
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            $long = sprintf('%u', ip2long($ip));
            return Security::escapeStr($format === 0 ? $ip : $long);
        }

        return Security::escapeStr($ip);
    }

    public static function getBrowserFingerprint()
    {
        $fingerprint = [];
        // 用户代理
        $fingerprint['userAgent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        // 屏幕分辨率
        $fingerprint['screenResolution'] = isset($_COOKIE['screenResolution']) ? $_COOKIE['screenResolution'] : '';
        // 时区
        $fingerprint['timezone'] = date_default_timezone_get();
        // 浏览器语言
        $fingerprint['language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        // 设备信息
        $fingerprint['device'] = $_SERVER['HTTP_DEVICE_PIXEL_RATIO'] ?? '1';

        // 返回指纹
        return md5(json_encode($fingerprint));
    }

    /**
     * 获取服务器变量
     * @param $keys
     * @return string
     */
    public static function initServer($keys)
    {
        // Fix for IIS when running with PHP ISAPI
        if (empty($_SERVER['REQUEST_URI']) || (php_sapi_name() != 'cgi-fcgi' && preg_match('/^Microsoft-IIS\//', $_SERVER['SERVER_SOFTWARE']))) {
            if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
                $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
            } elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
                $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
            } else {
                // Use ORIG_PATH_INFO if there is no PATH_INFO
                if (!isset($_SERVER['PATH_INFO']) && isset($_SERVER['ORIG_PATH_INFO'])) {
                    $_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
                }

                // Some IIS + PHP configurations puts the script-name in the path-info (No need to append it twice)
                if (isset($_SERVER['PATH_INFO'])) {
                    if ($_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME']) {
                        $_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
                    } else {
                        $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
                    }
                }

                // Append the query string if it exists and isn't null
                if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
                    $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
                }
            }
        }
        $_SERVER['HTTP_SCHEME'] = self::scheme();
        if (empty($_SERVER['REQUEST_URL'])) {
            $_SERVER['REQUEST_URL'] = self::scheme() . (isset($_SERVER['HTTP_X_HTTP_HOST']) ? $_SERVER['HTTP_X_HTTP_HOST'] : $_SERVER['HTTP_HOST']) . $_SERVER['REQUEST_URI'];
        }

        $_SERVER['REQUEST_HOST'] = self::scheme() . (isset($_SERVER['HTTP_X_HTTP_HOST']) ? $_SERVER['HTTP_X_HTTP_HOST'] : $_SERVER['HTTP_HOST']);

        // Fix for PHP as CGI hosts that set SCRIPT_FILENAME to something ending in php.cgi for all requests
        if (isset($_SERVER['SCRIPT_FILENAME']) && (strpos($_SERVER['SCRIPT_FILENAME'], 'php.cgi') == strlen($_SERVER['SCRIPT_FILENAME']) - 7)) {
            $_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];
        }

        // Fix for ther PHP as CGI hosts
        if (strpos($_SERVER['SCRIPT_NAME'], 'php.cgi') !== false) {
            unset($_SERVER['PATH_INFO']);
        }

        $_SERVER['PHP_SELF'] = '';
        preg_match('/(.+?\.php)/', $_SERVER['REQUEST_URI'], $matches);
        $matches[1] && $_SERVER['PHP_SELF'] = $matches[1];

        if (empty($_SERVER['PHP_SELF'])) {
            $_SERVER['PHP_SELF'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']);
        }

        foreach ($_SERVER as $key => $sval) {
            if (in_array($key, $keys) || strpos($key, 'HTTP_') !== false) {
                if ($sval) {
                    $sval = str_replace(['<', '>', '"', "'", '%3C', '%3E', '%22', '%27', '%3c', '%3e'], '', $sval);
                    $_SERVER[$key] = str_replace(["\0", "\x0B", '%00', "\r"], '', $sval);
                }
            } else {
                unset($_SERVER[$key]);
            }
        }
        Security::addslash($_SERVER);
    }

    public static function scheme($port = null)
    {
        // file_put_contents(__DIR__.'/test.log',var_export($_SERVER,true));
        $scheme = null;
        is_null($port) && $port = $_SERVER['SERVER_PORT'];
        $port == 443 && $scheme = 'https';
        $port == 80 && $scheme = 'http';
        $port == 21 && $scheme = 'ftp';
        //cloudflare
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ? 'https' : 'http';
        }
        //aliyun
        if (isset($_SERVER['HTTP_X_CLIENT_SCHEME'])) {
            $scheme = $_SERVER['HTTP_X_CLIENT_SCHEME'] == 'https' ? 'https' : 'http';
        }
        return $scheme ? $scheme . '://' : null;
    }

    public static function status($code, $error = '')
    {
        $statusMap = [
            // Success 2xx
            200 => 'OK',
            // Redirection 3xx
            301 => 'Moved Permanently',
            302 => 'Moved Temporarily ', // 1.1
            304 => 'Not Modified',
            // Client Error 4xx
            400 => 'Bad Request',
            403 => 'Forbidden',
            404 => 'Not Found',
            // Server Error 5xx
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
        ];
        if (isset($statusMap[$code])) {
            header('HTTP/1.1 ' . $code . ' ' . $statusMap[$code]);
            $error && header('X-iPHP-ERROR:' . $error);
        }
    }

    /**
     * 解析URI /xxx.php/article/add/id/1/aa/aaa
     *
     * @return void
     */
    public static function route()
    {
        $paths = self::getPaths();
        if (empty($paths)) {
            return;
        }

        // 提取应用名和操作名
        $app = isset($paths[0]) ? $paths[0] : null;
        $do = isset($paths[1]) ? $paths[1] : null;

        if (!is_null($app)) {
            $_GET['app'] = Security::safeStr($app);
        }
        if (!is_null($do)) {
            $_GET['do'] = Security::safeStr($do);
        }

        // 处理剩余路径参数
        $request = array_slice($paths, 2);
        if (!empty($request)) {
            $request = array_reverse($request);
            $request = array_chunk($request, 2);
            $request = array_reverse($request);

            foreach ($request as $pair) {
                if (count($pair) === 1) {
                    // 如果只有一个值，设置为 sdo
                    $_GET['sdo'] = Security::safeStr($pair[0]);
                } else {
                    // 否则，将键值对解析为 GET 参数
                    $key = Security::safeStr($pair[1]);
                    $value = Security::escapeStr($pair[0]);
                    $_GET[$key] = $value;
                }
            }
        }
    }

    /**
     * 获取路径片段
     *
     * @return array
     */
    public static function getPaths()
    {
        $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $iPhpSelf = defined('iPHP_SELF') ? iPHP_SELF : '';

        // 检查是否包含 iPHP_SELF
        $pos = stripos($requestUrl, $iPhpSelf . '/');
        if ($pos === false) {
            return [];
        }

        // 提取路径部分并分割
        $uri = parse_url($requestUrl, PHP_URL_PATH);
        $paths = explode('/', trim($uri, '/'));
        array_shift($paths); // 移除第一个元素（通常是脚本名）

        return $paths;
    }
}
