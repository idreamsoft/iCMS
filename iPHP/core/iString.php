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

class iString
{
    public static function strlen($str, $charset = 'UTF-8')
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, $charset);
        } elseif (function_exists('iconv_strlen')) {
            return iconv_strlen($str, $charset);
        } else {
            if ($charset == 'UTF-8') {
                return strlen(utf8_decode($str));
            } else {
                return strlen($str);
            }
        }
    }
    public static function ltrim($str, $mask)
    {
        $len = strlen($mask);
        if (strpos($str, $mask) === 0) {
            $str = substr($str, $len);
        }
        return $str;
    }
    public static function rtrim($str, $mask)
    {
        $len = strlen($mask);
        $offset = strlen($str) - $len;
        if (strrpos($str, $mask, $offset) !== false) {
            $str = substr($str, 0, 0 - $len);
        }
        return $str;
    }
    public static function substr()
    {
        $args = func_get_args();
        $func = 'substr';
        if (function_exists('mb_substr')) {
            $func = 'mb_substr';
        } elseif (function_exists('iconv_substr')) {
            $func = 'iconv_substr';
        }
        // else {
        //     $func = [__CLASS__,'cut'];
        // }
        return call_user_func_array($func, $args);
    }
    public static function cut($str, $len, $end = null)
    {
        $s = self::substr($str, 0, $len);
        $l = self::strlen($str);
        $sl = self::strlen($s);
        if ($l > $sl && $end) {
            $s .= $end;
        }
        return $s;
    }
    public static function bitcut($str, $len, $end = '')
    {
        $len = $len * 2;
        //获取总的字节数
        $ll = strlen($str);
        //字节数
        $i = 0;
        //显示字节数
        $l = 0;
        //返回的字符串
        $s = $str;
        while ($i < $ll) {
            //获取字符的asscii
            $byte = ord($str[$i]);
            //如果是1字节的字符
            if ($byte < 0x80) {
                $l++;
                $i++;
            } elseif ($byte < 0xe0) {  //如果是2字节字符
                $l += 2;
                $i += 2;
            } elseif ($byte < 0xf0) {   //如果是3字节字符
                $l += 2;
                $i += 3;
            } else {  //其他，基本用不到
                $l += 2;
                $i += 4;
            }
            //如果显示字节达到所需长度
            if ($l >= $len) {
                //截取字符串
                $s = substr($str, 0, $i);
                //如果所需字符串字节数，小于原字符串字节数
                if ($i < $ll) {
                    //则加上省略符号
                    $s = $s . $end;
                    break;
                }
                //跳出字符串截取
                break;
            }
        }
        //返回所需字符串
        return $s;
    }
}
