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
    public static function bootstrap()
    {
        // Waf::filter();
    }

    public static function WAF($arr)
    {
        Waf::check($arr);
    }

    public static function makeHash($value = 0, $key = '')
    {
        is_null($key) && $key = '';
        is_null($value) && $value = '';
        return md5(sha1(md5($value) . md5($key)) . sha1($key));
    }
    public static $CSRF_TOKEN = null;

    public static function csrf_token($value = 0, $key = null)
    {
        $token = self::makeHash(iPHP_KEY, $key) . '_' . $value . '_' . time();
        $token = Crypt::encrypt($token, iPHP_COOKIE_TIME);
        $token = urlencode($token);
        self::$CSRF_TOKEN = $token;
        return $token;
    }

    public static function csrf_check($value = 0, $key = null)
    {
        $token = Request::param('CSRF_TOKEN');
        if ($token) {
            $md5 = self::makeHash(iPHP_KEY, $key);
            $time = time();
            $_token = Crypt::decrypt(urldecode($token));
            if ($_token) {
                list($_md5, $_value, $_time) = explode('_', $_token);
                if ($time - $_time > iPHP_COOKIE_TIME) {
                    throw new sException('errors:token:expire', 'security');
                } elseif ($md5 == $_md5 && $value == $_value) {
                    return true;
                }
            }
            throw new sException('errors:token:error', 'security');
        } else {
            if ($_POST) {
                throw new sException('errors:token:missing', 'security');
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
        return trim(str_replace("\0", '&#0;', htmlspecialchars($param, ENT_QUOTES, 'utf-8')));
    }

    public static function htmlDecode($string)
    {
        if (is_array($string)) {
            $string = array_map([__CLASS__, 'htmlDecode'], $string);
        } else {
            $string = htmlspecialchars_decode($string);
            $string = str_replace(
                ['&#92;', '&#60;', '&#62;', '&#39;', '&#34;'],
                ['\\', '<', '>', "'", '"'],
                $string
            );
        }
        return $string;
    }

    public static function filterPath($text)
    {
        if (is_array($text)) {
            $text = array_map([__CLASS__, 'filterPath'], $text);
        } else {
            if(!is_object($text)){
                $winPath = strtr(iPHP_APP_PATH, '/', '\\'); //window
                $text && $text = str_replace(iPHP_APP_PATH, iPHP_PROTOCOL, $text);
                $text && $text = str_replace($winPath, iPHP_PROTOCOL, $text);
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
            $data = array_map([__CLASS__, 'escapeStr'], $data);
        } elseif (isset($data)) {
            $data = str_replace(["\0", '%00', "\r"], '', $data);
            $data = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/', '', $data);
            //& => &amp;
            $data = preg_replace('/&(?!(#[0-9]+|[a-z]+);)/is', '&amp;', $data);
            //&amp;#xA9 => &#xA9;
            $data = preg_replace('/&amp;#x([a-fA-F0-9]{2,4});/', '&#x\\1', $data);
            // $data = str_replace(array('\"',"\'","\\\\"), array('&#34;','&#39;','&#92;'), $data);
            $data = str_replace(['%3C', '<'], '&#60;', $data);
            $data = str_replace(['%3E', '>'], '&#62;', $data);
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
            $data && $data = addslashes($data);
        }
        return $data;
    }

    public static function encoding($string, $code = 'UTF-8')
    {
        $encode = mb_detect_encoding($string, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5']);
        if (strtoupper($encode) != $code) {
            if (function_exists('mb_convert_encoding')) {
                $string = mb_convert_encoding($string, $code, $encode);
            } elseif (function_exists('iconv')) {
                $string = iconv($encode, $code, $string);
            }
        }
        return $string;
    }

    public static function safePath($path)
    {
        $_path = $path;
        if (strpos($_path, '..') !== false) {
            return false;
        }
        // 解码多次编码，直到路径不再改变为止
        while (urldecode($_path) !== $_path) {
            $_path = urldecode($_path);
        }
        $_path = self::escapeStr($_path);
        // 移除路径中的双斜杠，防止目录遍历
        $_path = str_replace('//', '/', $_path);

        // 规范化路径，确保使用正斜杠，并移除相对路径中的 ".."
        $_path = str_replace('\\', '/', $_path);
        $parts = explode('/', $_path);
        $new_parts = [];

        foreach ($parts as $part) {
            if ($part === '..') {
                // 如果遇到 ".."，则从新的路径中移除一个目录
                array_pop($new_parts);
            } elseif ($part !== '.') {
                // 忽略 "."，并将有效目录添加到新的路径中
                $new_parts[] = $part;
            }
        }

        // 重新构建安全的路径
        $new_path = implode('/', $new_parts);

        if ($path != $new_path) {
            return false;
        }
        // $path = $new_path;
        return $new_path;
    }

    public static function safeStr($string, $fix = '')
    {
        if (is_array($string)) {
            foreach ($string as $key => &$value) {
                $value = self::safeStr($value, $fix);
            }
        } else {
            $pattern = '\w';
            $fix && $pattern .= preg_quote($fix, '/');
            $string && $string = preg_replace('/[^' . $pattern . ']+/is', '', $string);
        }
        return $string;
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
