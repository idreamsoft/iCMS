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

class ModelShard extends Model
{
    protected $casts = [
    ];
    protected $callback = [
        // '42000' => 'aa',//无创建表权限
        'SQLSTATE:42S02' => [__CLASS__, 'createTable'], //表不存在自动创建
    ];
    public static $unionKey = '';
    public static $appid = 0;

    public static function sharding($indexId)
    {
        $model = self::getInstance();
        $model->sharding = (int)$indexId % 10;
        return $model;
    }

    public static function createTable()
    {
        try {
            $target = self::getTableName();
            $source = self::table(__CLASS__);
            DB::copy($source, $target);
            return true;
        } catch (sException $ex) {
            $state = $ex->getState();
            if ($state === '42000') { //无创建表权限
                throw $ex;
            }
            return false;
        }
    }

    public static function getFields($update = false)
    {
        $fields = parent::getFields();
        $to_remove = ['id'];
        if ($update) {
            // 定义要删除的值
            $to_remove = ['id', static::$unionKey];
        }
        // 使用 array_diff 删除指定值
        $fields = array_diff($fields, $to_remove);
        return $fields;
    }

    public static function getValue($indexId, $field)
    {
        self::sharding($indexId);
        $result = [];
        try {
            $result = self::field($field)->where(static::$unionKey, $indexId)->pluck($field);
        } catch (\sException $ex) {
            $state = $ex->getState();
            if ($state == '42S02') { //表不存在
                return false;
            } elseif ($state === '42S021') { //表不存在,但自动创建成功
                return false;
            } else {
                throw $ex;
            }
        }
        return $result;
    }

    public static function getIds($indexId = 0)
    {
        return self::getValue($indexId, 'id');
    }

    public static function create($data)
    {
        if (!array_key_exists(static::$unionKey, $data)) {
            throw new sException('need a param of ' . static::$unionKey . ' In Data', 1);
        }
        $indexId = $data[static::$unionKey];
        self::sharding($indexId);
        try {
            return parent::create($data);
        } catch (\sException $ex) {
            $state = $ex->getState();
            // var_dump($state);
            if ($state == '42S02') { //表不存在
                $flag = self::createTable();
                if ($flag) {
                    return self::create($data);
                }
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
        if (array_key_exists(static::$unionKey, $where)) {
            $indexId = $where[static::$unionKey];
        } elseif (array_key_exists(static::$unionKey, $data)) {
            $indexId = $data[static::$unionKey];
        } else {
            throw new sException('need a param of ' . static::$unionKey . ' In Data OR Where', 1);
        }
        self::sharding($indexId);
        try {
            return parent::update($data, $where);
        } catch (\sException $ex) {
            $state = $ex->getState();
            if ($state == '42S02') { //表不存在
                $flag = self::createTable();
                if ($flag) {
                    return self::update($data, $where);
                }
            } elseif ($state == '42S021') { //表不存在，但自动创建成功
                return self::update($data, $where);
            } else {
                throw $ex;
            }
        }
    }

    public static function delete($indexId, $id = 0, $field = '')
    {
        self::sharding($indexId);
        !$field && $field = static::$unionKey;
        try {
            $field == static::$unionKey && $id = $indexId;
            return parent::delete($id, $field);
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

    public static function sql($indexId = 0)
    {
        $model = self::sharding($indexId);
        try {
            return $model->field('*')->where(static::$unionKey, $indexId)->getSql();
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
