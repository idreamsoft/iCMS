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

class AdmincpAuth
{
    public static $userid = 0;
    public static $noAccess = 'NoAccess';

    /**
     * 权限验证初化
     */
    public static function init()
    {
        Menu::$callback = [
            'access'  => [__CLASS__, 'checkMenu'],
        ];

        self::checkUri('#ADMINCP#'); //验证后台登录权限
    }

    /**
     * 安全检测
     */
    public static function security($params)
    {
        Security::csrf_token($params); //生成 CSRF token
        Security::csrf_check($params);
    }

    public static function accessTip($call = null)
    {
        $uri = Admincp::urlPath();
        //无权限提示
        if (in_array('#NOACCESSTIP#', Admin::$ACCESS['app'])) {
            if (is_callable($call)) {
                return call_user_func($call);
            } else {
                Admincp::error($uri, 'uri');
            }
        } else {
            $msg = $uri . ' 暂无权限访问';
            if (Request::isAjax() || Request::isPost()) {
                return iJson::error($msg);
            } else {
                exit($msg);
            }
        }
    }

    public static function checkRole($role = null)
    {
        if (Admin::checkRole($role)) {
            return true;
        } else {
            self::accessTip();
        }
    }
    public static function checkPermission($value = null)
    {
        if (Admin::checkPermission($value)) {
            return true;
        } else {
            self::accessTip();
        }
    }

    /**
     * 验证URI，是否有权限进入
     * @param  string $uri 网址参数
     * @return bool|\sException true 拥有权限|无权限 抛出异常
     */
    public static function checkUri($uri = null)
    {
        if (self::check($uri)) {
            return true;
        } else {
            self::accessTip();
        }
    }

    /**
     * 验证菜单权限，是否显示
     * @param  [array] $menu [菜单数组]
     * @return [bool]       [false 无权限]
     * @return [bool]       [true 拥有权限]
     */
    public static function checkMenu($menu)
    {
        $uri = $menu['access'];
        return self::check($uri);
    }

    /**
     * 验证应用权限
     */
    public static function check($uri = null)
    {
        if (Admin::isSuperRole()) {
            return true;
        }

        $access = (array)Admin::$ACCESS['app'];
        $access = array_map('strtolower', $access);

        $uri = str_replace(ADMINCP_URL, '', $uri);
        $uri = strtolower($uri);
        $uri = trim($uri, '/');
        $access = array_map(function ($v) {
            return trim($v, '/');
        }, $access);
        if ($access && $uri && in_array($uri, $access)) {
            return true;
        }
        return false;
    }
    public static $nodeTypeMap = ['m' => '管理', 'a' => '添加子节点', 'e' => '编辑', 'd' => '删除'];
    public static $appTypeMap = ['cm' => '内容管理', 'ca' => '内容添加', 'ce' => '内容编辑', 'cd' => '内容删除'];

    /**
     * 验证节点/栏目权限
     */
    public static function node($nodeId, $type = null, $msg = null)
    {
        if (Admin::isSuperRole()) {
            return true;
        }

        // var_dump($nodeId, $type, $flag);
        $node = "{$nodeId}:{$type}";
        $allNode = "all:{$type}";
        $access = Admin::$ACCESS['node'] ?: [];
        if ($access) {
            foreach ($access as $key => $value) {
                list($id, $at) = explode(':', $value);
                $typeArray[$at][$id] = (int)$id;
                $idsArray[$id][$at] = 1;
            }
        }

        if ($nodeId === 'IDS') { //获取该类型权限 ID
            if (in_array($allNode, $access)) {
                return true;
            }
            $acc = $typeArray[$type];
            unset($acc['all']);
            return empty($acc) ? '-1' : $acc;
        }

        if (
            in_array($node, $access) ||
            in_array($allNode, $access) ||
            in_array('all', $access)
        ) {
            return true;
        }
        return false;
        empty($msg) && $msg = $node;
        Admincp::error($msg, 'node');
    }

