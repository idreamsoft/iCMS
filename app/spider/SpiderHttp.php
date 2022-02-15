<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
defined('iPHP') or exit('What are you doing?');

class SpiderHttp
{
    /**
     * 检测采集url端口
     *
     * @var array
     */
    public static $safe_port   = array('80', '443');
    /**
     * 是否检测采集url安全性
     *
     * @var boolean
     */
    public static $safe_url    = false;
    public static $proxy_array = array();
    public static $callback    = array();
    public static $debug       = true;
    public static $handle      = null;

    public static $CURL_PROXY             = false;
    public static $CURL_INFO              = null;
    public static $CURL_ERRNO             = 0;
    public static $CURL_ERROR             = null;
    public static $CURLOPT_ENCODING       = null;
    public static $CURLOPT_REFERER        = null;
    /**
     * 数据传输的最大允许时间.
     * 接收数据时超时设置，如果60秒内数据未接收完，直接退出
     *
     * @var integer
     */
    public static $CURLOPT_TIMEOUT        = 60;
    /**
     * 连接超时，这个数值如果设置太短可能导致数据请求不到就断开了
     *
     * @var integer
     */
    public static $CURLOPT_CONNECTTIMEOUT = 15;
    public static $CURLOPT_USERAGENT      = null;
    public static $CURLOPT_COOKIE         = null;
    public static $CURLOPT_COOKIEFILE     = null;
    public static $CURLOPT_COOKIEJAR      = null;
    public static $CURLOPT_HTTPHEADER     = null;

