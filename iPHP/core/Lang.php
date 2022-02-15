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
class Lang
{
    public static $CACHE = array();
    public static function get($keys = '', $params = null)
    {
        if (empty($keys)) {
            return false;
        }
        if (is_array($keys)) {
            $params = $keys;
            $keys = array_shift($params);
        }
        $keyArray = explode(':', $keys);
        $app = array_shift($keyArray);
        if ($app != iPHP_APP) {
            $langs = self::load($app);
            $msg  = self::getMsg($langs, $keyArray);
        }
        if (empty($msg)) {
            $langs = (array)self::$CACHE[iPHP_APP];
            if (empty($langs)) {
                $json = file_get_contents(iPHP_CONFIG_DIR . '/lang.json');
                $langs = json_decode($json, true);
                self::$CACHE[iPHP_APP] = $langs;
            }
            $msg = self::getMsg($langs, $keyArray);
        }

        if (empty($msg)) {
            return $keys;
        }
        if ($params) {
            preg_match_all('/\{(\w+)\}/i', $msg, $matches);
            if ($matches[1]) {
                foreach ($matches[1] as $idx => $key) {
                    $key = strtolower($key);
                    $value = $params[$key];
                    is_null($value) or $msg = str_replace($matches[0][$idx], $value, $msg);
                }
            } else {
                $count = count($params);
                $length = substr_count($msg, '%s');
                if ($count > $length) {
                    $params = array_slice($params, 0, $length);
                } elseif ($count < $length) {
                    $fill = array_fill($count, ($length - $count), '?');
                    $params = array_merge($params, $fill);
                }
                $params = array_merge([$msg], $params);
                $msg = call_user_func_array('sprintf', $params);
            }
        }
        return $msg;
    }

    public static function load($app = '')
    {
        $md5 = md5($app);
        $langs = (array)self::$CACHE[$md5];
        if (empty($langs)) {
            $langs = [];
            $lg = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 4));
            // $lg = 'en';
            $many = Etc::many($app, "lang/{$lg}*"); //优先查找，本地语言包
            empty($many) && $many = Etc::many($app, 'lang/*');
            Etc::mergeRecursive($many, $langs);
            self::$CACHE[$md5] = $langs;
        }
        return $langs;
    }
    public static function getMsg($langs, $keys = null)
    {
        if ($keys) foreach ($keys as $k) {
            $langs = $langs[$k];
        }
        return $langs;
    }
}