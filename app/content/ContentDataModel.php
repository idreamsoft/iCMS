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

class ContentDataModel extends Model
{
    protected $casts = [
        'id' => 'as:cdata_id'
    ];
    protected $callback = array(
        // '42000' => 'aa',//无创建表权限
        // 'SQLSTATE:42S02' => array(__CLASS__, 'createTable'),//表不存在自动创建
    );
    public static $unionKey = null;
    public static function setUnionKey($app)
    {
        self::$unionKey  = AppsTable::getDataUnionKey($app);
    }
    public static function setTable($app)
    {
        $instance = self::getInstance();
        $instance->table = AppsTable::getDataTableName($app);
        return $instance;
    }
    public static function sharding($id)
    {
        // self::getInstance()->sharding = (int)$id % 10;
    }

    public static function createTable()
    {
        try {
            $target  = self::getTableName();
            $source  = self::getInstance()->table;
            return DB::copy($source, $target);
        } catch (sException $ex) {
            $state = $ex->getState();
            if ($state === '42000') { //无创建表权限
            }
            throw $ex;
        }
    }
    //从数据中的字段获多条值，以该值去获取多条节点数据
    public static function many($rs, $field)
    {
        if (empty($rs) || empty(self::$unionKey)) return array();

        $ids = array_column($rs, $field);
        $ids = array_unique($ids);
        $ids = array_filter($ids);
        $data = array();
        if ($ids) {
            $all = self::where([self::$unionKey => $ids])->select();
            if ($all) foreach ($all as $value) {
                $key = $value[self::$unionKey];
                $data[$key] = $value;
            }
        }
        return $data;
    }
    public static function getData($where)
    {
        $data = array();
        if (self::$unionKey) {
            $id = $where[self::$unionKey];
            self::sharding($id);
            try {
                $data = self::where($where)->get();
            } catch (\sException $ex) {
                $state = $ex->getState();
                if ($state == '42S02') { //表不存在
                    // ContentDataModel::createTable();
                } elseif ($state == '42S021') { //表不存在,创建成功
                }
            }
        }
        return $data;
    }
    public static function create($data)
    {
        $id = $data[self::$unionKey];
        self::sharding($id);
        try {
            return parent::create($data);
        } catch (\sException $ex) {
            $state = $ex->getState();
            if ($state == '42S02') { //表不存在
                $flag = self::createTable();
                if ($flag) return self::create($data);
                return false;
            } elseif ($state == '42S021') { //表不存在，但自动创建成功
                return self::create($data);
            } else {
                throw $ex;
            }
        }
    }
    public static function update($data, array $where)
    {
        if (array_key_exists(self::$unionKey, $where)) {
            $id = $where[self::$unionKey];
        } else {
            $id = $data[self::$unionKey];
        }
        self::sharding($id);
        try {
            return parent::update($data, $where);
        } catch (\sException $ex) {
            $state = $ex->getState();
            if ($state == '42S02') { //表不存在
                $flag = self::createTable();
                if ($flag) return self::update($data, $where);
            } elseif ($state == '42S021') { //表不存在，但自动创建成功
                return self::update($data, $where);
            } else {
                throw $ex;
            }
        }
    }
    public static function delete($id)
    {
        if (empty(self::$unionKey)) return;

        self::sharding($id);
        try {
            $field = self::$unionKey;
            return parent::delete($field, $id);
        } catch (\sException $ex) {
            $state = $ex->getState();
            if ($state == '42S02') { //表不存在
                return false;
            } elseif ($state == '42S021') { //表不存在，但自动创建成功
                return false;
            } else {
                throw $ex;
            }
        }
    }
}
