<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class AppsHooks
{
    public static $callback = null;

    public static function get()
    {
        return (array)Config::data(iCMS_APP_APPS, 'hooks');
    }

    public static function set($config)
    {
        return Config::save(iCMS_APP_APPS, 'hooks', $config);
    }

    /**
     * 查找根据$pat匹配到的类，包含符合$condition条件的方法
     *
     * @param   string $pat   glob的匹配表达式
     * @param   callable  $condition  判断符合执行的方法
     * @param   string  $exclude    排除的类
     *
     * @return Array $result 返回符合的数组
     */
    public static function find($pat = null, $condition = null, $exclude = null)
    {
        $pattern = Application::path('*', $pat);
        $files = glob($pattern);
        $result = [];
        if ($files) {
            foreach ($files as $filename) {
                $path = str_replace(iPHP_APP_DIR . '/', '', $filename);
                $class = basename($filename, '.php');
                if ($exclude && stripos($exclude, $class) !== false) {
                    continue;
                }
                if (class_exists($class)) {
                    $methods = get_class_methods($class);
                    $flag = false;
                    foreach ($methods as $key => $method) {
                        $call = [$class, $method];
                        is_callable($condition) && $flag = call_user_func_array($condition, $call);
                        $flag && $result[] = $call;
                    }
                } else {
                    // $result[] = false;
                }
            }
        }
        return $result;
    }

    /**
     * 执行根据$pat匹配到的类，包含符合$condition条件的方法
     *
     * @param   string $pat       [glob的匹配表达式]
     * @param   callable  $condition  [判断符合执行的方法]
     * @param   callable  $callback   [执行回调，如果没有设置直接执行]
     * @param   string  $exclude    [排除的类]
     *
     * @return Array $result [执行结果数组]
     */
    public static function run($pat = null, $condition = null, $params = [], $callback = null, $exclude = null)
    {
        $methods = self::find($pat, $condition, $exclude);
        $result = [];
        foreach ($methods as $key => $callable) {    
            $result[] = iPHP::callback($callable, $params);        
            // if (is_callable($callback)) {
            //     $res = iPHP::invoke($callback, function () use ($callable, $params) {
            //         return iPHP::invoke($callable, $params);
            //     });
            // } else {
            //     $res = iPHP::invoke($callable, $params);
            // }
            // $result[] = $res;
        }
        return $result;
    }

    /**
     * 执行应用字段钩子
     * @param  string $app      应用
     * @param  string $resource 资源
     * @param  array $hooks    钩子
     * @return void
     */
    public static function fields($app, &$resource = null, $hooks = [])
    {
        if ($hooks) {
            foreach ($hooks as $field => $callArr) {
                foreach ($callArr as $key => $call) {
                    $data = iPHP::callback($call, [$resource[$field], &$resource]);
                    is_null($data) or $resource[$field] = $data;
                }
            }
        }

        return $resource;
    }

    public static function field($app, &$resource = null, $hooks = [])
    {
        // if ($hooks) {
        //     foreach ($hooks as $field => $call) {

        //             $data = iPHP::callback($call, [$resource[$field], &$resource]);
        //             is_null($data) or $resource[$field] = $data;

        //     }
        // }

        return $resource;
    }

    /**
     * 查找包含所有**Hook类里带有钩子前缀方法
     *
     * @param   string $prefix   钩子前缀
     *
     * @return Array 返回符合的数组
     */
    public static function grep($prefix, $pat = '*Hook')
    {
        return AppsHooks::find($pat, function ($class, $method) use ($prefix) {
            return strpos($method, $prefix) === 0;
        });
    }

    /**
     * **Hooks类里带有run_**钩子
     * @return array
     */
    public static function getRun($prefix = 'run_')
    {
        //查找包含 **Hooks类里带有run_**方法
        $methods = AppsHooks::grep($prefix);
        if ($methods) {
            foreach ($methods as $key => $call) {
                if (!$call) {
                    continue;
                }
                list($_prefix, $text, $sort) = explode('_', $call[1]);
                preg_match('/([a-zA-z]+)/', $text, $match);
                $name = $match[1];
                $result[$name][] = $call;
            }
        }
        self::sortNum($result);
        return $result;
    }

    public static function sortNum(&$result)
    {
        foreach ($result as $key => &$call) {
            usort($call, function ($a, $b) {
                preg_match('/\d+/', $a[1], $a_num);
                preg_match('/\d+/', $b[1], $b_num);
                $an = isset($a_num[0]) ? (int)$a_num[0] : 10;
                $bn = isset($b_num[0]) ? (int)$b_num[0] : 10;
                if ($an == $bn) {
                    return  0;
                }
                return ($an < $bn) ? -1 : 1;
            });
        }
    }

    /**
     * **Hooks类里带有app_**_start/app_**_end钩子
     * @param  string $prefix 钩子前缀
     * @return array
     */
    public static function getHooks($prefix = 'app_')
    {
        $result = [];
        //查找包含 **Hooks类里带有app_**方法
        $methods = AppsHooks::grep($prefix);
        foreach ($methods as $key => $call) {
            if (!$call) {
                continue;
            }
            // 解析 $call[1] 获取 $pieces
            $pieces = explode('_', $call[1]);
            // 去掉前缀（第一个部分）
            array_shift($pieces);
            // 处理键冲突并赋值
            self::handleConflict($result, $pieces, $call);
        }

        return $result;
    }

    public static function handleConflict(&$result, $keys, $value)
    {
        $lastKey = array_pop($keys); // 取出最后一个键
        $current = &$result;

        // 遍历键，逐级构建数组
        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }

        // 处理最后一个键的冲突
        if (isset($current[$lastKey])) {
            // 如果最后一个键已存在，确保它是一个数组
            if (!is_array($current[$lastKey])) {
                $current[$lastKey] = [$current[$lastKey]];
            }
            // 追加新值
            $current[$lastKey][] = $value; // 存储整个 $value 数组
        } else {
            // 设置值
            $current[$lastKey][] = $value; // 存储整个 $value 数组
        }
    }
}
