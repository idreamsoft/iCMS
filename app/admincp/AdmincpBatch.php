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
    public static $config = [];
    public static $app = null;
    public static $method = null;

    public static function setApp($app)
    {
        self::$app = $app;
    }

    public static function setMethod($method)
    {
        self::$method = $method;
    }

    public static function getEtcs($app = null, $method = null, $auth = true)
    {
        is_null($app) && $app = Admincp::$APP_NAME;
        is_null($method) && $method = Admincp::$APP_DO ?: 'index';
        $app = self::$config['app'] ?: $app;
        $method = self::$config['method'] ?: $method;
        $actions = AdmincpBase::getEtcs('batch', $app, $method);
        if ($actions) {
            foreach ($actions as $key => $item) {
                if ($item['show'] === false) {
                    unset($actions[$key]);
                    continue;
                }
                $name = $item['name'];
                self::$config['name'] && $name = str_replace('{name}', self::$config['name'], $name);
                $actions[$key]['name'] = str_replace('{name}', Admincp::$APP_DATA['title'], $name);
                $actions[$key]['from'] = $method;
                if ($auth && !AdmincpAuth::batch($key)) {
                    unset($actions[$key]);
                }
            }
        } else {
            // var_dump($patterns);
        }
        return $actions;
    }

    //批量操作其它UI
    public static function html()
    {
        $actions = self::getEtcs();
        $app = Admincp::$APP_NAME;
        /**
         * app/article/views/batch.html
         * app/article/views/category/batch.html
         * app/spider/views/project/batch.html
         */
        $parent = get_parent_class(Admincp::$APP_CLASS);
        $path = AdmincpView::display('batch', null, false);
        $path && is_file($path) && include $path;
        if ($parent == 'NodeAdmincp') {
            /**
             * app/node/pages/views/batch.html
             */
            $app = substr(Admincp::$APP_CLASS, 0, -7);
            $pieces = preg_split('/(?<=\w)(?=[A-Z])/', $app);
            $app = implode('/', $pieces);
            $path = AdmincpView::display('batch', $app, false);
            if($path && file_exists($path)){
                include $path;
            }else{
                $app = substr($parent, 0, -7);
                /**
                 * app/node/views/batch.html
                 */
                $path = AdmincpView::display('batch', $app, false);
                $path && file_exists($path) && include $path;
            }
        }
        if ($actions) {
            foreach ($actions as $key => $item) {
                if ($item['view']) {
                    $view = $item['view'];
                    ///app/article/views/batch.sss.html
                    $path = AdmincpView::display($app . '/batch.' . $view[0], $view[1] ?: $app, false);
                    // var_dump($app,AdmincpView::$TEMPLATE );
                    if ($path && is_file($path)) {
                        include $path;
                    }
                }
            }
        }
    }

    //生成批量操作菜单
    public static function btns()
    {
        $actions = self::getEtcs();
        $enable = isset(self::$config['enable']) ? self::$config['enable'] : true;
        $class = 'btn btn-alt-primary';
        include AdmincpView::display('widget/batch/btns', 'admincp');
    }
    public static $group = false;

    public static function group($app = null, $method = null)
    {
        self::$group = true;
        $actions = self::getEtcs($app, $method);
        $enable = isset(self::$config['enable']) ? self::$config['enable'] : true;
        $class = 'dropdown-item';
        include AdmincpView::display('widget/batch/group', 'admincp');
    }

    public static function run($actions, $title = null)
    {
        $batch = Request::post('batch');
        if (!AdmincpAuth::batch($batch)) {
            return true;
        }

        if ($from = Request::post('from')) {
            self::set('method', $from);
        }
        $config = self::getEtcs();
        $app = Admincp::$APP_NAME;
        $ids = self::getIds($title);
        $params = [];
        if (strpos($batch, '=') !== false) {
            parse_str($batch, $params);
            foreach ((array) $params as $k => $v) {
                $k = Security::safeStr($k);
                $v == 'now' && $v = time();
                $v = (int) $v;
                $params[$k] = $v;
            }
        } else {
            $params = Request::post('BatchData');
            unset($params['ids'], $params['batch']);
        }

        $item = $config[$batch];
        $call = $item['call'] ?: $batch;
        $callbale = $actions[$call] ?: $call;
        is_callable($callbale) or AdmincpBase::alert('批处理方法[' . $call . ']不存在');
        $result = iPHP::callback($callbale, [$ids, $params, $batch, $app], E_USER_ERROR);

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

    public static function getIds($title = null)
    {
        $msg = '请选择要操作的' . ($title ?: '项目');
        $ids = (array) $_POST['ids'];
        $ids or AdmincpBase::alert($msg);
        $ids = array_map('intval', $ids);
        return $ids;
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
