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

    public static $target = [];
    public static $data = [];
    public static $app = null;
    public static $appId = null;
    public static $id = null;

    protected $casts = ['data' => 'array'];
    protected $callback = [
        'SQLSTATE:42S02' => [__CLASS__, 'createTable'], //表不存在自动创建
    ];

    public static function model($app)
    {
        if (is_numeric($app)) {
            $a = Apps::get($app);
            $app = $a['app'];
        } elseif (is_array($app)) {
            $app = $app['app'];
        }
        self::$app = $app;

        $instance = self::getInstance();
        $instance->table = $app . 'MetaModel';
        return $instance;
    }

    public static function withData($data){
        return array_map(function($items){
            return array_map(function($item){
                if($item['ui'] == 'cms:ui:file:btns'){
                    $pic = FilesPic::getArray($item['value']);
                    $item['url'] = $pic['url'];
                }
                return $item;
            }, $items);
        }, $data);
    }
    public static function data($app, $ids, &$data = null)
    {
        $result = [];
        try {
            if (empty($ids)) {
                return [];
            }

            $where['id'] = $ids;
            $model = self::model($app)->where($where);
            if (is_numeric($ids)) {
                $result = $model->value('data');
                if (is_array($data) && $result) {
                    $data['METADATA']['mode'] = $result['mode'];
                    $data['METADATA']['data'] = self::withData($result['metadata']);
                    if(!$result['mode'] && $result['metadata']){
                        $data['METADATA']['data'] = array_column($result['metadata'][0],null,'key');
                    }
                }     
            } else {
                $array = $model->select();
                if ($array) {
                    foreach ($array as $key => $value) {
                        $result[$value['id']] = $value['data'];
                        if (is_array($data)) {
                            $data[$value['id']]['METADATA'] = [
                                'mode'=> $value['data']['mode'],
                                'data'=> self::withData($value['data']['metadata']),
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $ex) {
        }

        return $result;
    }

    public static function post($pkey = 'metadata')
    {
        self::$appId = Request::post($pkey . '_appid');
        self::$id = Request::post($pkey . '_id');
        $postdata = (array)Request::post($pkey);
        $metadata = [];
        if ($postdata) {
            foreach ($postdata as $mdk => $md) {
                if (is_array($md)) {
                    if ($md['name'] && empty($md['key'])) {
                        $md['key'] = strtolower(Pinyin::get($md['name']));
                    }
                    empty($md['key']) && AdmincpBase::alert('字段不能为空');
                    !preg_match("/^[a-zA-Z0-9_\-\.:]+$/", $md['key']) && AdmincpBase::alert('字段只能由英文字母、数字或_-组成,不支持中文');
                    $md['key'] = trim($md['key']);
                    $metadata[$md['key']] = $md;
                } else {
                    $metadata[$mdk] = ['name' => $mdk, 'key' => $mdk, 'value' => $md];
                }
            }
        }
        return $metadata;
    }

    public static function getTables($app)
    {
        $table = $app . '_meta';
        return [$table => [$table, 'id', null, '动态属性']];
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
                $flag = DB::copy($source, $target);
                $flag && self::setConfig(self::$app);
            }
            return true;
        } catch (\PDOException $ex) {
            $code = $ex->getCode();
            // var_dump($state);
            //42000 无创建表权限
            //42S02 app_meta 表不存在
            if ($code === '42000' || $code === '42S02') {
                throw $ex;
            }
            return false;
        }
    }

    public static function get($app, $id = 0, $flag = false)
    {
        $data = [];
        try {
            $where['id'] = $id;
            $data = self::model($app)->where($where)->value('data');
            if ($flag) {
                return $data;
            }
            if ($data) {
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        // var_dump($value);
                        unset($data[$key]);
                        $data[$value['key']] = $value;
                    }
                }
            }
        } catch (\PDOException $ex) {
            $code = $ex->getCode();
            if ($code == '42S021') { //表不存在,但自动创建成功
                // self::get($app, $id);
            } elseif ($code === '42S02') { //表不存在
                self::createTable();
            } else {
                throw $ex;
            }
        }
        self::$data = $data;
        self::$appId = $app;
        self::$id = $id;

        return $data;
    }

    public static function saveData($app, $id, $data = null)
    {
        empty($id) && iPHP::alert('MetaData ID is empty');
        is_null($data) && $data = self::post();
        if($data['metadata']){
            $data['metadata'] = array_map(function($items){
                $items = array_map(function($item){
                    if($item['ui'] && $item['ui']=='cms:ui:editor'){
                        $item['value'] = htmlspecialchars_decode($item['value']);
                    }
                    return $item;
                },$items);
                return $items;
            },$data['metadata']);
        }
        try {
            $model = self::model($app);
            $check = $model->field('id')->where($id)->value();
            if ($check) {
                $model->update(compact('data'), $id);
            } else {
                $model->create(compact('id', 'data'));
            }
        } catch (\PDOException $ex) {
            $code = $ex->getCode();
            // var_dump($state);
            if ($code == '42S02') { //表不存在
            } elseif ($code === '42S021') { //表不存在,但自动创建成功
                self::saveData($app, $id, $data);
            } else {
                throw $ex;
            }
        }
    }

    public static function setConfig($app)
    {
        $data = Config::data(iCMS_APP_APPS, 'APPS:META');
        $data[$app] = 1;
        Config::save(iCMS_APP_APPS, 'APPS:META', $data);
    }

    public static function cache()
    {
        $tables = DB::tables();
        $prefix = DB::getTablePrefix();
        $config = [];
        foreach ($tables as $key => $name) {
            if (stristr($name, '_meta') !== false) {
                $name = str_replace($prefix, '', $name);
                $name = str_replace('_meta', '', $name);
                $config[$name] = 1;
            }
        }
        Config::save(iCMS_APP_APPS, 'APPS:META', $config);
        // iJson::success('保存成功');
    }

    public static function makeHtml($idx = null, $data = [], $field = 'metadata', $func = null)
    {
        ob_start();
        include AdmincpView::display('apps.meta.td', 'apps');
        $html = AdmincpView::html();
        $idx === null or $html = str_replace('{key}', $idx, $html);
        $data && $html = str_replace('disabled="disabled"', '', $html);
        if ($func) {
            $html .= call_user_func_array($func, [$idx]);
        }
        return $html;
    }
}
