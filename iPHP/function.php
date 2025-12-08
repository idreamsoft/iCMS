<?php

/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
function Lang()
{
    $args = func_get_args();
    $format = array_shift($args);
    if (is_array($args[0])) {
        $args = $args[0];
    }
    return Lang::get($format, $args);
}
function throwFalse($msg = 'FALSE', $code = '0')
{
    throw new FalseEx($msg, $code);
}

function count_days($formdate, $todate)
{
    return round(abs(strtotime($formdate) - strtotime($todate)) / 3600 / 24);
}

function bitscale($a)
{
    $a['th'] == 0 && $a['th'] = 9999;
    if ($a['w'] / $a['h'] > $a['tw'] / $a['th'] && $a['w'] > $a['tw']) {
        $a['h'] = ceil($a['h'] * ($a['tw'] / $a['w']));
        $a['w'] = $a['tw'];
    } elseif ($a['w'] / $a['h'] <= $a['tw'] / $a['th'] && $a['h'] > $a['th']) {
        $a['w'] = ceil($a['w'] * ($a['th'] / $a['h']));
        $a['h'] = $a['th'];
    }
    return $a;
}
function number($val)
{
    return preg_replace('~[^0-9]+~', '', $val);
}
function num10K($num)
{
    if ($num < 1000) {
        return $num;
    } else {
        return round($num / 1000, 1) . 'K';
    }
}
function format_time($time, $flag = 's')
{
    // 边界检查
    if (!is_numeric($time) || $time < 0) {
        return '';
    }

    // 单位定义：[描述, 秒数]
    $units = [
        ['years',   31556926],  // 平均每年秒数（考虑闰年）
        ['days',     86400],
        ['hours',     3600],
        ['minutes',      60],
        ['seconds',       1],
    ];

    // 单位别名映射
    $unit_map = [
        's'  => ['y', 'd', 'h', 'm', 's'],
        'l'  => ['year', 'day', 'hour', 'minute', 'second'],
        'cn' => ['年', '天', '小时', '分钟', '秒'],
    ];

    // 获取当前使用的单位
    $labels = $unit_map[$flag] ? $unit_map[$flag] : $unit_map['s'];

    $result = [];
    foreach ($units as [$name, $divisor]) {
        $value = (int) floor($time / $divisor);
        if ($value > 0) {
            $result[] = $value . $labels[array_search($name, array_column($units, 0))];
            $time %= $divisor;
        }
    }

    return implode(' ', $result);
}
function format_date($date, $format = 'Y-m-d H:i')
{
    $limit = time() - $date;
    if ($limit < 60) {
        return '刚刚';
    }
    if ($limit >= 60 && $limit < 3600) {
        return floor($limit / 60) . Lang('time:minutes_ago');
    }
    if ($limit >= 3600 && $limit < 86400) {
        return floor($limit / 3600) . Lang('time:hours_ago');
    }
    $week = 86400 * 7;
    if ($limit >= 86400 and $limit < $week) {
        return floor($limit / 86400) . Lang('time:days_ago');
    }
    $month = 86400 * 30;
    if ($limit >= $week and $limit < $month) {
        return floor($limit / $week) . Lang('time:weeks_ago');
    }
    $year = 86400 * 365;
    if ($limit >= $month and $limit < $year) {
        return floor($limit / $month) . Lang('time:months_ago');
    }

    if ($limit >= $year && $format) {
        return get_date($date, $format);
    } else {
        return '';
    }
}
function str2time($str = '0')
{
    if (is_numeric($str) && strlen($str) == 10) {
        return $str;
    }
    $correct = 0;
    $str or $str = 'now';
    $time = strtotime($str);
    (int) iPHP_TIME_CORRECT && $correct = (int) iPHP_TIME_CORRECT * 60;
    return $time + $correct;
}
// 格式化时间
function get_date($timestamp = 0, $format = '')
{
    $correct = 0;
    $format or $format = iPHP_DATE_FORMAT;
    $timestamp or $timestamp = time();
    (int) iPHP_TIME_CORRECT && $correct = (int) iPHP_TIME_CORRECT * 60;
    return date($format, $timestamp + $correct);
}

