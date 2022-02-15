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

if (!function_exists('_object')) {
    function _object()
    {
        return new stdClass;
    }
}

if (!function_exists('gc_collect_cycles')) {
    function gc_collect_cycles()
    {
        return false;
    }
}
if (!function_exists('json_last_error_msg')) {
    function json_last_error_msg()
    {
        switch (json_last_error()) {
            case  JSON_ERROR_NONE:
                break;
            case JSON_ERROR_DEPTH:
                $msg = 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $msg = 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $msg = 'Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $msg = 'Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $msg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
        }
        return $msg;
    }
}
# for PHP < 5.5
# AND it works with arrayObject AND array of objects
# miguelfzarth at gmail dot com 18-Aug-2016 11:02
if (!function_exists('array_column')) {
    function array_column($array, $columnKey, $indexKey = null)
    {
        $result = array();
        foreach ($array as $subArray) {
            if (is_null($indexKey) && array_key_exists($columnKey, $subArray)) {
                $result[] = is_object($subArray) ? $subArray->$columnKey : $subArray[$columnKey];
            } elseif (array_key_exists($indexKey, $subArray)) {
                if (is_null($columnKey)) {
                    $index = is_object($subArray) ? $subArray->$indexKey : $subArray[$indexKey];
                    $result[$index] = $subArray;
                } elseif (array_key_exists($columnKey, $subArray)) {
                    $index = is_object($subArray) ? $subArray->$indexKey : $subArray[$indexKey];
                    $result[$index] = is_object($subArray) ? $subArray->$columnKey : $subArray[$columnKey];
                }
            }
        }
        return $result;
    }
}
if (!function_exists('cal_days_in_month')) {
    function cal_days_in_month($cal, $month, $year)
    {
        return date('t', mktime(0, 0, 0, $month + 1, 0, $year));
    }
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
function _each(&$array)
{
    $res = array();
    $key = key($array);
    if ($key !== null) {
        next($array);
        $res[1] = $res['value'] = $array[$key];
        $res[0] = $res['key'] = $key;
    } else {
        $res = false;
    }
    return $res;
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