    public static function charsetTrans($html, $content_charset, $encode, $out = 'UTF-8')
    {
        if (Spider::$isTest) {
            echo '<b>规则设置编码:</b>' . $encode . '<br />';
        }

        $encode == 'auto' && $encode = null;
        /**
         * 检测http返回的编码
         */
        if ($content_charset) {
            $content_charset = rtrim($content_charset, ';');
            if (empty($encode) || strtoupper($encode) != strtoupper($content_charset)) {
                $encode = $content_charset;
            }
            if (Spider::$isTest) {
                echo '<b>检测http编码:</b>' . $encode . '<br />';
            }
            if (strtoupper($encode) == $out) {
                return $html;
            }
        }
        /**
         * 检测页面编码
         */
        preg_match('/<meta[^>]*?charset=(["\']?)([a-zA-z0-9\-\_]+)(\1)[^>]*?>/is', $html, $charset);
        $meta_encode = str_replace(array('"', "'"), '', trim($charset[2]));
        if (empty($encode)) {
            $meta_encode && $encode = $meta_encode;
            if (Spider::$isTest) {
                echo '<b>检测页面编码:</b>' . $meta_encode . '<br />';
            }
        }
        preg_match('/<meta[^>]*?http-equiv=(["\']?)content-language(\1)[^>]*?content=(["\']?)([a-zA-z0-9\-\_]+)(\3)[^>]*?>/is', $html, $language);
        $lang_encode = str_replace(array('"', "'"), '', trim($language[4]));
        if (empty($encode)) {
            $lang_encode && $encode = $lang_encode;
            if (Spider::$isTest) {
                echo '<b>检测页面meta编码声明:</b>' . $lang_encode . '<br />';
            }
        }
        if ($content_charset && $meta_encode && strtoupper($meta_encode) != strtoupper($content_charset)) {
            $encode = $meta_encode;
            if (Spider::$isTest) {
                echo '<b>检测到http编码与页面编码不一致:</b>' . $content_charset . ',' . $meta_encode . '<br />';
            }
        }

        if ($lang_encode && $meta_encode && strtoupper($meta_encode) != strtoupper($lang_encode)) {
            $encode = null;
            if (Spider::$isTest) {
                echo '<b>检测到页面存在两种不一样的编码声明:</b>' . $lang_encode . ',' . $meta_encode . '<br />';
            }
        }

        if (function_exists('mb_detect_encoding') && empty($encode)) {
            $detect_encode = mb_detect_encoding($html, array("ASCII", "UTF-8", "GB2312", "GBK", "BIG5"));
            $detect_encode && $encode = $detect_encode;
            if (Spider::$isTest) {
                echo '<b>程序自动识别页面编码:</b>' . $detect_encode . '<br />';
            }
        }

        if (strtoupper($encode) == $out) {
            return $html;
        }
        if (strtoupper($encode) == 'GB2312') {
            $encode = 'GBK';
        }
        if (Spider::$isTest) {
            echo '<b>页面编码不一致,进行转码[' . $encode . '=>' . $out . ']</b><br />';
        }
        $html = preg_replace('/(<meta[^>]*?charset=(["\']?))[a-zA-z0-9\-\_]*(\2[^>]*?>)/is', "\\1$out\\3", $html, 1);

        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($html, $out, $encode);
        } elseif (function_exists('iconv')) {
            return iconv($encode, $out, $html);
        } else {
            echo 'charsetTrans failed, no function';
        }
    }

    public static function safe_url($url)
    {
        $parsed = parse_url($url);
        $validate_ip = true;

        if ($parsed['port'] && is_array(self::$safe_port) && !in_array($parsed['port'], self::$safe_port)) {
            if (Spider::$isTest) {
                echo "<b>请求错误:非正常端口,因安全问题只允许抓取80,443端口的链接,如有特殊需求请自行修改程序</b>" . PHP_EOL;
            }
            return false;
        } else {
            preg_match('/^\d+$/', $parsed['host']) && $parsed['host'] = long2ip($parsed['host']);
            $long = ip2long($parsed['host']);
            if ($long === false) {
                $ip = null;
                if (self::$safe_url) {
                    @putenv('RES_OPTIONS=retrans:1 retry:1 timeout:1 attempts:1');
                    $ip   = gethostbyname($parsed['host']);
                    $long = ip2long($ip);
                    $long === false && $ip = null;
                    @putenv('RES_OPTIONS');
                }
            } else {
                $ip = $parsed['host'];
            }
            $ip && $validate_ip = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        if (!in_array($parsed['scheme'], array('http', 'https')) || !$validate_ip) {
            if (Spider::$isTest) {
                echo "<b>{$url} 请求错误:非正常URL格式,因安全问题只允许抓取 http:// 或 https:// 开头的链接或公有IP地址</b>" . PHP_EOL;
            }
            return false;
        } else {
            return $url;
        }
    }

    public static function remote($url, $ref = null, $_count = 0)
    {
        if (self::safe_url($url) === false) return false;

        if (Spider::$isShell && self::$debug) {
            SpiderTools::prints('%s 第[%s]次 => [w]%s[/w]', ['抓取链接', $_count + 1, $url], 's');
        }

        $parsed = parse_url($url);
        $url = str_replace('&amp;', '&', $url);
        if (empty(Spider::$referer)) {
            Spider::$referer = $parsed['scheme'] . '://' . $parsed['host'];
        }

        $options = array(
            CURLOPT_URL                  => $url,
            CURLOPT_REFERER              => self::$CURLOPT_REFERER ? self::$CURLOPT_REFERER : Spider::$referer,
            CURLOPT_USERAGENT            => self::$CURLOPT_USERAGENT ? self::$CURLOPT_USERAGENT : Spider::$useragent,
            CURLOPT_ENCODING             => self::$CURLOPT_ENCODING ? self::$CURLOPT_ENCODING : Spider::$encoding,
            CURLOPT_TIMEOUT              => self::$CURLOPT_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT       => self::$CURLOPT_CONNECTTIMEOUT,
            CURLOPT_RETURNTRANSFER       => 1,
            CURLOPT_FAILONERROR          => 0,
            CURLOPT_HEADER               => 0,
            CURLOPT_NOSIGNAL             => true,
            // CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            // CURLOPT_DNS_CACHE_TIMEOUT    => 86400,
            CURLOPT_SSL_VERIFYPEER       => false,
            CURLOPT_SSL_VERIFYHOST       => false
            // CURLOPT_FOLLOWLOCATION => 1,// 使用自动跳转
            // CURLOPT_MAXREDIRS => 7,//查找次数，防止查找太深
        );
        Spider::$cookie && $options[CURLOPT_COOKIE] = Spider::$cookie;

        if (self::$CURLOPT_COOKIE) {
            $options[CURLOPT_COOKIE] = self::$CURLOPT_COOKIE;
        }
        if (self::$CURLOPT_COOKIEFILE) {
            $options[CURLOPT_COOKIEFILE] = self::$CURLOPT_COOKIEFILE;
        }
        if (self::$CURLOPT_COOKIEJAR) {
            $options[CURLOPT_COOKIEJAR] = self::$CURLOPT_COOKIEJAR;
        }
        if (self::$CURLOPT_HTTPHEADER) {
            $options[CURLOPT_HTTPHEADER] = self::$CURLOPT_HTTPHEADER;
        }
        if (Spider::$CURL_PROXY || SpiderHttp::$CURL_PROXY) {
            $proxy = self::proxy_test($options);
            if (Spider::$isTest) {
                echo "<b>使用代理:</b>";
                echo $proxy;
                echo '<hr />';
            }
            $proxy && Http::setProxy($options, $proxy);
        }

        Spider::$PROXY_URL && $options[CURLOPT_URL] = Spider::$PROXY_URL . urlencode($url);

        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }
        if (self::$callback['progress'] && is_callable(self::$callback['progress'])) {
            $options[CURLOPT_NOPROGRESS] = FALSE;
            $options[CURLOPT_PROGRESSFUNCTION] = array(__CLASS__, 'curl_progressfunction');
        }
        if (self::$callback['header'] && is_callable(self::$callback['header'])) {
            $options[CURLOPT_HEADERFUNCTION] = self::$callback['header'];
        }
        if (self::$callback['options'] && is_callable(self::$callback['options'])) {
            call_user_func_array(self::$callback['options'], array(&$options, $ref, &$_count));
        }

        self::$handle = curl_init();
        curl_setopt_array(self::$handle, $options);
        $responses = curl_exec(self::$handle);
        $info  = self::$CURL_INFO   = curl_getinfo(self::$handle);
        self::$CURL_ERRNO  = curl_errno(self::$handle);
        self::$CURL_ERRNO && self::$CURL_ERROR = curl_error(self::$handle);

        if (Spider::$isTest) {
            echo "<b>{$url} 请求信息:</b>";
            echo "<pre style='max-height:90px;overflow-y: scroll;'>";
            print_r($info);
            echo '</pre><hr />';
            if ($_GET['breakinfo']) {
                exit();
            }
        }
        if (in_array($info['http_code'], array(301, 302)) && $_count < 5) {
            $_count++;
            $newurl = $info['redirect_url'];
            if (empty($newurl)) {
                curl_setopt(self::$handle, CURLOPT_HEADER, 1);
                $header        = curl_exec(self::$handle);
                preg_match('|Location: (.*)|i', $header, $matches);
                $newurl     = ltrim($matches[1], '/');
                if (empty($newurl)) return false;

                if (!strstr($newurl, 'http://')) {
                    $host    = $parsed['scheme'] . '://' . $parsed['host'];
                    $newurl = $host . '/' . $newurl;
                }
            }
            $newurl    = trim($newurl);
            curl_close(self::$handle);
            if (Spider::$isShell && self::$debug) {
                SpiderTools::prints('[http_code=%s]链接跳转至 => [w]%s[/w]', [$info['http_code'], $newurl], 's');
            }
            unset($responses, $info);
            return self::remote($newurl, $ref, $_count);
        }
        if (in_array($info['http_code'], array(404, 500))) {
            curl_close(self::$handle);
            unset($responses, $info);
            return false;
        }

        if ((empty($responses) || $info['http_code'] != 200) && $_count < 5) {
            $_count++;
            if (Spider::$isTest) {
                echo $url . '<br />';
                echo "获取内容失败,重试第{$_count}次...<br />";
            }
            curl_close(self::$handle);
            unset($responses, $info);
            return self::remote($url, $ref, $_count);
        }
        $pos = stripos($info['content_type'], 'charset=');
        $pos !== false && $content_charset = trim(substr($info['content_type'], $pos + 8));
        $responses = self::charsetTrans($responses, $content_charset, Spider::$charset);
        curl_close(self::$handle);
        unset($info);
        if (Spider::$isTest) {
            echo "<b>{$url} 抓取结果:</b>";
            echo "<pre style='max-height:150px;overflow-y: scroll;'>";
            print_r(htmlspecialchars($responses));
            // print_r(htmlspecialchars(substr($responses, 0, 800)));
            echo '</pre><hr />';
        }
        Spider::$url = $url;

        // (Spider::$isShell && self::$debug) && print self::datetime()."\033[36mSpiderHttp::remote\033[0m OK ".PHP_EOL;

        return $responses;
    }
    public static function Cookie_get($url, $data = null, $flag = false)
    {
        Http::$CURLOPT_TIMEOUT        = 60; //数据传输的最大允许时间
        Http::$CURLOPT_CONNECTTIMEOUT = 10;  //连接超时时间
        $host = parse_url($url, PHP_URL_HOST);
        $path = iPHP_APP_CACHE . '/spider/cookie.' . $host . '.txt';
        File::mkdir(dirname($path));
        Http::$CURLOPT_COOKIEJAR = $path;
        if ($data && is_string($data)) {
            $data = parse_url_qs($data);
        }
        $ret = Http::post($url, $data);
        $flag === true && self::$CURLOPT_COOKIEFILE = $path;
        is_callable(self::$callback['get_cookie']) && call_user_func_array(self::$callback['get_cookie'], array($ret, $path));
        return array($ret, $path);
    }
    public static function proxy_test($options = null)
    {
        Http::$CURL_PROXY = self::$CURL_PROXY ?: Spider::$CURL_PROXY;
        // Http::$CURL_PROXY_ARRAY = self::$proxy_array?:Spider::$proxy_array;
        return Http::testProxy($options);
    }
    public static function curl_progressCallback($a)
    {
        static $previousProgress = 0;
        $download_size = $a[1];
        $downloaded_size = $a[2];
        if ($download_size == 0) {
            $progress = 0;
        } else {
            $progress = round($downloaded_size / $download_size, 2) * 100;
        }
        if ($progress > $previousProgress) {
            $previousProgress = $progress;
            if ($progress % 2) {
                echo '.';
            }
        }
    }
    public static function curl_progressfunction($resource, $download_total = 0, $downloaded_size = 0, $upload_total = 0, $uploaded_size = 0)
    {
        if (version_compare(PHP_VERSION, '5.5.0') < 0) {
            $download_total  = $resource;
            $downloaded_size = $download_total;
            $upload_total    = $downloaded_size;
            $uploaded_size   = $upload_total;
            $resource        = null;
        }
        $args = array($resource, $download_total, $downloaded_size, $upload_total, $uploaded_size);
        call_user_func_array(self::$callback['progress'], array($args));
    }
}
