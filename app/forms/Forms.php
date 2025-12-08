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

    public static $primaryKey = 'id';
    public static $prefix = 'forms_';

    public static function routeRule($key, $params)
    {
        // var_dump($key, $params);
        list($a, $node, $_node) = $params;
        if ($key == 'FKEY') {
            return $a['app'];
        }
    }

    public static function getTableName($name)
    {
        return self::$prefix . iString::ltrim($name, self::$prefix);
    }

    public static function getMasterIndex()
    {
        return [
            // 'index_id' =>'KEY `id` (`status`,`id`)',
        ];
    }

    public static function get($vars = 0, $field = 'id')
    {
        if (empty($vars)) {
            return [];
        }
        if (is_array($vars)) {
            $vars = array_unique($vars);
        } else {
            !is_numeric($vars) && $field == 'id' && $field = 'app'; //非数字，使用app字段
        }

        $where[$field] = $vars;

        $key = md5(json_encode($where));
        return Cache::temp($key, function () use ($where, $vars, $field) {
            $model = FormsModel::where($where);
            if (is_array($vars)) {
                $array = $model->select();
                $result = [];
                foreach ($array as $key => $value) {
                    $result[$value[$field]] = Apps::item($value);
                }
            } else {
                $result = $model->find();
                Apps::item($result);
            }
            return $result;
        });
    }

    public static function delete($app)
    {
        is_array($app) or $app = self::get($app);
        if ($app) {
            //删除表单表
            self::dropTable($app['table']);
            //删除表单数据
            FormsData::delete($app['id']);
            //删除表单
            FormsModel::delete($app['id']);
        }
    }

    public static function sign($formid, $nonce, $timestamp)
    {
        return Utils::sign([
            'id'       => $formid,
            'token'    => md5(md5(iPHP_KEY)),
            'nonce'    => $nonce,
            'timestamp'=> $timestamp,
        ]);
    }

    public static function token()
    {
        $timestamp = $_SERVER['REQUEST_TIME'];
        $nonce = random(16);
        $pieces = [iPHP_KEY, $timestamp, $nonce];
        sort($pieces, SORT_STRING);
        $token = sha1(implode($pieces));
        return [$token, $timestamp, $nonce];
    }

    public static function dropTable($table)
    {
        if ($table) {
            foreach ((array) $table as $key => $value) {
                $value['table'] && DB::table($value['table'])->drop();
            }
        }
    }
}
