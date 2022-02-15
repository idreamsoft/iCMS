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
class Hooks
{
    public static $MAP = array();
    public static function loader($class)
    {
    }
    public static function get($key = null, $key2 = null)
    {
        $map = self::$MAP;
        if (is_null($key) && is_null($key2)) {
            return $map;
        }
        $keyArray = explode('.', $key);
        foreach ($keyArray as $k) {
            $map = $map[$k];
        }
        (is_string($key2) && is_array($map)) && $map = $map[$key2];
        return $map;
    }
    public static function set($key = null, $fn = null)
    {
        //::set()
        if (is_null($key) && is_null($fn)) {
            self::$MAP = null;
        } else if (is_array($key) && is_null($fn)) {
            //::Set([])
            self::$MAP = $key;
        } else {
            //::Set('a','v')
            //::Set('a.b','vv')
            //::Set('a.b.c','vvv')
            $map = make_multi_array($key, $fn);
            if (is_null($fn)) {
                self::$MAP = array_replace_recursive(self::$MAP, $map);
            } else {
                self::$MAP = array_merge_recursive(self::$MAP, $map);
            }
        }
        return self::$MAP;
    }
    public static function map($map)
    {
        self::$MAP = $map;
    }
    public static function add($name, $fn)
    {
        self::$MAP[$name] = $fn;
    }
    /**
     * 静默执行
     *
     * @param   [type]$fn      [$fn description]
     * @param   [type]$params  [$params description]
     * @param   null           [ description]
     *
     * @return  [type]         [return description]
     */
    public static function run($fn, $params = [])
    {
        is_array($fn) or $fn = self::$MAP[$fn];
        return iPHP::callback($fn, $params);
    }

    public static function call($fn, $params = [])
    {
        $call = self::get($fn);
        // var_dump($call, $params);
        // return iPHP::callfunc($call, $params);
        return iPHP::callback($call, $params, E_USER_ERROR);
    }
}
