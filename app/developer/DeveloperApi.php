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

class DeveloperApi
{
    const URL = "https://api.icmsdev.com/v8";
    public static function url($url, $args = null)
    {
        $url = sprintf('%s/%s', self::URL, $url);
        $url  = Route::make($args, $url);
        return $url;
    }
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
}
