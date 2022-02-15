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

class iJson
{
    public static $jsonp = null;
    public static $result = null;
    public static $exception = null;

    public static $message = null;
    public static $url = null;
    public static $forward = null;
    public static $code = null;
    public static $state = null;

    public static function error($message = '请求失败', $url = '', $code = 0, $state = 'ERROR')
    {
        $args = func_get_args();
        $num = func_num_args();
        if (is_numeric($url)) { //error(message,code)
            $code = $url;
            $url = '';
        }
        $result = compact("message", "url", "code", "state");
        self::$exception && $result['exception'] = self::$exception;
        return self::$jsonp ? self::jsonp($result) : self::display($result);
    }
    /**
     * [请求成功]
     *
     * @param   Array   $data  [数组]
     * @param   String  $message   [提示信息]
     * @param   String  $url   [网址]
     * @param   Int     $code  [状态]
     *
     * @return  Json [返回json]
     */
    public static function success($data = array(), $message = '请求成功', $url = '', $code = 1, $state = 'SUCCESS')
    {
        $args = func_get_args();
        $num = func_num_args();
        if (!is_array($data)) {
            $map = [
                ['message'], //success(message)
                ['message', 'code'], //success(message,code)
                ['message', 'url', 'code'] //success(message,url,code)
            ];
            $k = $num - 1;
            $vars = array_combine($map[$k], $args);
            extract($vars);
            //success(message,url)
            if (!is_numeric($code)) {
                $url = $code;
                $code = 1;
            }
            $data = [];
        } else {
            //success(data,message,code)
            if (is_numeric($url)) {
                $code = $url;
                $url = '';
            }
        }
        $result = compact("data", "message", "url", "code", "state");
        return self::$jsonp ? self::jsonp($result) : self::display($result);
    }
    public static function fetch($array)
    {
        return self::display($array, true);
    }
    public static function display($array, $flag = false)
    {
        self::$message && $array['message'] = self::$message;
        if (preg_match('/\w+\:\w+/', $array['message'])) {
            $array['message'] = Lang::get($array['message']);
        }
        self::$forward && $array['forward'] = self::$forward;
        self::$url && $array['url'] = self::$url;
        self::$state && $array['state'] = self::$state;
        !is_null(self::$code) && $array['code'] = self::$code;

        $json = json_encode($array);
        if ($flag) return $json;
        exit($json);
    }
    public static function jsonp($json, $callback = null, $node = 'top')
    {
        is_array($json) && $json = self::fetch($json);

        is_string(self::$jsonp) && $callback = self::$jsonp;
        if($_callback = Request::param('callback')){
            $callback = $_callback;
        };
        !is_string($callback) && $callback = 'callback';
        $callback = Security::safeStr($callback);

        $json = str_replace('"%%function', 'function', $json);
        $json = str_replace('}%%"', '}', $json);

        $_node = Request::param('jsonp_node');
        isset($_node) && $node = $_node;
        $node && $node = '.' . ltrim($node, '.');

        // print_R(debug_backtrace()) ;

        printf(
            '<script>(function(){var a = window.parent.jsonpCallback||window%s.%s;a(%s);})();</script>',
            $node,
            $callback,
            $json
        );
        exit;
    }
}
