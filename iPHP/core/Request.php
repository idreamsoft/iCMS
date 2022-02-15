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
    public static function isUrl($url, $strict = false)
    {
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
    public static function isPost()
    {
        return (self::getMethod() == 'POST');
    }
    public static function isGet()
    {
        return (self::getMethod() == 'GET');
    }
    public static function isAjax()
    {
        return (
            (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest") ||
            (isset($_SERVER["X-Requested-With"]) && $_SERVER["X-Requested-With"] == "XMLHttpRequest") ||
            isset($_GET['ajax']) || isset($_POST['ajax']) ||
            isset($_GET['is_ajax']) || isset($_POST['is_ajax']) ||
            (isset($_GET['format']) && ($_GET['format'] == 'json' || $_POST['format'] == 'json')));
    }
    /**
     * 全局变量过滤
     */
    public static function boot()
    {
        $allowed = array(
            'GLOBALS' => 1,
            '_GET' => 1, '_POST' => 1,
            '_COOKIE' => 1, '_FILES' => 1, '_SERVER' => 1,
            '_APP' => 1, 'page' => 1
        );
        foreach ($GLOBALS as $key => $value) {
            if (!isset($allowed[$key])) {
                unset($GLOBALS[$key]);
            }
        }

        //兼容PHP5.3 magic_quotes_gpc=1
        define('iPHP_GET_MAGIC_QUOTES_GPC', !@ini_get('magic_quotes_gpc'));

        if (iPHP_GET_MAGIC_QUOTES_GPC) {
            // Security::addslash($GLOBALS);
            // Security::addslash($_POST);
            // Security::addslash($_GET);
            // Security::addslash($_COOKIE);
            // Security::addslash($_SESION);
            // Security::addslash($_FILES);
        }

        self::initServer(array(
            'HTTP_REFERER', 'HTTP_SCHEME', 'HTTP_HOST', 'HTTP_URL', 'HTTP_X_FORWARDED_FOR',
            'HTTP_USER_AGENT', 'HTTP_ACCEPT_LANGUAGE',
            'HTTP_CLIENT_IP', 'HTTP_SCHEME', 'HTTPS', 'PHP_SELF', 'REMOTE_ADDR',
            'REQUEST_URI', 'REQUEST_URL', 'REQUEST_METHOD', 'SCRIPT_NAME', 'REQUEST_TIME',
            'SERVER_SOFTWARE', 'SERVER_ADDR', 'SERVER_PORT',
            'X-Requested-With', 'HTTP_X_REQUESTED_WITH',
            'QUERY_STRING', 'argv', 'argc',
            'Authorization', 'HTTP_AUTHORIZATION',
            'Token', 'HTTP_TOKEN',
        ));
    }

    /**
     * 初始化$_GET/$_POST为全局变量
     * @param $keys
     * @param $method
     * @param $cvtype
     */
    public static function filter($keys, $method = null, $cvtype = 1, $istrim = true)
    {
        !is_array($keys) && $keys = array($keys);
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
    public static function param($key = null)
    {
        $get  = (array)self::get();
        $post = (array)self::post();
        $param = array_merge($get, $post);
        $key && $param = $param[$key];
        return $param;
    }
    public static function sparam($key = null, $default = null)
    {
        $get  = (array)self::sget();
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
        $array = array();
        $dA = explode(',', $data);
        foreach ((array) $dA as $d) {
            list($f, $v) = explode(';', $d);
            $v == 'now' && $v = time();
            $v = (int) $v;
            $array[$f] = $v;
        }
        return $array;
    }
    public static function input($input = null, $name = false)
    {

        $input === null && $input = file_get_contents("php://input");
        $name === null && Utils::LOG($input, 'input');

        if ($input) {
            if (strpos($input, '<xml>') !== false) {
                $data = Utils::xmlToArray($input);
            } else {
                $data = json_decode($input, true);
                if (empty($data) && strpos($input, '&') !== false) {
                    parse_str($input, $data);
                }
            }
            // Security::addslash($data);
            // Waf::check($data);
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
        $value = $key === null ? $_GET : $_GET[$key];
        if (is_null($value)) {
            return;
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
        $value = $key === null ? $_POST : $_POST[$key];
        if (is_null($value)) {
            return;
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
    public static function ip($format = 0)
    {
        if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] && strcasecmp($_SERVER['HTTP_CLIENT_IP'], 'unknown')) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], 'unknown')) {
            $ip  = $_SERVER['HTTP_X_FORWARDED_FOR'];
            $ips = explode(',', $ip);
            $key = array_search('unknown', $ips);
            if ($key !== false) unset($ips[$key]);
            $ip = trim($ips[0]);
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return Security::escapeStr($ip[$format]);
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
            $_SERVER['REQUEST_URL'] = self::scheme() . ($_SERVER['HTTP_X_HTTP_HOST'] ?: $_SERVER['HTTP_HOST']) . $_SERVER['REQUEST_URI'];
        }
        if (empty($_SERVER['HTTP_URL'])) {
            $_SERVER['HTTP_URL'] = self::scheme() . ($_SERVER['HTTP_X_HTTP_HOST'] ?: $_SERVER['HTTP_HOST']);
        }

        // Fix for PHP as CGI hosts that set SCRIPT_FILENAME to something ending in php.cgi for all requests
        if (isset($_SERVER['SCRIPT_FILENAME']) && (strpos($_SERVER['SCRIPT_FILENAME'], 'php.cgi') == strlen($_SERVER['SCRIPT_FILENAME']) - 7)) {
            $_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];
        }

        // Fix for ther PHP as CGI hosts
        if (strpos($_SERVER['SCRIPT_NAME'], 'php.cgi') !== false) {
            unset($_SERVER['PATH_INFO']);
        }

        if (empty($_SERVER['PHP_SELF'])) {
            $_SERVER['PHP_SELF'] = preg_replace("/(\?.*)?$/", '', $_SERVER["REQUEST_URI"]);
        }
        if (stripos($_SERVER['PHP_SELF'], '.php/') !== false) {
            $_SERVER['PHP_SELF'] = sprintf('%s.php', strstr($_SERVER['PHP_SELF'], '.php/', true));
        }
        foreach ($_SERVER as $key => $sval) {
            if (in_array($key, $keys) || strpos($key, 'HTTP_') !== false) {
                $sval = str_replace(array('<', '>', '"', "'", '%3C', '%3E', '%22', '%27', '%3c', '%3e'), '', $sval);
                $_SERVER[$key] = str_replace(array("\0", "\x0B", "%00", "\r"), '', $sval);
            } else {
                unset($_SERVER[$key]);
            }
        }
        Security::addslash($_SERVER);
    }
    public static function scheme($port = null)
    {
        $scheme = null;
        is_null($port) && $port = $_SERVER['SERVER_PORT'];
        $port == 443 && $scheme = 'https';
        $port == 80 && $scheme = 'http';
        $port == 21 && $scheme = 'ftp';
        return $scheme ? $scheme . "://" : null;
    }
    public static function status($code, $ecode = '')
    {
        $statusMap = array(
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
        );
        if (isset($statusMap[$code])) {
            header('HTTP/1.1 ' . $code . ' ' . $statusMap[$code]);
            $ecode && header("X-iPHP-ECODE:" . $ecode);
        }
    }
}
