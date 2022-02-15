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
function lang($text){
    print $text;
}
function throwFalse($msg = "FALSE", $code = "0")
{
    throw new FalseEx($msg, $code);
}
function sException($text, $code = 0)
{
    throw new sException($text, $code);
}
function count_days($formdate, $todate)
{
    return round(abs(strtotime($formdate) - strtotime($todate)) / 3600 / 24);
}
function sortKey(&$variable, $key = 'sort')
{
    $idx = 0;
    foreach ($variable as $vkey => &$value) {
        if (isset($value[$key])) {
            $idx += $value[$key];
        } else {
            $value[$key] = $idx;
        }
        $idx++;
    }
    uasort($variable, function ($a, $b) use ($key) {
        if ($a[$key]  ==  $b[$key]) {
            return  0;
        }
        return ($a[$key]  <  $b[$key]) ? -1  :  1;
    });
}
function bitscale($a)
{
    $a['th'] == 0 && $a['th'] = 9999;
    if ($a['w'] / $a['h'] > $a['tw'] / $a['th']  && $a['w'] > $a['tw']) {
        $a['h'] = ceil($a['h'] * ($a['tw'] / $a['w']));
        $a['w'] = $a['tw'];
    } else if ($a['w'] / $a['h'] <= $a['tw'] / $a['th'] && $a['h'] > $a['th']) {
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
    if ($num < 10000) {
        return $num;
    } else {
        return round($num / 10000, 1) . 'K';
    }
}
function format_time($time, $flag = 's')
{
    $value = array(
        "years" => 0, "days" => 0, "hours" => 0,
        "minutes" => 0, "seconds" => 0,
    );
    if ($time >= 31556926) {
        $value["years"] = floor($time / 31556926);
        $time = ($time % 31556926);
    }
    if ($time >= 86400) {
        $value["days"] = floor($time / 86400);
        $time = ($time % 86400);
    }
    if ($time >= 3600) {
        $value["hours"] = floor($time / 3600);
        $time = ($time % 3600);
    }
    if ($time >= 60) {
        $value["minutes"] = floor($time / 60);
        $time = ($time % 60);
    }
    $value["seconds"] = floor($time);

    $unit_map = array(
        's' => array('d', 'h', 'm', 's'),
        'l' => array('days', 'hours', 'minutes', 'seconds'),
        'cn' => array('天', '小时', '分钟', '秒'),
    );
    $t = '';
    $unit = $unit_map[$flag];
    $value["days"]   && $t .= $value["days"] . $unit[0] . ' ';
    $value["hours"]  && $t .= $value["hours"] . $unit[1] . ' ';
    $value["minutes"] && $t .= $value["minutes"] . $unit[2] . ' ';
    $value["seconds"] && $t .= $value["seconds"] . $unit[3];

    return $t;
}
function format_date($date, $format = 'Y-m-d H:i')
{
    $limit = time() - $date;
    if ($limit < 60) {
        return '刚刚';
    }
    if ($limit >= 60 && $limit < 3600) {
        return floor($limit / 60) . '分钟之前';
    }
    if ($limit >= 3600 && $limit < 86400) {
        return floor($limit / 3600) . '小时之前';
    }
    if ($limit >= 86400 and $limit < 259200) {
        return floor($limit / 86400) . '天之前';
    }
    if ($limit >= 259200 and $format) {
        return get_date($date, $format);
    } else {
        return '';
    }
}
function str2time($str = "0")
{
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
    $format or $format            = iPHP_DATE_FORMAT;
    $timestamp or $timestamp      = time();
    (int) iPHP_TIME_CORRECT && $correct = (int) iPHP_TIME_CORRECT * 60;
    return date($format, $timestamp + $correct);
}


//截取HTML
function htmlcut($content, $maxlen = 300, $suffix = FALSE)
{
    $content   = preg_split("/(<[^>]+?>)/si", $content, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    $wordrows  = 0;
    $outstr    = "";
    $wordend   = false;
    $beginTags = 0;
    $endTags   = 0;
    foreach ($content as $value) {
        if (trim($value) == "") continue;

        if (strpos(";$value", "<") > 0) {
            if (!preg_match("/(<[^>]+?>)/si", $value) && iString::strlen($value) <= $maxlen) {
                $wordend = true;
                $outstr .= $value;
            }
            if ($wordend == false) {
                $outstr .= $value;
                if (!preg_match("/<img([^>]+?)>/is", $value) && !preg_match("/<param([^>]+?)>/is", $value) && !preg_match("/<!([^>]+?)>/is", $value) && !preg_match("/<br([^>]+?)>/is", $value) && !preg_match("/<hr([^>]+?)>/is", $value) && !preg_match("/<\/([^>]+?)>/is", $value)) {
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
                    if ($beginTags == $endTags && $wordend == true) break;
                } else {
                    if (!preg_match("/<img([^>]+?)>/is", $value) && !preg_match("/<param([^>]+?)>/is", $value) && !preg_match("/<!([^>]+?)>/is", $value) && !preg_match("/<[br|BR]([^>]+?)>/is", $value) && !preg_match("/<hr([^>]+?)>/is", $value) && !preg_match("/<\/([^>]+?)>/is", $value)) {
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
                        $outstr .= iString::cut($value, $maxlen - $wordrows, FALSE, 0);
                        $wordend = true;
                    } else {
                        $wordrows = $maxLength;
                        $outstr .= $value;
                    }
                }
            } else {
                if ($wordend == false) $outstr .= $value;
            }
        }
    }
    while (preg_match("/<([^\/][^>]*?)><\/([^>]+?)>/is", $outstr)) {
        $outstr = preg_replace_callback("/<([^\/][^>]*?)><\/([^>]+?)>/is", "strip_empty_html", $outstr);
    }
    if (strpos(";" . $outstr, "[html_") > 0) {
        $outstr = str_replace("[html_&lt;]", "<", $outstr);
        $outstr = str_replace("[html_&gt;]", ">", $outstr);
    }
    if ($suffix && iString::strlen($outstr) >= $maxlen) $outstr .= "......";
    return $outstr;
}
//去掉多余的空标签
function strip_empty_html($matches)
{
    $arr_tags1 = explode(" ", $matches[1]);
    if ($arr_tags1[0] == $matches[2]) {
        return "";
    } else {
        $matches[0] = str_replace("<", "[html_&lt;]", $matches[0]);
        $matches[0] = str_replace(">", "[html_&gt;]", $matches[0]);
        return $matches[0];
    }
}
/** Escape for HTML
 * @param string
 * @return string
 */
function h($string)
{
    return str_replace("\0", "&#0;", htmlspecialchars($string, ENT_QUOTES, 'utf-8'));
}
function sechtml($string)
{
    $search  = array("/\s+/", "/<(\/?)(script|iframe|style|object|html|body|title|link|meta|\?|\%)([^>]*?)>/isU", "/(<[^>]*)on[a-zA-Z]+\s*=([^>]*>)/isU");
    $replace = array(" ", "&lt;\\1\\2\\3&gt;", "\\1\\2",);
    $string  = preg_replace($search, $replace, $string);
    return $string;
}
//HTML TO TEXT
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
        str_replace(array("\\", "\"", "\n", "\r"), array("\\\\", "\\\"", "\\n", "\\r"), $value);

    return $value;
}

if (!function_exists('htmlspecialchars_decode')) {
    function htmlspecialchars_decode($value)
    {
        $value = is_array($value) ?
            array_map('htmlspecialchars_decode', $value) :
            str_replace(array('&amp;', '&#039;', '&quot;', '&lt;', '&gt;'), array('&', '\'', '\"', '<', '>'), $value);

        return $value;
    }
}
function stripslashes_deep($value)
{
    $value = is_array($value) ?
        array_map('stripslashes_deep', $value) :
        stripslashes($value);

    return $value;
}

function random($length, $numeric = 0)
{
    if ($numeric) {
        $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
    } else {
        $hash  = '';
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
        $max   = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
    }
    return $hash;
}
function get_user_dir($uid, $dir = 'avatar')
{
    $nuid = abs(intval($uid));
    $nuid = sprintf("%08d", $nuid);
    $dir1 = substr($nuid, 0, 3);
    $dir2 = substr($nuid, 3, 2);
    $path = $dir . '/' . $dir1 . '/' . $dir2;
    return $path;
}
function get_user_pic($uid, $size = 0, $dir = 'avatar')
{
    $path = get_user_dir($uid, $dir) . '/' . $uid . ".jpg";
    if ($size) {
        $path .= '_' . $size . 'x' . $size . '.jpg';
    }
    return $path;
}

function auth_encode($string, $expiry = 0)
{
    return authcode($string, "ENCODE", null, $expiry);
}
function auth_decode($string)
{
    return authcode($string);
}
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
{
    if (empty($string)) return;
    
    is_array($string) && $string = json_encode($string);
    $ckey_length   = 8;
    $key           = md5($key ? $key : iPHP_KEY);
    $keya          = md5(substr($key, 0, 16));
    $keyb          = md5(substr($key, 16, 16));
    $keyc          = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

    $cryptkey      = $keya . md5($keya . $keyc);
    $key_length    = strlen($cryptkey);

    $string        = $operation == 'DECODE' ? urlsafe_b64decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? (int) $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);

    $result        = '';
    $box           = range(0, 255);

    $rndkey        = array();
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for ($j = $i = 0; $i < 256; $i++) {
        $j       = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp     = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a       = ($a + 1) % 256;
        $j       = ($j + $box[$a]) % 256;
        $tmp     = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result  .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
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

function str_exists($string, $find)
{
    return !(strpos($string, $find) === FALSE);
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
    if (empty($string)) return;

    $array = (array) $string;
    $json  = json_encode($array);
    return str_replace(array('["', '"]'), '', $json);
}


function select_fields($array, $fields = '', $map = false)
{
    $fields_array = explode(',', $fields);
    foreach ($fields_array as $key => $field) {
        $rs[$field] = $array[$field];
    }
    return $rs;
}
function get_bytes($val)
{
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $val = (int)$val;
    switch ($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}
function unicode_convert_encoding($code)
{
    return mb_convert_encoding(pack("H*", $code[1]), "UTF-8", "UCS-2BE");
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
    parse_str($query, $output);
    return $output;
}
//a.b.c字符串 转换成多维数组
function make_multi_array($string, $value = null, $s = '.')
{
    $a_array = explode($s, $string);
    krsort($a_array);
    $a = $value;
    foreach ($a_array as $k => $v) {
        $s === '[' && $v = rtrim($v, ']');
        empty($v) && $v = 0;
        $a = array($v => $a);
        count($a) > 1 && array_shift($a);
    }
    return $a;
}

function unset_array(&$vars, $keys)
{
    foreach ($keys as $k) {
        unset($vars[$k]);
    }
}
//二维数组按给定键名过滤
function pluck(&$resource, $keys, $remove = false)
{
    is_array($keys) or $keys = explode(',', $keys);
    foreach ((array)$resource as $key => $value) {
        foreach ($value as $k => $v) {
            if (in_array($k, $keys)) {
                if ($remove) unset($resource[$key][$k]);
            } else {
                if (!$remove) unset($resource[$key][$k]);
            }
        }
    }
}

//数组按给定键名过滤
//$flag true 保留字段 false 移除字段
function array_filter_keys($array, $keys = null, $flag = true)
{
    if ($keys) {
        is_array($keys) or $keys = explode(',', $keys);
        if ($array) foreach ($array as $field => $value) {
            if (in_array($field, $keys)) {
                if (!$flag) { //仅保留移除$keys设置的字段，其它保留
                    unset($array[$field]);
                }
            } else {
                if ($flag) { //仅保留$keys设置的字段,其它移除
                    unset($array[$field]);
                }
            }
        }
    }
    return $array;
}
function is_not_null($val)
{
    return !is_null($val);
}
