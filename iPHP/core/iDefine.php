<?php
// namespace iPHP\core;

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

    public static function boot()
    {
        define('iPHP_SELF', $_SERVER['PHP_SELF']);
        define('iPHP_REFERER',  $_SERVER['HTTP_REFERER']);

        define('iPHP_REQUEST_HOST', $_SERVER['HTTP_URL']);
        define('iPHP_REQUEST_URI', $_SERVER['REQUEST_URI']);
        define('iPHP_REQUEST_URL', $_SERVER['REQUEST_URL']);
    }
    public static function route($conf)
    {
        define('iPHP_URL', $conf['url']);
        define('iPHP_URL_404', $conf['404']); //404定义
        define('iPHP_ROUTE_REWRITE', $conf['rewrite']);
    }
    public static function timezone($conf)
    {
        defined('iPHP_TIME_CORRECT') or define('iPHP_TIME_CORRECT', (int)$conf['cvtime']);
        $conf['zone'] && @date_default_timezone_set($conf['zone']); //设置时区
    }
    public static function debug($conf)
    {
        defined('iPHP_DEBUG') or define('iPHP_DEBUG', $conf['php']); //程序调试模式
        defined('iPHP_DEBUG_TRACE') or define('iPHP_DEBUG_TRACE', $conf['php_trace']); //程序调试模式
        defined('iPHP_DEBUG_ERRORLOG') or define('iPHP_DEBUG_ERRORLOG', $conf['php_errorlog']); //程序调试模式
        defined('iPHP_DB_DEBUG') or define('iPHP_DB_DEBUG', $conf['db']); //数据调试
        defined('iPHP_DB_TRACE') or define('iPHP_DB_TRACE', $conf['db_trace']); //SQL跟踪
        defined('iPHP_DB_EXPLAIN') or define('iPHP_DB_EXPLAIN', $conf['db_explain']); //SQL解释
        defined('iPHP_TPL_DEBUG') or define('iPHP_TPL_DEBUG', $conf['tpl']); //模板调试
        defined('iPHP_TPL_DEBUGGING') or define('iPHP_TPL_DEBUGGING', $conf['tpl_trace']); //模板数据调试

        ini_set('display_errors', 'OFF');
        error_reporting(0);

        if (iPHP_DEBUG || iPHP_DB_DEBUG || iPHP_TPL_DEBUG) {
            ini_set('display_errors', 'ON');
            error_reporting(E_ALL ^ E_NOTICE);
        }
        iPHP_DB_DEBUG   && DB::debug(1);
        iPHP_DB_TRACE   && DB::debug(2);
        iPHP_DB_EXPLAIN && DB::debug(3);
    }
    /**
     * [其它]
     * @param  string  $device [设备标识]
     * @param  boolean $mobile [是否移动设设备]
     * @return [type]          [description]
     */
    public static function info($device = '', $mobile = false)
    {
        defined('iPHP_DEVICE') or define('iPHP_DEVICE', $device);
        defined('iPHP_MOBILE') or define('iPHP_MOBILE', $mobile);
    }
}
