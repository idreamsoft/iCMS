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
        if (is_null($str)) {
            return 0;
        }
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, $charset);
        } elseif (function_exists('iconv_strlen')) {
            return iconv_strlen($str, $charset);
        } else {
            if ($charset == 'UTF-8') {
                if (function_exists('iconv')) {
                    $str = iconv('UTF-8', 'ISO-8859-1', $str);
                } elseif (function_exists('mb_convert_encoding')) {
                    $str = mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
                }
            }
            return strlen($str);
        }
    }

    public static function ltrim($str, $mask)
    {
        $len = strlen($mask);
        if ($str && $mask && strpos($str, $mask) === 0) {
            $str = substr($str, $len);
        }
        return $str;
    }

    public static function rtrim($str, $mask)
    {
        $len = strlen($mask);
        $offset = strlen($str) - $len;
        if ($str && $mask && strrpos($str, $mask, $offset) !== false) {
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
        if (is_null($args[0])) {
            return;
        }
        return call_user_func_array($func, $args);
    }

    public static function cut($str, $len, $end = null)
    {
        if (is_null($str) || $len <= 0) {
            return '';
        }

        $result = '';
        $currentLen = 0;
        $strLen = self::strlen($str);
        $i = 0;

        while ($i < $strLen && $currentLen < $len) {
            $char = self::substr($str, $i, 1);

            // 判断是否为中文字符（包括中文标点符号）
            if (preg_match('/[\x{4e00}-\x{9fff}\x{3000}-\x{303f}\x{ff00}-\x{ffef}]/u', $char)) {
                // 中文字符算1个长度单位
                $charLen = 1;
            } else {
                // 英文字符算0.5个长度单位（2个英文字符算1个长度单位）
                $charLen = 0.5;
            }

            // 检查添加当前字符后是否会超出长度限制
            if ($currentLen + $charLen > $len) {
                break;
            }

            $result .= $char;
            $currentLen += $charLen;
            $i++;
        }

        // 如果原字符串被截断了，添加结束符
        if ($i < $strLen && $end) {
            $result .= $end;
        }

        return $result;
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
