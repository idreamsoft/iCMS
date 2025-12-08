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
// class SessionHandler implements SessionHandlerInterface
// {}
class Session
{
    private $lifetime = 86400;

    private static $state = false;
    private static $name = '';
    private static $instance;

    public static function init($name = 'SESSION')
    {
        if(self::$state) return;

        self::ini();
        self::cookie();
        self::engine();
        self::name($name);
        self::start();
    }

    public static function engine($engine = 'file')
    {
        // session_set_save_handler($handler, true);
    }

    public static function name($name)
    {
        self::$name = session_name(iPHP_COOKIE_PRE . '_' . strtoupper($name));
    }

    public static function ini()
    {
        @ini_set('session.cookie_lifetime', 86400);
        @ini_set('session.use_cookies', 'On');
        @ini_set('session.use_only_cookies', 'On');
        @ini_set('session.use_strict_mode', 'On');
        @ini_set('session.cookie_httponly', 'On');
        @ini_set('session.cookie_secure', $_SERVER['SERVER_PORT'] == 443 ? 'On' : 'Off');
        @ini_set('session.gc_maxlifetime', 1440);
        @ini_set('session.cache_limiter', 'nocache');
    }

    public static function cookie($life = 0)
    {
        // $cookie = session_get_cookie_params();
        list($life, $path, $domain, $secure, $httponly) = Cookie::getParams($life);
        session_set_cookie_params($life, $path, $domain, $secure, $httponly);
    }
    // public static function getInstance()
    // {
    //     if (!isset(self::$instance)) {
    //         self::$instance = new self;
    //     }
    //     // session_set_save_handler(new SessionHandler, true);
    //     self::$instance->start();

    //     return self::$instance;
    // }
    // public function __set($name, $value)
    // {
    //     $name = Security::safeStr($name);
    //     $_SESSION[$name] = Security::escapeStr($value);
    // }
    // public function __get($name)
    // {
    //     $name = Security::safeStr($name);
    //     if (isset($_SESSION[$name])) {
    //         return $_SESSION[$name];
    //     }
    // }
    public static function start()
    {
        self::$state = session_start([
            'cookie_lifetime' => 86400,
        ]);
        if (!self::$state) {
            throw new sException('Error Session Start');
        }
    }

    public static function set($name, $value)
    {
        $name = Security::safeStr($name);
        $_SESSION[$name] = Security::escapeStr($value);
    }

    public static function get($name = null)
    {
        if (is_null($name)) {
            return $_SESSION;
        }
        $name = Security::safeStr($name);
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }
    }

    public static function uset($name)
    {
        unset($_SESSION[$name]);
    }

    public static function destroy()
    {
        if (self::$state) {
            session_unset();
            session_regenerate_id(true);
            self::$state = session_destroy();
            session_write_close();
            Cookie::del(self::$name);
            unset($_SESSION);

            return !self::$state;
        }

        return false;
    }
}
