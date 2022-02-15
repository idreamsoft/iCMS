<?php

/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
require_once __DIR__ . '/database/src/Builder.php';

// class Model extends Builder {
class Model
{
    /**
     * 模型的连接名称
     *
     * @var string
     */
    protected $connection;
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table;
    /**
     * 重定义主键
     *
     * @var string
     */
    protected $primaryKey;
    /**
     * 重定义查询字段
     *
     * @var string
     */
    protected $fields;
    /**
     * 类型转换
     *
     * @var string
     */
    protected $casts;

    /**
     * 模型的默认属性值。
     *
     * @var array
     */
    protected $attributes;
    /**
     * 模型的事件映射
     *
     * @var array
     */
    protected $events;
    /**
     * 记录query
     */
    protected $queryLog = false;
    /**
     * 追踪query运行
     */
    protected $queryTrace = false;
    /**
     * 模型的分表号。
     *
     * @var numeric
     */
    protected $sharding;
    /**
     * 模型单实例
     */
    public static $instance = array();

    protected static $_model;
    protected static $_vars;
    protected static $_methods;

    public function __construct($model = null, $vars = null, $methods = null)
    {
        is_null($model) && $model = get_called_class();
        self::$_model = $model;
        $vars && self::$_vars[$model]    = $vars;
        $methods && self::$_methods[$model] = $methods;
        $vars && $this->vars = $vars;
        self::$instance[$model] = $this;
    }

    public static function postData()
    {
        $args = func_get_args();
        $post = Request::post();
        $fullFields = self::fullFields();
        $types = array_column($fullFields, 'type', 'field');
        $keys = array_keys($fullFields);
        $args && $keys = array_merge($keys, $args);
        $defaults = self::defaults();
        $result = array();
        foreach ($keys as $idx => $key) {
            $result[$key] = isset($post[$key]) ? $post[$key] : $defaults[$key];
        }
        $pk = self::getPrimaryKey();
        if (empty($result[$pk])) unset($result[$pk]);
        self::castData($result, $types);
        return $result;
    }
    public static function check($where, $pv = null)
    {
        // $args = func_get_args();
        $pk = self::getPrimaryKey();
        $pv && $where[$pk] = array('<>', $pv);
        return self::field($pk)->where($where)->value();
    }
    /**
     * 数据类型转换，当前只转换 int类型
     */
    public static function castData(&$data, $types)
    {
        foreach ($data as $key => $val) {
            $type = $types[$key];
            if (in_array($type, array('BIGINT', 'INT', 'MEDIUMINT', 'SMALLINT', 'TINYINT'))) {
                empty($val) && $val = 0;
                strtotime($val)===false && $data[$key] = (int)$val;
            }
        }
    }
    public static function makeData($data, $fields = null)
    {
        $result = array();
        foreach ($fields as $key => $val) {
            is_numeric($key) && $key = $val;
            if (isset($data[$key])) {
                $result[$key] = $data[$key];
            } else {
                $result[$key] = $val;
            }
        }
        return $result;
    }
    /**
     * @return Builder
     */
    public function __call($method, $params)
    {
        return self::called($method, $params);
    }
    /**
     * @return Builder
     */
    public static function __callStatic($method, $params)
    {
        return self::called($method, $params);
    }
    /**
     * @return Builder
     */
    public static function called($method, $params)
    {
        // $model = get_called_class();
        // $rc = new ReflectionClass($model);
        // if ($rc->hasMethod($method)) {
        //     $instance = self::getInstance($model);
        //     call_user_func_array(array($instance, $method), $params);
        // }

        $rc = new ReflectionClass('Builder');
        if ($rc->hasMethod($method)) {
            try {
                $builder = static::makeBuilder();
                $call    = array($builder, $method);
                $result  = call_user_func_array($call, $params);
                return $result;
            } catch (\sException $ex) {
                throw $ex;
            }
        } else {
            throw new sException("Call to undefined method Model::{$method}", -1);
        }
    }
    public static function getInstance($model = null)
    {
        is_null($model) && $model = get_called_class();
        if (!self::$instance[$model]) {
            self::$instance[$model] = new $model();
        }
        return self::$instance[$model];
    }
    public static function setTable($table)
    {
        $instance = self::getInstance();
        $instance->table = $table;
        return $instance;
    }
    public static function reset($model = null)
    {
        if ($model) {
            self::$instance[$model] = null;
        } else {
            self::$instance = null;
        }
    }
    /**
     * @return Builder
     */
    public static function makeBuilder()
    {
        $model = get_called_class();
        if (self::$_model && $model == __CLASS__ && self::$_model != __CLASS__) {
            $model      = self::$_model;
            $vars       = self::$_vars[$model];
            $methods    = self::$_methods[$model];
        } else {
            $vars       = get_class_vars($model);
            $methods    = get_class_methods($model);
        }
        if (empty(self::$_vars[$model]) && self::$instance[$model]) {
            $vars = get_object_vars(self::$instance[$model]);
        }
        if ($methods) {
            in_array('init', $methods) && call_user_func([$model, 'init']);
        }
        return self::builder($model, $vars, $methods);
    }
    /**
     * @return Builder
     */
    public static function builder($model, $vars, $methods)
    {
        $connection = $vars['connection'];
        $table      = $vars['table'] ?: $model;
        $primaryKey = $vars['primaryKey'];
        $fields     = $vars['fields'];
        $casts      = $vars['casts'];
        $sharding   = $vars['sharding'];
        $events     = $vars['events'];
        $callback   = $vars['callback'];
        $queryLog   = $vars['queryLog'];
        $queryTrace = $vars['queryTrace'] ? 2 : 1;

        $queryLog && DB::debug($queryTrace);
        $table = self::table($table, $sharding);
        $builder = DB::connection($connection)->table($table);
        $primaryKey && $builder->setPrimaryKey($primaryKey);
        $fields     && $builder->setFields($fields);
        $casts      && $builder->setCasts($casts);
        $callback   && $builder->setEvents('called', $callback);

        if ($events) foreach ($events as $method => $func) {
            self::attrBind($method, $func, $builder);
        }
        if ($methods) foreach ($methods as $key => $method) {
            self::attrBind($method, array($model, $method), $builder);
        }

        return $builder;
    }
    /**
     * 绑定事件
     */
    private static function attrBind($method, $callfunc, $builder)
    {
        $array = array();
        foreach (['geted', 'updated', 'created', 'deleted', 'changed', 'event'] as $event) {
            if (stripos($method, $event) !== false) {
                $field = iString::rtrim($method, ucfirst($event));
                $array[$event][$field] = $callfunc;
            }
        }
        $builder->setEvents($array);
        return $array;
    }
    public static function table($name, $sharding = NULL)
    {
        if (substr($name, -5, 5) == 'Model') {
            $name = substr($name, 0, -5);
        } else {
            // $pieces[] = $name;
        }
        $pieces = preg_split('/(?<=\w)(?=[A-Z])/', $name);
        is_numeric($sharding) && $pieces[] = 'p' . $sharding;
        return strtolower(implode('_', $pieces));
    }
}
