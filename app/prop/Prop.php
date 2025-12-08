<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class Prop
{
    const APP = 'Prop';
    const APPID = iCMS_APP_PLUGIN;

    const PREFIX = "prop";
    public static $sapp   = null;
    public static $field = null;
    public static $DATA = array();

    public static function app($app)
    {
        $self = new self;
        $self::$sapp = $app;
        return $self;
    }

    public static function cache()
    {
        $result = PropModel::select();
        foreach ((array) $result as $row) {
            if ($row['app']) {
                $app_field_id[$row['app'] . '/' . $row['field']][$row['id']] =
                    $app_field_val[$row['app']][$row['field']][$row['val']]   = $row;
            } else {
                $app_field_id[$row['field'] . '/id'][$row['id']] =
                    $app_field_val[$row['field']][$row['val']]       = $row;
            }
        }
        // prop/article/author=>id
        // prop/author/id
        foreach ((array) $app_field_id as $key => $a) {
            Cache::set(sprintf("%s/%s", self::PREFIX, $key), $a, 0);
        }
        // prop/article
        // prop/author
        foreach ((array) $app_field_val as $k => $a) {
            Cache::set(sprintf("%s/%s", self::PREFIX, $k), $a, 0);
        }
    }

    public static function getMap($class, $key)
    {
        $vkey = $key . 'Map';
        $class_vars = get_class_vars($class);
        $maps = [];
        if ($class_vars[$vkey]) {
            $maps = $class::${$vkey};
        }
        if ($arr = Prop::get($key)) {
            $maps = array_merge($maps, $arr);
        }
        return $maps;
    }
    public static function get($field, $app = "",$int=false)
    {
        self::$field = $field;
        $app or $app = Admincp::$APP_NAME;
        self::$sapp && $app = self::$sapp;
        $key = sprintf("%s/%s/%s", self::PREFIX, $app, $field);
        $propArray = Cache::get($key);
        $result = [];
        if ($propArray) foreach ((array) $propArray as $k => $P) {
            $v = $P['val'];
            if($int && !is_numeric($v)){
                $v = $P['id'];
            }
            $result[$v] = $P['name'];
        }
        return $result;
    }

    public static function deleteAppData($appid = null, $app = null)
    {
        if ($appid) {
            $where = compact('appid');
            PropModel::where($where)->delete();
            PropMapModel::where($where)->delete();

            if ($app) {
                $sql = PropModel::field('id')->where($where)->getSql();
                PropMapModel::where('node', 'in', DB::raw($sql))->delete();
                PropModel::where(compact('app'))->delete();
            }
        }
    }

    public static function create($data)
    {
        if ($data['field'] == 'prop_id' || $data['field'] == 'pid') {
            is_numeric($data['val']) or iPHP::alert($data['field'] . '字段的值只能用数字');
            $data['val'] = (int) $data['val'];
        }
        $where = array_filter_keys($data, 'app,val,field,cid');
        $id = PropModel::field('id')->where($where)->value();

        if ($id) return $id;

        return PropModel::create($data, true);
    }
    public static function delete($id = null, $appid = null, $iid = null)
    {
        if ($id) {
            $where = array();
            $appid && $where['appid'] = $appid;
            $rs = PropModel::where($where)->where('id', $id)->get();
            PropModel::where($where)->where('id', $id)->delete();
            $iid && $where['iid'] = $iid;
            PropMapModel::where($where)->where('node', $id)->delete();
            Cache::delete(self::PREFIX . '/' . $rs['field']);
        }
    }
}
