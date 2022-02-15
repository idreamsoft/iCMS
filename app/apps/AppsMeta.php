<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class AppsMeta extends Model
{

    // const DDL = 'CREATE TABLE `%s` (
    //               `id` int(10) unsigned NOT NULL,
    //               `data` mediumtext NOT NULL,
    //               PRIMARY KEY (`id`)
    //             ) ENGINE=InnoDB DEFAULT CHARSET=%s';

    public static $target = array();
    public static $data = array();
    public static $app = null;

    protected $casts = array('data' => 'array');
    protected $callback =  array(
        'SQLSTATE:42S02' => array(__CLASS__, 'createTable'), //表不存在自动创建
    );

    public static function model($app)
    {
        if (is_numeric($app)) {
            $a   = Apps::get($app);
            $app = $a['app'];
        } elseif (is_array($app)) {
            $app = $app['app'];
        }
        self::$app = $app;

        $instance = self::getInstance();
        $instance->table = $app . 'MetaModel';
        return $instance;
    }

    public static function data($app, $ids)
    {
        $result = array();
        try {
            if (empty($ids)) return array();

            $where['id'] = $ids;
            $model = self::model($app)->where($where);
            if (is_numeric($ids)) {
                $result = $model->value('data');
            } else {
                $array  = $model->select();
                if ($array) foreach ($array as $key => $value) {
                    $result[$value['id']] = $value['data'];
                }
            }
        } catch (\Exception $ex) {
        }
        return $result;
    }
    public static function post($pkey = 'metadata')
    {
        $postdata = (array)Request::post($pkey);
        $metadata = array();
        if ($postdata) foreach ($postdata as $mdk => $md) {
            if (is_array($md)) {
                if ($md['name'] && empty($md['key'])) {
                    $md['key'] = strtolower(Pinyin::get($md['name']));
                }
                preg_match("/^[a-zA-Z0-9_\-\.:]+$/", $md['key']) or Script::alert('字段名不能为空,只能由英文字母、数字或_-组成,不支持中文');
                $md['key'] = trim($md['key']);
                $metadata[$md['key']] = $md;
            } else {
                $metadata[$mdk] = array('name' => $mdk, 'key' => $mdk, 'value' => $md);
            }
        }
        return $metadata;
    }
    public static function getTables($app)
    {
        $table = $app . '_meta';
        return array($table => array($table, 'id', null, '动态属性'));
    }
    /**
     * 创建表
     */
    public static function createTable()
    {
        try {
            $model = self::model(self::$app);
            $target = $model->getTableName();
            if (!DB::hasTable($target)) {
                $source = $model->table(__CLASS__);
                $flag  = DB::copy($source, $target);
                $flag && self::setConfig(self::$app);
            }
            return $flag;
        } catch (sException $ex) {
            $state = $ex->getState();
            // var_dump($state);
            //42000 无创建表权限
            //42S02 app_meta 表不存在
            if ($state === '42000' || $state === '42S02') {
                throw $ex;
            }
            return false;
        }
    }
    public static function get($app, $id = 0)
    {
        $data = array();
        try {
            $where['id'] = $id;
            $data = self::model($app)->where($where)->value('data');
            if ($data) foreach ($data as $key => $value) {
                if (is_array($value)) {
                    unset($data[$key]);
                    $data[$value['key']] = $value;
                }
            }
        } catch (\sException $ex) {
            $state = $ex->getState();

            if ($state == '42S021') { //表不存在,但自动创建成功
                // self::get($app, $id);
            } elseif ($state === '42S02') { //表不存在
            } else {
                throw $ex;
            }
        }
        self::$data = $data;
        return $data;
    }

    public static function save($app, $id, $data = null)
    {
        try {
            is_null($data) && $data = self::post();
            $model = self::model($app);
            $check = $model->field('id')->where($id)->value();
            if ($check) {
                $model->update(compact('data'), $id);
            } else {
                $model->create(compact('id', 'data'));
            }
        } catch (\sException $ex) {
            $state = $ex->getState();
            // var_dump($state);
            if ($state == '42S02') { //表不存在
            } elseif ($state === '42S021') { //表不存在,但自动创建成功
                self::save($app, $id, $data);
            } else {
                throw $ex;
            }
        }
    }

    public static function setConfig($app)
    {
        Config::data(iCMS_APP_APPS, 'APPS:META');
        Config::$data[$app] = 1;
        Config::save(iCMS_APP_APPS, 'APPS:META');
    }
    public static function cache()
    {
        $tables = DB::tables();
        $prefix = DB::getTablePrefix();
        $config = array();
        foreach ($tables as $key => $name) {
            if (stristr($name, '_meta') !== false) {
                $name = str_replace($prefix, '', $name);
                $name = str_replace('_meta', '', $name);
                $config[$name] = 1;
            }
        }
        Config::$data = $config;
        Config::save(iCMS_APP_APPS, 'APPS:META');
        // iJson::success('保存成功');
    }
    public static function makeHtml($idx = null, $data = array(), $field = 'metadata', $func = null)
    {
        ob_start();
        include AdmincpView::display("apps.meta.td", "apps");
        $html = AdmincpView::html();
        $idx === null or $html = str_replace('{key}', $idx, $html);
        $data && $html = str_replace('disabled="disabled"', '', $html);
        if ($func) {
            $html .= call_user_func_array($func, array($idx));
        }
        return $html;
    }
    public static function display()
    {
        include AdmincpView::display("apps.meta", "apps");
    }
}
