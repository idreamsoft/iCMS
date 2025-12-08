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
class iDefine
{
    public static function set($vars, $val = null)
    {
        if (is_array($vars)) {
            foreach ($vars as $key => $value) {
                self::set($key, $value);
            }
        } else {
            $name = 'iPHP_' . strtoupper($vars);
            defined($name) or define($name, $val);
        }
    }

    public static function bootstrap()
    {
        define('iPHP_SELF', $_SERVER['PHP_SELF']);
        define('iPHP_REFERER', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');

        define('iPHP_REQUEST_HOST', $_SERVER['REQUEST_HOST']);
        define('iPHP_REQUEST_URI', $_SERVER['REQUEST_URI']);
        define('iPHP_REQUEST_URL', $_SERVER['REQUEST_URL']);
        define('iPHP_REQUEST_SCHEME', $_SERVER['REQUEST_SCHEME']);
    }

    public static function framework(&$conf)
    {
        define('iPHP_URL_404', $conf['notfound']); //404定义
    }

    public static function timezone($conf)
    {
        defined('iPHP_TIME_CORRECT') or define('iPHP_TIME_CORRECT', (int)$conf['cvtime']);
        isset($conf['timezone']) && @date_default_timezone_set($conf['timezone']); //设置时区
    }

    /**
     * 设备标识 iPHP_DEVICE
     */
    public static function device($device)
    {
        defined('iPHP_DEVICE') or define('iPHP_DEVICE', $device['name']);
    }

    /**
     * //是否移动设设备 iPHP_MOBILE
     */
    public static function mobile($flag)
    {
        defined('iPHP_MOBILE') or define('iPHP_MOBILE', $flag);
    }

    public static function debug($conf)
    {
        defined('iPHP_DEBUG') or define('iPHP_DEBUG', $conf['php']); //程序调试模式
        defined('iPHP_DEBUG_TRACE') or define('iPHP_DEBUG_TRACE', $conf['php_trace']); //程序调试模式
        defined('iPHP_DEBUG_ERRORLOG') or define('iPHP_DEBUG_ERRORLOG', $conf['php_errorlog']); //程序调试模式
        defined('iPHP_JS_DEBUG') or define('iPHP_JS_DEBUG', $conf['js']); //数据调试
        defined('iPHP_DB_DEBUG') or define('iPHP_DB_DEBUG', $conf['db']); //数据调试
        defined('iPHP_DB_TRACE') or define('iPHP_DB_TRACE', $conf['db_trace']); //SQL跟踪
        defined('iPHP_DB_EXPLAIN') or define('iPHP_DB_EXPLAIN', $conf['db_explain']); //SQL解释
        defined('iPHP_TEMPLATE_DEBUG') or define('iPHP_TEMPLATE_DEBUG', $conf['tpl']); //模板调试
        defined('iPHP_TPL_DEBUGGING') or define('iPHP_TPL_DEBUGGING', $conf['tpl_trace']); //模板数据调试

        ini_set('display_errors', 'OFF');
        error_reporting(0);

        if (iPHP_DEBUG || iPHP_DB_DEBUG || iPHP_TEMPLATE_DEBUG) {
            ini_set('display_errors', 'ON');
            error_reporting(E_ALL ^ E_NOTICE);
        }
        iPHP_DB_DEBUG && DB::debug(1);
        iPHP_DB_TRACE && DB::debug(2);
        iPHP_DB_EXPLAIN && DB::debug(3);
    }

    /**
     * 定义
     *
     * @param array $variable
     * @return void
     */
    public static function make(array $variable)
    {
        foreach ($variable as $key => $value) {
            defined($key) or define($key, $value);
        }
    }
}
