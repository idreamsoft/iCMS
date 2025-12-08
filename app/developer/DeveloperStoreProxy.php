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

class DeveloperStoreProxy
{
    const URL = 'https://store.icmsdev.com/v8';
    // const URL = 'http://v8.icmsdev.com/v8';

    public static function get($url, $args = null)
    {
        $url = sprintf('%s/%s', self::URL, $url);
        return Developer::get($url, $args);
    }

    public static function post($url, $args = null,$flag=false)
    {
        $url = sprintf('%s/%s', self::URL, $url);
        $json = Developer::post($url, $args);
        if($flag){
            $arr = json_decode($json, true);
            if(json_last_error()){
                AdmincpBase::alert($json);
            }
            if($arr['code']<1){
                AdmincpBase::alert($arr['message']);
            }
            return $arr;
        }else{
            return $json;
        }
    }

    // public static function getData($sid, $do = 'get', $args = null)
    // {
    //     $time = time();
    //     $host = $_SERVER['HTTP_HOST'];
    //     $key = md5(iPHP_KEY . $host . $time);
    //     $param = compact(['sid', 'key', 'host', 'time']);
    //     $args && $param = array_merge($param, $args);
    //     $url = sprintf('%s/%s', $do, $sid);
    //     $json = self::post($url, $param);
    //     $array = json_decode($json, true);
    //     return $array;
    // }

    public static function ajax($name = 'home')
    {
        // $id = Request::get('id');
        // $id && $url = sprintf('store/%d', $id);
        // $cid = Request::get('cid');
        // $cid && $url = sprintf('list/%d', $cid);
        // $list = Request::get('list');
        // $list && $url = sprintf('list/%s', Request::get('do'));

        $param = [
            'pageSize' => Request::get('pageSize'),
            'orderby'  => Request::get('orderBy'),
            'page'     => Request::get('page'),
            'keywords' => Request::get('keywords'),
            'stype'    => Request::get('stype'),
            'name'     => $name,
        ];
        $json = self::post('list', $param);
        $array = json_decode($json, true);
        if (json_last_error()) {
            $msg = sprintf('list %s %s', json_last_error_msg(), $json);
            throw new sException($msg);
        }
        return $array;
    }

    public static function index($url = 'home')
    {
        $id = Request::get('id');
        $id && $url = sprintf('store/%d', $id);
        $cid = Request::get('cid');
        $cid && $url = sprintf('list/%d', $cid);
        $list = Request::get('list');
        $list && $url = sprintf('list/%s', Request::get('do'));

        $param = [
            'pageSize' => Request::get('pageSize'),
            'orderBy'  => Request::get('orderBy'),
            'page'     => Request::get('page'),
            'keywords' => Request::get('keywords'),
            'tid'      => Request::get('tid'),
        ];
        $html = self::get($url, $param);
        return $html;
    }
}