    public static function nodeWhere($cid, &$where, $field = 'cid')
    {
        $nodeAccess = self::node('IDS', 'cm'); //取得所有有权限的栏目ID
        if ($nodeAccess !== true) { //被设置了相关栏目权限- 1 没有任何权限
            if (is_array($nodeAccess) && $cid) {
                $cflag = self::node($cid, 'cm');
                if ($cflag) { //有权限
                    $nodeAccess = (array)$cid;
                    $sub = Request::param('sub');
                    if ($sub) {
                        $subidsArray = NodeCache::getIds($cid);
                        if ($subidsArray) {
                            foreach ($subidsArray as $_id) {
                                if (self::node($_id, 'cm')) {
                                    $nodeAccess[] = $_id;
                                }
                            }
                        }
                    }
                } else {
                    $nodeAccess = '-2';
                }
            }
            if (is_array($nodeAccess)) {
                $nodeAccess = array_unique($nodeAccess);
                $nodeAccess = array_values($nodeAccess);
            }
            $where[] = [$field, 'IN', $nodeAccess];
        } else {
            if ($cid) { // -1 没有任何权限
                Node::makeWhere($where, $cid);
            }
        }
        if ($_GET['hidden']) {
            $hidden = NodeCache::get('hidden');
            $where[] = [$field, 'NOT IN', $hidden];
        }
    }

    // public static function content($node, $flag)
    // {
    //     if (Admin::isSuperRole()) {
    //         return true;
    //     }
    //     if (is_numeric($node)) {
    //         $node = Node::get($node);
    //     }
    //     if ($ACCESS = $node['ACCESS']['content']) {
    //         $operation = $flag ? 'edit' : 'add';
    //         if ($flag == 'delete' || $flag === 'manage') {
    //             $operation = $flag;
    //         }
    //         if (!$ACCESS[$operation]) {
    //             AdmincpBase::alert("无权进行此项操作[{$operation}]");
    //         }
    //     }
    // }
    public static function where(&$where)
    {
        if (Admin::isSuperRole()) {
            $userid = (int)Request::get('userid');
        } else {
            $userid = Admin::$user_id;
        }
        $userid && $where[] = ['userid', $userid];
    }

    /**
     * APP 基础权限
     */
    public static function app($access, $app = null)
    {
        // AdmincpAuth::app('/add')
        if (strpos($access, '/') === false) {
            $app === null && $app = Admincp::$APP_NAME;
            $access = "{$app}/{$access}";
        }
        // AdmincpAuth::app('./add')
        // if (strpos($access, './') === 0) {
        //     $app === null && $app = Admincp::$APP_NAME;
        //     $access = Admincp::$APP_NAME.'/'.$access;
        //     $access = str_replace('./', '', $access);
        // }

        return self::check($access);
    }

    /**
     * 判断整个页面所有链接权限
     * 无权限链接删除整个a标签
     * 标记 i="ACCESS:EXCLUDE" 权限排除 无权限直接显示文本信息
     */
    public static function html(&$html)
    {
        if (Admin::isSuperRole()) {
            return true;
        }
        preg_match_all('@<a\b[^>]*href=["|\']([^"|\']*)["|\'][^>]*>(.+)</a>@isU', $html, $matches);
        if ($matches[1]) {
            $uriArray = [];
            $textArray = [];
            foreach ($matches[1] as $key => $value) {
                if (strpos($value, ADMINCP_URL) !== false) {
                    $parse = parse_url($value);
                    $path = $parse['path'];
                    if ($parse['query']) {
                        parse_str($parse['query'], $querys);
                        if ($querys['app']) {
                            $path .= '/' . $querys['app'];
                            unset($querys['app']);
                        }
                        if ($querys['do']) {
                            $path .= '/' . $querys['do'];
                            unset($querys['do']);
                        }
                    }
                    $path = iString::ltrim($path, ADMINCP_URL);
                    $path = ltrim($path, '/');
                    $uriArray[$path][] = $matches[0][$key];
                }
            }

            foreach ($uriArray as $uri => $arr) {
                if (self::check($uri)) {
                    // return true;
                } else {
                    $html = str_replace($arr, '', $html);
                }
            }
        }
        unset($matches, $uriArray);
    }

    /**
     * 批处理权限
     */
    public static function batch($action)
    {
        $uri = Admincp::url([Admincp::$APP_NAME, 'batch']) . '/' . $action; //$action会被转义 直接拼接
        return self::check($uri);
    }
}
