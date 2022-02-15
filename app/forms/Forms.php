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

class Forms
{
    const APP = 'forms';
    const APPID = iCMS_APP_FORMS;

    public static $primaryKey   = 'id';
    public static $prefix = 'forms_';
    public static $DATA = array();

    public static function init($data)
    {
        self::$DATA = is_numeric($data) ? self::get($data) : $data;
        $table = self::getTableName(self::$DATA['app']);
        Content::model(self::$DATA, $table);
    }
    public static function getTableName($name)
    {
        return self::$prefix . iString::ltrim($name, self::$prefix);
    }
    public static function getMasterIndex()
    {
        return array(
            // 'index_id' =>'KEY `id` (`status`,`id`)',
        );
    }
    public static function get($vars = 0, $field = 'id')
    {
        if (empty($vars)) return array();
        if (is_array($vars)) {
            $vars = array_unique($vars);
        } else {
            !is_numeric($vars) && $field == 'id' && $field = 'app'; //非数字，使用app字段
        }

        $where[$field] = $vars;

        $hash = md5(json_encode($where));
        $result = Cache::$DATA[$hash];
        if (empty($result)) {
            iDebug::$DATA[__METHOD__][] = $where;
            $model = FormsModel::where($where);
            if (is_array($vars)) {
                $array  = $model->select();
                $result = array();
                foreach ($array as $key => $value) {
                    $result[$value[$field]] = Apps::item($value);
                }
            } else {
                $result = $model->find();
                Apps::item($result);

                // $hashId = md5(json_encode(array('id'=> $result['id'])));
                // Cache::$DATA[$hashId] = $result;
            }
            Cache::$DATA[$hash] = $result;
        }

        return $result;
    }
    public static function delete($app)
    {
        is_array($app) or $app = self::get($app);
        if ($app) {
            //删除表单表
            self::dropTable($app['table']);
            //删除表单数据
            self::deleteData($app['id']);
            //删除表单
            FormsModel::delete($app['id']);
        }
    }
    public static function dropTable($table)
    {
        if ($table) foreach ((array) $table as $key => $value) {
            $value['table'] && DB::table($value['table'])->drop();
        }
    }
    public static function deleteData($id = 0)
    {
        try {
            Content::delete($id);
            ContentDataModel::delete($id);
        } catch (\sException $ex) {
            //throw $th;
        }
        return true;
    }
    public static function data($id = 0, $dataId = 0, $userid = 0)
    {
        return Content::data($id, $dataId, $userid);
    }
}
