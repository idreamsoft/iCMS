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

class AdmincpAccess
{
    public static $userid   = 0;
    public static $noAccess   = 'NoAccess';
    /**
     * 权限验证初化
     * Admincp::$CALLBACK['auth']
     */
    public static function auth()
    {
        Menu::$callback = array(
            "access"  => array(__CLASS__, "checkMenu"),
        );
        NodeAccess::$callback = array(
            "check"  => array(__CLASS__, "checkNode"),
        );

        self::accessUri("ADMINCP"); //验证后台登录权限

        // $a = Menu::access_data('article');
        // var_dump($a);
        // exit;

    }
    /**
     * 安全检测
     * Admincp::$CALLBACK['security']
     */
    public static function security($params)
    {
        try {
            Security::csrf_token($params); //生成 CSRF token
            Security::csrf_check($params);
        } catch (\Exception $ex) {
            Admincp::exception($ex, 'security');
        }
    }
    /**
     * 
     * Admincp::$CALLBACK['access:app']
     */
    public static function accessApp($app, $obj, $method, $args)
    {
        // var_dump($app, $obj, $method, $args);
    }

    /**
     * [验证URI，是否有权限进入]
     * Admincp::$CALLBACK['access:uri']
     * @param  [array] $uri [网址参数]
     * @return [bool]       [true 拥有权限]
     * @return AdmincpException [无权限 抛出异常]
     */
    public static function accessUri($uri = null)
    {
        $uri === null && $uri = Admincp::uri();
        $batch = Request::param('batch');
        $batch && $uri .= '&batch=' . $batch;

        if (self::checkApp($uri)) {
            return true;
        } else {
            return Admincp::exception($uri, 'uri');
        }
    }

    /**
     * [验证菜单权限，是否显示]
     * @param  [array] $menu [菜单数组]
     * @return [bool]       [false 无权限]
     * @return [bool]       [true 拥有权限]
     */
    public static function checkMenu($menu)
    {
        $uri = $menu['access'];
        return self::checkApp($uri);
    }
    /**
     * 验证栏目权限
     */
    public static function checkNode($cid, $type = null, $flag = null, $msg = null)
    {
        if (Member::isSuperRole()) {
            return true;
        }

        // var_dump($cid, $type, $flag);
        $node = "{$cid}:{$type}";
        $allNode = "all:{$type}";
        $access = Member::$ACCESS['node'];

        // iDebug::$DATA['checkNode.NEED'][] = $node;
        // iDebug::$DATA['checkNode.allNode'][] = $allNode;
        iDebug::$DATA['checkNode.has'] = $access;
        if ($access) foreach ($access as $key => $value) {
            list($id, $at) = explode(':', $value);
            $typeArray[$at][$id] = (int)$id;
            $idsArray[$id][$at] = 1;
        }
        iDebug::$DATA['checkNode.Access.Type'] = $typeArray;
        if ($cid === 'IDS') { //获取该类型权限 ID
            $acc = $typeArray[$type];
            // iDebug::$DATA['checkNode.Access.Ids']= $idsArray;
            unset($acc['all']);
            return empty($acc) ? '-1' : $acc;
        }
        // iDebug::$DATA['checkNode.result'][$node] = in_array($node, $access);
        // iDebug::$DATA['checkNode.result'][$allNode] = in_array($allNode, $access);
        // iDebug::$DATA['checkNode.result']['all'] = in_array('all', $access);
        // if($type=='ce'){
        //     var_dump($node, in_array($node, $access));
        //     var_dump($allNode, in_array($allNode, $access));
        //     var_dump('all', in_array('all', $access));
        // }
        if (
            in_array($node, $access) ||
            in_array($allNode, $access) ||
            in_array('all', $access)
        ) {
            return true;
        }
        empty($msg) && $msg = $node;


        return $flag ?
            Admincp::exception($msg, 'node.' . $flag) :
            false;
    }
    /**
     * 验证应用权限
     */
    public static function checkApp($uri)
    {
        if (Member::isSuperRole()) {
            return true;
        }
        $access = Member::$ACCESS['app'];
        iDebug::$DATA['checkApp.Access'] = $access;
        if (in_array($uri, $access)) {
            return true;
        }
        return false;
    }
    /**
     * APP 基础权限
     */
    public static function app($access, $app = null)
    {
        if (strpos($access, '.') === false) {
            $app === null && $app = Admincp::$APP_NAME;
            $access = "{$app}.{$access}";
        }
        // iDebug::$DATA['auth.app'][] = $access;
        return self::checkApp($access);
    }
    /**
     * 判断整个页面所有链接权限 
     * 无权限链接删除整个a标签
     * 标记 i="ACCESS:EXCLUDE" 权限排除 无权限直接显示文本信息
     */
    public static function html(&$html)
    {
        // return true;
        if (Member::isSuperRole()) {
        }
        preg_match_all('@<a\b[^>]*href=["|\']([^"|\']*)["|\'][^>]*>(.+)</a>@isU', $html, $matches);
        if ($matches[1]) {
            $uriArray = array();
            $textArray = array();
            foreach ($matches[1] as $key => $value) {
                if (strpos($value, iPHP_SELF) !== false) {
                    $querys = parse_url($value, PHP_URL_QUERY);
                    $QA = parse_url_qs($querys);
                    $uri = Admincp::makeQS(array(
                        $QA['app'],
                        'action' => $QA['action'],
                        'do' => $QA['do']
                    ));
                    $uriArray[$uri][] = $matches[0][$key];
                    $textArray[$uri][] = $matches[2][$key];
                } else {
                }
            }
            // $html = str_replace($exclude, 'javascript:;', $html);
            foreach ($uriArray as $uri => $arr) {
                if (self::checkApp($uri)) {
                    // return true;
                } else {
                    $tmp = implode('', $arr);
                    //ACCESS:EXCLUDE 权限排除 无权限直接显示文字
                    if (strpos($tmp, 'ACCESS:EXCLUDE') !== false) {
                        $tArr = $textArray[$uri];
                        $html = str_replace($arr, $tArr, $html);
                    }
                    unset($tmp);
                    $html = str_replace($arr, '', $html);
                }
            }
            // print_r($uriArray);
        }
        unset($matches, $uriArray);
        // exit;
        // var_dump($matches);
        $html = preg_replace('@<(\w+)[^>]+i="NoAccess"[^>]+>.*</\\1>@isU', '', $html);
        $html = str_replace('{{APP_URL}}', APP_URL, $html);
    }

    public static function has($node)
    {
        echo 'i="' . self::$noAccess . '"';
    }
    public static function href($url)
    {
        // echo 'i="'.self::$noAccess.'"';
        echo 'href="' . $url . '"';
    }
    /**
     * 批处理权限
     */
    public static function batch($action)
    {
        $uri = Admincp::makeQS(array(
            Admincp::$APP_NAME,
            'do' => 'batch'
        )) . '&batch=' . $action;
        return self::checkApp($uri);
    }
}
