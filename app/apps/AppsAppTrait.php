<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

/**
 * 前台通用方法
 */
trait AppsAppTrait
{
    public static $app = null;
    public static $appid = 0;
    public static $status = '1';
    public static $config = [];
    public static $node = [];
    public static $model = null;
    // public static $APP = null;
    public static $PRIMARY = 'id';
    public static $SETS = [];

    // public $methods = ['index', 'clink', 'search', 'hits', 'vote', 'comment', 'lang'];

    /**
     * Undocumented function
     *
     * @param string $app
     * @param string $primary
     * @param OBJECT $model
     */
    public function __construct($app = null, $primary = null, $model = null)
    {
        parent::__construct();
        $this->inited($app, $primary, $model);
    }

    public function inited($app = null, $primary = null, $model = null)
    {
        empty($app) && $app = self::$APP;
        empty($app) && self::alert('$app is empty');
        
        if (is_object($model)) {
            self::$model = $model;
        } elseif (is_null($model)) {
            $model = $app . 'Model';
            self::$model = new $model();
        }
        self::$app = $app;
        self::$PRIMARY = $primary ?: self::$model->getPrimaryKey();
        self::$config = Config::get($app);
        // $this->addMethod($app);
    }

    public function do_index($param = null)
    {
        is_null($param) && $param = $this->gets();
        method_exists($this, 'display') or self::alert('Call to undefined method <b>' . get_called_class() . '->display()</b>', '1004');
        $cdn = Config::get('CDN');
        if ($cdn['enable']) {
            $expires = $cdn['expires'];
            @header('Cache-Control: ' . $cdn['cache_control'] . ', max-age=' . $expires);
            @header('Pragma: ' . $cdn['cache_control']);
            @header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
            @header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
        }
        return call_user_func_array([$this, 'display'], $param);
    }

    public function api_index($param = null)
    {
        return $this->do_index($param);
    }

    public function do_clink($param = null)
    {
        return $this->do_index($param);
    }

    public function do_search()
    {
        try {
            list($tpl, $app) = self::baseTpl('search', self::$app);
            $search = new SearchApp();
            return $search->display($tpl, $app);
        } catch (ViewEx $ex) {
            $msg = $ex->getMessage();
            self::alert($msg);
        }
    }

    public function api_search()
    {
        return $this->do_search();
    }

    public function api_comment()
    {
        $appid = (int) Request::get('appid');
        $cid = (int) Request::get('cid');
        $iid = (int) Request::get('iid');
        list($tpl, $app) = self::baseTpl('comment', self::$app);
        $param = [$iid, self::$PRIMARY, $tpl];
        return call_user_func_array([$this, 'display'], $param);
    }

    public function getData($value, $field = null, $flag = true)
    {
        $field === null && $field = self::$PRIMARY;
        $where = ['status' => self::$status, $field => $value];
        self::$SETS['cid'] && $where['cid'] = (int) self::$SETS['cid'];

        $data = self::$model->where($where)->get();
        if ($data) {
            $data['outurl'] = $data['url']?:'';
        }

        if ($flag !== true) {
            return $data;
        }

        $data or self::alert(['errors:not_found', [self::$app,$field, $value]], 10001);
        
        if ($data['url']) {
            View::$gateway == 'html' && Application::nil('html mode');
            self::updateHits(self::$app, $data['id']);
            Utils::jumpto($data['url']);
        }
        return $data;
    }
}