//截取HTML
function htmlcut($content, $maxlen = 300, $suffix = false)
{
    $content = preg_split('/(<[^>]+?>)/si', $content, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    $wordrows = 0;
    $outstr = '';
    $wordend = false;
    $beginTags = 0;
    $endTags = 0;
    foreach ($content as $value) {
        if (trim($value) == '') {
            continue;
        }

        if (strpos(";$value", '<') > 0) {
            if (!preg_match('/(<[^>]+?>)/si', $value) && iString::strlen($value) <= $maxlen) {
                $wordend = true;
                $outstr .= $value;
            }
            if ($wordend == false) {
                $outstr .= $value;
                if (!preg_match('/<img([^>]+?)>/is', $value) && !preg_match('/<param([^>]+?)>/is', $value) && !preg_match('/<!([^>]+?)>/is', $value) && !preg_match('/<br([^>]+?)>/is', $value) && !preg_match('/<hr([^>]+?)>/is', $value) && !preg_match("/<\/([^>]+?)>/is", $value)) {
                    $beginTags++;
                } else {
                    if (preg_match("/<\/([^>]+?)>/is", $value, $matches)) {
                        $endTags++;
                    }
                }
            } else {
                if (preg_match("/<\/([^>]+?)>/is", $value, $matches)) {
                    $endTags++;
                    $outstr .= $value;
                    if ($beginTags == $endTags && $wordend == true) {
                        break;
                    }
                } else {
                    if (!preg_match('/<img([^>]+?)>/is', $value) && !preg_match('/<param([^>]+?)>/is', $value) && !preg_match('/<!([^>]+?)>/is', $value) && !preg_match('/<[br|BR]([^>]+?)>/is', $value) && !preg_match('/<hr([^>]+?)>/is', $value) && !preg_match("/<\/([^>]+?)>/is", $value)) {
                        $beginTags++;
                        $outstr .= $value;
                    }
                }
            }
        } else {
            if (is_numeric($maxlen)) {
                $curLength = iString::strlen($value);
                $maxLength = $curLength + $wordrows;
                if ($wordend == false) {
                    if ($maxLength > $maxlen) {
                        $outstr .= iString::cut($value, $maxlen - $wordrows, false, 0);
                        $wordend = true;
                    } else {
                        $wordrows = $maxLength;
                        $outstr .= $value;
                    }
                }
            } else {
                if ($wordend == false) {
                    $outstr .= $value;
                }
            }
        }
    }
    while (preg_match("/<([^\/][^>]*?)><\/([^>]+?)>/is", $outstr)) {
        $outstr = preg_replace_callback("/<([^\/][^>]*?)><\/([^>]+?)>/is", 'strip_empty_html', $outstr);
    }
    if (strpos(';' . $outstr, '[html_') > 0) {
        $outstr = str_replace('[html_&lt;]', '<', $outstr);
        $outstr = str_replace('[html_&gt;]', '>', $outstr);
    }
    if ($suffix && iString::strlen($outstr) >= $maxlen) {
        $outstr .= '......';
    }
    return $outstr;
}
//去掉多余的空标签
function strip_empty_html($matches)
{
    $arr_tags1 = explode(' ', $matches[1]);
    if ($arr_tags1[0] == $matches[2]) {
        return '';
    } else {
        $matches[0] = str_replace('<', '[html_&lt;]', $matches[0]);
        $matches[0] = str_replace('>', '[html_&gt;]', $matches[0]);
        return $matches[0];
    }
}
/** Escape for HTML
 * @param string
 * @return string
 */
function h($string)
{
    return str_replace("\0", '&#0;', htmlspecialchars($string, ENT_QUOTES, 'utf-8'));
}
function sechtml($string)
{
    $search = ["/\s+/", "/<(\/?)(script|iframe|style|object|html|body|title|link|meta|\?|\%)([^>]*?)>/isU", "/(<[^>]*)on[a-zA-Z]+\s*=([^>]*>)/isU"];
    $replace = [' ', '&lt;\\1\\2\\3&gt;', '\\1\\2', ];
    $string = preg_replace($search, $replace, $string);
    return $string;
}

/**
 * HTML TO TEXT
 *
 * @param array|string $value
 * @return array|string
 */
function html2text($value)
{
    $value = is_array($value) ?
        array_map('html2text', $value) :
        preg_replace('/<[\/\!]*?[^<>]*?>/is', '', $value);

    return $value;
}
function html2js($value)
{
    $value = is_array($value) ?
        array_map('html2js', $value) :
        str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], $value);

    return $value;
}
function random($length, $numeric = 0)
{
    if ($numeric) {
        $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
    } else {
        $hash = '';
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
    }
    return $hash;
}

/**
 * 加密
 *
 * @param string $plaintext
 * @param string $key
 * @param integer $expiry
 * @return string
 */
