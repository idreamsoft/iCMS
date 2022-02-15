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
    //设置COOKIE
    public static function set($name, $value = "", $life = 0, $httponly = true)
    {
        // $cookiedomain = iPHP_COOKIE_DOMAIN;
        $cookiedomain = '';
        $cookiepath = iPHP_COOKIE_PATH;
        $value = rawurlencode($value);
        $life = ($life ? $life : iPHP_COOKIE_TIME);
        $name = iPHP_COOKIE_PRE . '_' . $name;
        $timestamp = time();
        $life = $life > 0 ? $timestamp + $life : ($life < 0 ? $timestamp - 31536000 : 0);
        $secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;
        setcookie($name, $value, $life, $cookiepath, $cookiedomain, $secure, $httponly);
    }
    //取得COOKIE
    public static function get($name)
    {
        $name = iPHP_COOKIE_PRE . '_' . $name;
        return rawurldecode($_COOKIE[$name]);
    }
    public static function destroy($name)
    {
        self::set($name, '',-31536000);
    } 
}
