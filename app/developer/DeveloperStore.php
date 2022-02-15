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

class DeveloperStore
{
    const URL = "https://store.icmsdev.com/v8";

    public static function get($url, $args = null)
    {
        $url = sprintf('%s/%s', self::URL, $url);
        return Developer::get($url, $args);
    }
    public static function post($url, $args = null)
    {
        $url = sprintf('%s/%s', self::URL, $url);
        return Developer::post($url, $args);
    }
    public static function getData($sid, $do = 'get', $args = null)
    {
        $time  = time();
        $host  = $_SERVER['HTTP_HOST'];
        $key   = md5(iPHP_KEY . $host . $time);
        $param = compact(array('sid', 'key', 'host', 'time'));
        $args && $param = array_merge($param, $args);
        $url   = sprintf('%s/%s', $do, $sid);
        $json  = self::get($url, $param);
        $array = json_decode($json, true);
        return $array;
    }
}