function encrypt($plaintext, $key = null, $expiry = -1)
{
    is_array($plaintext) && $plaintext = json_encode($plaintext, JSON_UNESCAPED_UNICODE);
    is_null($key) && $key = md5(iPHP_KEY);
    $iv = openssl_random_pseudo_bytes(16);
    $ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext, $key, true);
    $expire_time = $expiry < 0 ? $expiry : time() + $expiry;
    $payload = json_encode(['expire_time' => $expire_time, 'ciphertext' => urlsafe_b64encode($iv . $hmac . $ciphertext)]);
    return urlsafe_b64encode($payload);
}
/**
 * 解密
 *
 * @param string $plaintext
 * @param string $key
 * @return string
 */
function decrypt($ciphertext, $key = null)
{
    is_null($key) && $key = md5(iPHP_KEY);
    $payload = base64_decode($ciphertext);
    $payload = json_decode($payload, true);
    if (!$payload) {
        return null;
    }
    $expire_time = $payload['expire_time'];
    if ($expire_time > 0 && time() > $expire_time) {
        return null;
    }
    $ciphertext = urlsafe_b64decode($payload['ciphertext']);
    $iv = substr($ciphertext, 0, 16);
    $hmac = substr($ciphertext, 16, 32);
    $ciphertext = substr($ciphertext, 48);
    $expected_hmac = hash_hmac('sha256', $ciphertext, $key, true);
    if ($hmac !== $expected_hmac) {
        return null;
    }
    $plaintext = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    $array = json_decode($plaintext, true);
    return is_array($array) ? $array : $plaintext;
}
function auth_encode($string, $expiry = 0)
{
    return authcode($string, 'ENCODE', null, $expiry);
}
function auth_decode($string)
{
    return authcode($string);
}
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
{
    if (empty($string)) {
        return;
    }

    is_array($string) && $string = json_encode($string);
    $ckey_length = 8;
    $key = md5($key ? $key : iPHP_KEY);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);

    $string = $operation == 'DECODE' ? urlsafe_b64decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? (int) $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);

    $result = '';
    $box = range(0, 255);

    $rndkey = [];
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }

    if ($operation == 'DECODE') {
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc . urlsafe_b64encode($result);
    }
}
function urlsafe_b64decode($input)
{
    $remainder = strlen($input) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $input .= str_repeat('=', $padlen);
    }
    return base64_decode(strtr($input, '-_!', '+/%'));
}

function urlsafe_b64encode($input)
{
    return str_replace('=', '', strtr(base64_encode($input), '+/%', '-_!'));
}

function array_diff_values(array $N, array $O)
{
    $diff['+'] = array_diff($N, $O);
    $diff['-'] = array_diff($O, $N);
    return $diff;
}

function get_dir_name($path = null)
{
    if (!empty($path)) {
        if (strpos($path, '\\') !== false) {
            return substr($path, 0, strrpos($path, '\\')) . '/';
        } elseif (strpos($path, '/') !== false) {
            return substr($path, 0, strrpos($path, '/')) . '/';
        }
    }
    return './';
}
function get_unicode($string)
{
    if (empty($string)) {
        return;
    }

    $array = (array) $string;
    $json = json_encode($array);
    return str_replace(['["', '"]'], '', $json);
}

function get_bytes($val)
{
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $val = (int)$val;
    switch ($last) {
        case 'g':
            $val *= 1024;
            // no break
        case 'm':
            $val *= 1024;
            // no break
        case 'k':
            $val *= 1024;
    }
    return $val;
}
function unicode_convert_encoding($code)
{
    return mb_convert_encoding(pack('H*', $code[1]), 'UTF-8', 'UCS-2BE');
}
function unicode_encode($value)
{
    return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'unicode_convert_encoding', $value);
}
function cnjson_encode($array)
{
    $json = json_encode($array);
    $json = unicode_encode($json);
    return $json;
}
function parse_url_qs($query)
{
    $output = [];
    $query && parse_str($query, $output);
    return $output;
}
/**
 * 判断是否为多维数组
 *
 * @param array $arr
 * @return boolean
 */
function is_multi_array($arr)
{
    return is_array($arr) && count(array_filter($arr, 'is_array')) > 0;
}

/**
 * 将a.b.c,[a][b][c] 等字符串，转换成多维数组
 *
 * @param string $string 字符串
 * @param mixed $value 值
 * @param string $s 分割符
 * @param array $array 引用返回
 * @return array
 */
