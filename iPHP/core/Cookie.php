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

class Cookie
{
    public static function getParams($life)
    {
        // $domain = iPHP_COOKIE_DOMAIN;
        $domain = '';
        $path = iPHP_COOKIE_PATH;
        $lifetime = ($life ? $life : iPHP_COOKIE_TIME);
        $timestamp = time();
        $life = $life > 0 ? ($timestamp + $life) : ($life < 0 ? $timestamp - 31536000 : 0);
        $secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;
        $httponly = true;
        return [$life, $path, $domain, $secure, $httponly];
    }

    //设置COOKIE
    public static function set($name, $value = '', $life = 0, $httponly = true)
    {
        list($life, $path, $domain, $secure, $httponly) = self::getParams($life);
        $value = rawurlencode($value);
        $name = iPHP_COOKIE_PRE . '_' . $name;
        setcookie($name, $value, $life, $path, $domain, $secure, $httponly);
    }

    //取得COOKIE
    public static function get($name)
    {
        $name = iPHP_COOKIE_PRE . '_' . $name;
        $value = isset($_COOKIE[$name]) ? $_COOKIE[$name] : '';
        return rawurldecode($value);
    }

    public static function del($name)
    {
        self::set($name, '', -31536000);
    }
    public static function clear()
    {
        foreach ($_COOKIE as $key => $value) {
            self::del($key);
        }
    }
}
