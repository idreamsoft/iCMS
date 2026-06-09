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
    // public static $exception = null;
    public static $throw = false;

    public static $message = null;
    public static $url = null;
    public static $forward = null;
    public static $code = null;
    public static $state = null;

    /**
     * 输出失败json
     *
     * @param string $message
     * @param string $url
     * @param integer $code
     * @param string $state
     * @return void
     */
    public static function fail($message = '', $url = '', $code = 0, $state = 'ERROR')
    {
        $args = func_get_args();
        $num = func_num_args();
        if (is_numeric($url)) { //error(message,code)
            $code = $url;
            $url = '';
        }
        empty($state) && $state = 'ERROR';
        $success = false;
        $result = compact('message', 'url', 'code', 'state', 'success');
        return self::display($result);
    }

    /**
     * 输出错误json
     *
     * @param string $message
     * @param string $url
     * @param integer $code
     * @param string $state
     * @return void
     */
    public static function error($message = 'request:fail', $url = '', $code = 0, $state = 'ERROR')
    {
        $args = func_get_args();
        $num = func_num_args();
        if (is_numeric($url)) { //error(message,code)
            $code = $url;
            $url = '';
        }
        $data = [];
        $success = false;
        $result = compact('message', 'url', 'code', 'state', 'data', 'success');
        if (Request::isAjax()) {
            return self::display($result);
        }
        $json = self::fetch($result);
        throw new sException($json, 'json');
    }

    /**
     * 请求成功
     *
     * @param   Array   $data  数组
     * @param   String  $message   提示信息
     * @param   String  $url   网址
     * @param   Int     $code  状态
     *
     * @return  Json 返回json
     */
    public static function success($data = [], $message = 'request:success', $url = '', $code = 1, $state = 'SUCCESS')
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
        $success = true;
        $result = compact('data', 'message', 'url', 'code', 'state', 'success');
        return self::display($result);
    }

    /**
     * 返回 json 字符串
     *
     * @param array $array
     * @return string
     */
    public static function fetch($array)
    {
        return self::display($array, true);
    }

    public static function display($array, $flag = false)
    {
        self::$message && $array['message'] = self::$message;
        if (is_string($array['message']) && preg_match(Lang::PATTERN, $array['message'])) {
            $array['message'] = Lang::get($array['message']);
        }
        self::$forward && $array['forward'] = self::$forward;
        self::$url && $array['url'] = self::$url;
        self::$state && $array['state'] = self::$state;
        !is_null(self::$code) && $array['code'] = self::$code;

        self::$jsonp && self::jsonp($array);

        $json = json_encode($array, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
     
        if ($flag) {
            return $json;
        }
        self::output($json);
    }

    /**
     * 输入 json 字符串
     *
     * @param string $json
     * @return void
     */
    public static function output($json)
    {
        if (!headers_sent()) {
            @header('Content-Type:application/json; charset=utf-8');
        }
        exit($json);
    }

    public static function jsonp($json, $callback = null, $node = 'top')
    {
        is_array($json) && $json = self::fetch($json);
        is_string(self::$jsonp) && $callback = self::$jsonp;
        if ($_callback = Request::param('callback')) {
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

    /**
     * 解析JSON字符或者文件,出错抛出异常
     *
     * @param [type] $json
     * @param boolean $flag
     * @return array
     */
    public static function decode($json, $flag = true)
    {
        $path = '';
        if (strpos($json, '/') !== false) {
            $path = $json;
            $json = file_get_contents($json);
        }
        $array = json_decode($json, $flag);
        $error = json_last_error();
        $errorMsg = json_last_error_msg();

        if ($error) {
            throw new sException($path . ' ' . $errorMsg);
        }
        if ($flag) {
            return is_array($array) ? $array : [];
        }
        return $array;
    }

    public static function listItem($id, $name, $text = null, $item = [])
    {
        $field = 'id';
        if (is_array($id)) {
            $field = key($id);
            $id = current($id);
        }

        return array_merge([
            'id'   => $id,
            'name' => $name,
            'text' => isset($text) ? $text : sprintf('%s [%s=%s]', $name, $field, $id)
        ], $item);
    }

    public static function listData($list = [], $pageSize = null, $pageNumber = null, $totalRow = null, $totalPage = 1)
    {
        Paging::setName('pageNumber');
        // 确保 $list 是数组
        is_array($list) or $list = [];
        // 计算总行数
        is_null($totalRow) && $totalRow = count($list);

        if (is_null($pageSize) && is_null($pageNumber)) {
            // 设置默认值
            $pageSize = Request::post('pageSize', 20);
            $pageNumber = Request::post('pageNumber', 1);
            // 计算总页数
            $totalPage = max(1, ceil($totalRow / $pageSize));
            // 确保页码在有效范围内
            $pageNumber = max(1, min($pageNumber, $totalPage));
            // 计算分页的起始位置
            $offset = ($pageNumber - 1) * $pageSize;
            // 截取分页数据
            $list = array_slice($list, $offset, $pageSize);
        }
        return compact([
            'pageSize',
            'pageNumber',
            'totalRow',
            'totalPage',
            'list'
        ]);
    }
}
class iJsonException extends sException
{
}