// function make_multi_array($string, $value = null, $s = '.', &$array = [])
// {
//     $keys = explode($s, preg_replace('/\=(\w+)$/', '', $string));
//     $ref = &$array;
//     foreach ($keys as $key) {
//         $s === '[' && $key = rtrim($key, ']');
//         empty($key) && $key = 0;
//         $ref = &$ref[$key];
//     }
//     $ref = $value;
//     return $array;
// }
function make_multi_array($string, $value)
{
    $matches = [];
    preg_match_all('/(?:\[([^\]]+)\]|(\w+))/', $string, $matches);
    $parts = $matches[0];
    $result = [];

    $current = &$result;
    foreach ($parts as $part) {
        $key = preg_replace('/[^\w\-_]/', '', $part);
        if (isset($current[$key])) {
            $current = &$current[$key];
        } else {
            $current[$key] = [];
            $current = &$current[$key];
        }
    }

    $current = $value;

    return $result;
}
/**
 * php 递归合并多维数组，多值项只保留一个最后加入的值 该函数可以有多个参数
 *
 * @return void
 */
function array_merge_recursive_unique()
{
    $arrays = func_get_args();
    $merged = [];

    foreach ($arrays as $array) {
        foreach ($array as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = array_merge_recursive_unique($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
    }

    return $merged;
}
/**
 * 判断数组的值是否为纯数字
 *
 * @param array $array
 * @return boolean
 */
function isNumArr($array)
{
    $arr = array_filter($array, function ($element) {
        return !is_numeric($element);
    });
    return empty($arr);
}
/**
 * 判断数组的键是否为纯数字
 * @param array $array
 * @return boolean
 */
function isNumKeys($array)
{
    // 检查输入是否为数组
    if (!is_array($array)) {
        return false;
    }
    // 使用 array_filter 和 array_map 简化代码
    $keys = array_keys($array);
    $numericKeys = array_filter($keys, 'is_numeric');
    // 检查所有键是否都是数字
    return count($numericKeys) === count($keys);
}
/**
 * 自定义数组合并
 * 同键名且值为字符串的覆盖，
 * 同键名值为数组的合并
 *
 * @param array $array1
 * @param array $array2
 * @return array
 */
function custom_array_merge_recursive()
{
    $arrays = func_get_args();
    $base = array_shift($arrays);
    foreach ($arrays as $array) {
        if (!is_array($array)) {
            continue;
        }

        foreach ($array as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = custom_array_merge_recursive($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
    }
    return $base;
}
/**
 * 自定义数组合并
 * 同键名且值为字符串的保留新的值，同键名值为数组的合并
 *
 * @param array $array1
 * @param array $array2
 * @return array
 */
function custom_array_merge_recursive2($array1, $array2)
{
    foreach ($array2 as $key => $value) {
        if (isset($array1[$key])) {
            if (is_array($array1[$key]) && is_array($value)) {
                $array1[$key] = custom_array_merge_recursive2($array1[$key], $value);
            } elseif (!is_array($array1[$key]) && !is_array($value)) {
                // If both values are strings, retain the original value
                continue;
            } else {
                // Otherwise, merge the values
                $array1[$key] = array_merge((array)$array1[$key], (array)$value);
            }
        } else {
            $array1[$key] = $value;
        }
    }
    return $array1;
}
/**
 * 递归打印多维数组
 *
 * @param array $arrays
 * @param string $callfunc
 * @param string $key
 * @return void
*/
function array_stack($arrays, $callfunc, $key = 'children')
{
    $stack = [[$arrays, 0]];
    while (!empty($stack)) {
        list($array, $level) = array_pop($stack);

        echo str_repeat("\t", $level) . $array['name'] . "\n";
        if (isset($node[$key])) {
            foreach (array_reverse($node[$key]) as $child) {
                array_push($stack, [$child, $level + 1]);
            }
        }
    }
}
function array_plus(array $left, array $right)
{
    foreach ($right as $key => $value) {
        $left[$key] = $value;
    }
    return $left;
}
function array_plus_recursive(array $left, array $right)
{
    foreach ($right as $key => $value) {
        if (isset($left[$key]) && is_array($left[$key]) && is_array($value)) {
            // 如果 $left 和 $right 的值都是数组，则递归合并
            $left[$key] = array_plus_recursive($left[$key], $value);
        } else {
            // 否则直接覆盖或添加
            $left[$key] = $value;
        }
    }
    return $left;
}
function sortLen($a, $b)
{
    $al = strlen($a);
    $bl = strlen($b);
    if ($al == $bl) {
        return  0;
    }
    return ($al > $bl) ? -1 : 1;
}
// 降序排序，兼容 PHP 5.6
function sortKey(&$variable, $key = 'sort')
{
    // 使用 array_walk 来遍历数组，初始化每个项的 key 值
    $idx = 0;
    array_walk($variable, function (&$item) use (&$idx, $key) {
        if (!isset($item[$key])) {
            $item[$key] = $idx++;
        }
    });

    // 使用 uasort 来排序
    uasort($variable, function ($a, $b) use ($key) {
        if ($a[$key] == $b[$key]) {
            return 0;
        }
        return ($a[$key] > $b[$key]) ? 1 : -1; // 使用传统的比较方式
    });
}

function unset_array(&$vars, $keys)
{
    foreach ($keys as $k) {
        unset($vars[$k]);
    }
}
/**
  * 递归对多维数组的键名进行排序
  * @param array $array 要排序的数组
  * @return array 排序后的数组
  */
function array_recursive_ksort(&$array)
{
    if (!is_array($array)) {
        return $array;
    }

    ksort($array);

    foreach ($array as &$value) {
        if (is_array($value)) {
            array_recursive_ksort($value);
        }
    }

    return $array;
}
/**
 * 二维数组按给定键名过滤
 *
 * @param array &$array 数组[引用]
 * @param array|string $keys
 * @param boolean $flag true 保留字段|false 移除字段
 * @return array
 */
function pluck(array &$array, $keys, $flag = false)
{
    $array = array_map(function ($item) use ($keys, $flag) {
        return array_filter_keys($item, $keys, $flag);
    }, $array);
    return $array;
}

/**
 * 数组按给定键名过滤
 *
 * @param array $array 数组
 * @param array|string $keys
 * @param boolean $flag true 保留字段|false 移除字段
 * @return array
 */
function array_filter_keys($array, $keys = null, $flag = true)
{
    if (!is_array($array)) {
        return $array;
    }
    if ($keys === null || empty($keys)) {
        return $array;
    }
    if (is_string($keys)) {
        $keys = explode(',', $keys);
    }
    $subKeys = [];
    foreach ($keys as $index => $key) {
        if (strpos($key, '.') !== false) {
            list($skey, $subkey) = explode('.', $key, 2);
            $subKeys[$skey][] = $subkey;
            $keys[$index] = $skey;
        }
    }
    // 过滤含有子键的主键
    if ($subKeys) {
        foreach ($subKeys as $key => $value) {
            $array[$key] = array_filter_keys($array[$key], $value);
        }
    }

    $keys = array_flip($keys);
    if ($flag) {
        //过滤出仅包含 $keys 两个成员的数组
        return array_intersect_key($array, $keys);
    } else {
        // 过滤出不包含 $keys 成员的数组
        return array_diff_key($array, $keys);
    }
}
function is_not_null($val)
{
    return !is_null($val);
}
function array_map_func($array, $func)
{
    return array_map(function ($item) use ($func) {
        if (is_array($item)) {
            $item = array_map_func($item, $func);
        } else {
            $item = call_user_func_array($func, [$item]);
        }
        return $item;
    }, $array);
}
// function array_walk_item($array, $func)
// {
//     return array_walk($array,function ($item, $key) use ($func) {
//         if (is_array($item)) {
//             $item = array_walk($item, $func);
//         } else {
//             $item = call_user_func_array($func, [$item]);
//         }
//         return $item;
//     }, $array);
// }
if (!function_exists('array_key_first')) {
    function array_key_first(array $arr)
    {
        foreach ($arr as $key => $unused) {
            return $key;
        }
        return null;
    }
}
function create_array($keys, $value = null)
{
    // 从最内层开始构建数组
    $array = [];
    $current = &$array; // 使用引用以便逐级构建

    // 遍历键数组
    foreach ($keys as $key) {
        // 如果键不存在，则创建一个空数组
        if (!isset($current[$key])) {
            $current[$key] = [];
        }
        // 移动到下一层
        $current = &$current[$key];
    }

    // 如果需要，可以设置最内层的值
    if ($value !== null) {
        $current = $value;
    }

    return $array;
}

function json_param($value)
{
    if (empty($value)) {
        return '[]';
    }

    if (!is_array($value)) {
        $value = [$value];
    }

    $json = json_encode($value, JSON_UNESCAPED_UNICODE);
    $json = str_replace('"', '&quot;', $json);
    return $json;
}
function short_md5($input)
{
    return substr(md5($input), 8, 16);
}
