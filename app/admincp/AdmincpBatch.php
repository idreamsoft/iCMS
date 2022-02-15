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

class AdmincpBatch
{
    public static $config = array();

    public static function getEtcs($app = null)
    {
        is_null($app) && $app = Admincp::$APP_NAME;
        $app  = self::$config['etc.app'] ?: $app;
        $method  = self::$config['etc.method'] ?: 'manage';
        //app/*/etc/admincp/test.manage.batch*.json
        $pattern = sprintf("admincp/%s.%s.batch*", $app, $method);
        //admincp/test.manage.batch*.json
        $many1 = (array)Etc::many('*', $pattern, true);
        $many2 = [];
        $className = get_called_class();
        $parent = get_parent_class($className);
        if ($parent == 'NodeAdmincp') {
            $pattern = sprintf("admincp/node.%s.batch*", $method);
            //admincp/node.%s.batch*
            $many2 = (array)Etc::many('*', $pattern, true);
        }
        $actions = array_merge($many1, $many2);
        if ($actions) foreach ($actions as $key => $item) {
            $name = $item['name'];
            self::$config['etc.name'] && $name = str_replace("{name}", self::$config['etc.name'], $name);
            $actions[$key]['name'] = str_replace("{name}", Admincp::$APP_DATA['title'], $name);
            if (!AdmincpAccess::batch($key)) {
                unset($actions[$key]);
            }
        }
        return $actions;
    }
    //批量操作其它UI
    public static function html()
    {
        // var_dump('batchHtml');
        // self::$config['data'] = true;
        // $actions = Admincp::$APP_INSTANCE->do_batch();
        $actions = self::getEtcs();
        $app = Admincp::$APP_NAME;
        if ($actions) foreach ($actions as $key => $item) {
            if ($item['view']) {
                $view = $item['view'];
                ///app/article/views/batch.sss.html
                $path = AdmincpView::display('batch.' . $view[0], $view[1] ?: $app);
                if (is_file($path)) {
                    include $path;
                }
            }
        }
    }
    //批量操作菜单
    public static function menu()
    {
        // self::$config['data'] = true;
        // $actions = Admincp::$APP_INSTANCE->do_batch();
        $actions = self::getEtcs();
        $enable  = isset(self::$config['enable']) ? self::$config['enable'] : true;
        include AdmincpView::display("widget/batch", 'admincp');
    }
    public static function run($default, $title = null, $field = 'id', $type = 'intval')
    {

        $actions = self::getEtcs();
        // if (self::$config['data']) {
        //     return $actions;
        // }
        // var_dump($actions,iDebug::$DATA['batchEtc'],Admincp::$APP_NAME);
        $app = Admincp::$APP_NAME;
        $param = self::param($title, $field, $type);
        $batch = $param[2];
        $item = $actions[$batch];
        if ($item['post']) {
            foreach ($item['post'] as $key => $pkey) {
                is_numeric($key) && $key = $pkey;
                $post[$key] = Request::post($pkey);
            }
            array_push($param, $post);
        }
        $call = $item['call'] ?: $batch;
        $callbale = $default[$call] ?: $call;
        is_callable($callbale) or AdmincpBase::alert('[' . $callbale . ']不存在');
        $result = iPHP::callback($callbale, $param, E_USER_ERROR);

        $title = $item['title'] ?: $item['name'];
        empty($title) && $title = Admincp::$APP_DATA['title'];
        if (is_bool($result)) {
            return [$result, $title];
        } elseif (is_array($result)) {
            return $result;
        } else {
            return $result ?: $title;
        }
    }
    public static function set($key, $value)
    {
        self::$config[$key] = $value;
    }
    public static function param($title = null, $field = 'id', $type = 'intval')
    {
        $msg = '请选择要操作的' . ($title ?: '项目');
        $idArray = (array) $_POST[$field];
        $idArray or AdmincpBase::alert($msg);
        $type && $idArray = array_map($type, $idArray);
        $ids     = implode(',', $idArray);
        $batch   = $_POST['batch'];
        return array($idArray, $ids, $batch);
    }
    public static function showed($show, $data, $node = null)
    {
        $showed = (bool)(isset($show) ? $show : true);
        if (is_array($show)) {
            foreach ($show as $sk => $sv) {
                $dv = $data[$sk];
                if (is_array($sv)) {
                    $showed = version_compare($dv, $sv[1], $sv[0]) && $showed;
                } else {
                    $showed = ($dv == $sv) && $showed;
                }
            }
        } elseif ($show) {
            if (strpos($show, '::') !== false) {
                $class = get_called_class();
                $show = str_replace('self::', $class . '::', $show);
                $showed = call_user_func_array($show, [$data, $node]);
            }
        }
        return $showed;
    }
}
