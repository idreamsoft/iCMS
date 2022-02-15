<?php
// namespace iPHP\core;

/**
 * Basic Security Filter Service
 * @author liuhui@2010-6-30 zzZero.L@2010-9-15
 * @status building
 * @from phpwind
 */
class Security
{
    public static function boot()
    {
        // Waf::filter();
    }
    public static function WAF($arr)
    {
        Waf::check($arr);
    }
    public static function makeHash($value = 0, $key = null)
    {
        return md5(sha1(md5($value) . md5($key)) . sha1($key));
    }
    public static $CSRF_TOKEN = null;
    public static function csrf_token($value = 0, $key = null)
    {
        $token = self::makeHash(iPHP_KEY, $key) . '_' . $value . '_' . time();
        $token = auth_encode($token, iPHP_COOKIE_TIME);
        $token = urlencode($token);
        self::$CSRF_TOKEN = $token;
        return $token;
    }
    public static function csrf_check($value = 0, $key = null)
    {
        $token = Request::param('CSRF_TOKEN');
        if ($token) {
            $md5  = self::makeHash(iPHP_KEY, $key);
            $time = time();
            $auth = auth_decode(urldecode($token));
            list($_md5, $_value, $_time) = explode('_', $auth);
            if (empty($auth) || $time - $_time > iPHP_COOKIE_TIME) {
                throw new sException("安全令牌错误,请刷新页面或者重新登录!", -90);
            } elseif ($md5 == $_md5 && $value == $_value) {
                return true;
            }
            throw new sException("安全令牌错误", -90);
        } else {
            if (isset($_GET['action']) || $_POST) {
                throw new sException("安全令牌丢失", -90);
            }
        }
    }
    /**
     * html转换输出
     * @param $param
     * @return string
     */
    public static function htmlEncode($param)
    {
        return trim(str_replace("\0", "&#0;", htmlspecialchars($param, ENT_QUOTES, 'utf-8')));
    }
    public static function htmlDecode($string)
    {
        if (is_array($string)) {
            $string = array_map(array(__CLASS__, 'htmlDecode'), $string);
        } else {
            $string = htmlspecialchars_decode($string);
            $string = str_replace(
                array('&#92;', '&#60;', '&#62;', '&#39;', '&#34;'),
                array('\\', '<', '>', "'", '"'),
                $string
            );
        }
        return $string;
    }

    public static function filterPath($text)
    {
        if (is_array($text)) {
            $text = array_map(array(__CLASS__, 'filterPath'), $text);
        } else {
            $text = str_replace('\\', '/', $text);
            $text = str_replace(iPHP_PATH, iPHP_PROTOCOL, $text);
            $pieces = explode('/', iPHP_PATH);
            $count = count($pieces);
            for ($i = 0; $i < ceil($count / 2); $i++) {
                $output = array_slice($pieces, 0, $count - $i);
                $path   = implode('/', $output);
                if (stripos($text, $path) !== false) {
                    $text = str_replace($path, iPHP_PROTOCOL, $text);
                }
            }
        }
        return $text;
    }

    /**
     * 通用多类型转换
     * @param $mixed
     * @param $isint
     * @param $istrim
     * @return mixture
     */
    public static function escapeChar($mixed, $isint = false, $istrim = false)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = self::escapeChar($value, $isint, $istrim);
            }
        } elseif ($isint) {
            $mixed = (int) $mixed;
        } elseif (!is_numeric($mixed) && ($istrim ? $mixed = trim($mixed) : $mixed) && $mixed) {
            $mixed = self::escapeStr($mixed);
        }
        return $mixed;
    }
    /**
     * 字符转换
     * @param $data
     * @return string
     */
    public static function escapeStr($data)
    {
        if (is_array($data)) {
            $data = array_map(array(__CLASS__, 'escapeStr'), $data);
        } else {
            $data = str_replace(array("\0", "%00", "\r"), '', $data);
            $data = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/', '', $data);
            //& => &amp;
            $data = preg_replace('/&(?!(#[0-9]+|[a-z]+);)/is', '&amp;', $data);
            //&amp;#xA9 => &#xA9;
            $data = preg_replace('/&amp;#x([a-fA-F0-9]{2,4});/', '&#x\\1', $data);
            // $data = str_replace(array('\"',"\'","\\\\"), array('&#34;','&#39;','&#92;'), $data);
            $data = str_replace(array("%3C", '<'), '&#60;', $data);
            $data = str_replace(array("%3E", '>'), '&#62;', $data);
            // $data = str_replace(array('"',"'"), array('&#34;','&#39;'), $data);
        }
        return $data;
    }

    /**
     * 变量转义
     * @param $array
     */
    public static function addslash(&$data)
    {
        if (is_object($data) || is_array($data)) {
            foreach ($data as $key => &$value) {
                self::addslash($value);
            }
        } else {
            $data = addslashes($data);
        }
        return $data;
    }

    public static function encoding($string, $code = 'UTF-8')
    {
        $encode = mb_detect_encoding($string, array("ASCII", "UTF-8", "GB2312", "GBK", "BIG5"));
        if (strtoupper($encode) != $code) {
            if (function_exists('mb_convert_encoding')) {
                $string = mb_convert_encoding($string, $code, $encode);
            } elseif (function_exists('iconv')) {
                $string = iconv($encode, $code, $string);
            }
        }
        return $string;
    }
    public static function safeStr($string)
    {
        return is_array($string) ?
            array_map(array(__CLASS__, 'safeStr'), $string) :
            preg_replace('/\W+/is', '', $string);
    }
    public static function secureToken($token)
    {
        $token = self::makeHash($token, iPHP_KEY);
        for ($i = 0; $i < 100; $i++) {
            $token = md5(sha1($token));
        }
        return $token;
    }
}
