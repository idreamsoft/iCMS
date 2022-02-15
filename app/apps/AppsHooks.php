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
    public static function run($pat = null, $condition = null, $callback = null, $exclude = null)
    {
        $pattern = iAPP::path('*', $pat);
        $files = glob($pattern);
        if ($files) foreach ($files as $filename) {
            $path  = str_replace(iPHP_APP_DIR . '/', '', $filename);
            $class = basename($filename, '.php');
            if ($exclude && stripos($exclude, $class) !== false) {
                continue;
            }
            $methods = get_class_methods($class);
            $flag = false;
            foreach ($methods as $key => $method) {
                $params = [$class, $method];
                is_callable($condition) && $flag = call_user_func_array($condition, $params);
                if ($flag) {
                    if (is_callable($callback)) {
                        $res = iPHP::invoke($callback, $params);
                    } else {
                        $res = iPHP::invoke($params);
                    }
                    $result[$class][$method] = $res;
                }
            }
        }
        return $result;
    }

    /**
     * 获取带钩子APP
     * @param  [type] $app [description]
     * @return [type]      [description]
     */
    public static function appSelect($app = null)
    {
        foreach (Apps::getTableArray() as $key => $value) {
            list($path, $obj_name) = Apps::getPathArr($value['app'], 'app');
            if (is_file($path) && method_exists($obj_name, 'hooked') && $obj_name != 'AppsApp') {
                $selected = $app == $value['app'] ? 'selected' : '';
                $option[] = sprintf(
                    '<option value="%s" %s>%s:%s</option>',
                    $value['app'],
                    $selected,
                    $value['app'],
                    $value['name']
                );
            }
        }
        return implode('', (array) $option);
    }
    /**
     * 获取钩子APP字段 select
     */
    public static function appFieldsSelect()
    {
        foreach (Apps::getTableArray() as $a => $app) {
            $option = array();
            list($path, $obj_name) = Apps::getPathArr($app['app'], 'app');
            if ($app['table'] && is_file($path) && method_exists($obj_name, 'hooked')) {
                foreach ((array) $app['table'] as $key => $table) {
                    $name = $table['table'];
                    if (DB::hasTable($name)) {
                        $option[] = '<optgroup label="' . $table['label'] . '表">';
                        $fullFields  = DB::table($name)->fullFields();
                        foreach ((array) $fullFields as $field => $value) {
                            $text = ($value['comment'] ? $value['comment'] . ' (' . $field . ')' : $field);
                            $option[] = sprintf(
                                '<option value="%s">%s</option>',
                                $field,
                                $text
                            );
                        }
                        $option[] = '</optgroup>';
                    }
                }
                if ($option) {
                    printf(
                        '<select id="app_%s_select" class="hide">%s</select>',
                        $app['app'],
                        implode('', (array) $option)
                    );
                }
            }
        }
    }

    /**
     * 获取APP、插件等可用钩子
     * @return [type] [description]
     */
    public static function appMethod()
    {
        $files = glob(iPHP_APP_DIR . "/*/*Hook.php");
        $option = '';
        if ($files) foreach ($files as $key => $file) {
            $obj_name = basename($file, '.php');
            $option .= self::getHookMethod($obj_name);
        }
        return $option;
    }
    /**
     * 获取app钩子
     * @param  [type] $obj_name [description]
     * @return [type]           [description]
     */
    public static function getHookMethod($obj_name = null)
    {
        $class_methods = get_class_methods($obj_name);
        foreach ($class_methods as $key => $method) {
            if (stripos($method, 'run_') !== false || $method == "run") {
                $doc = iPHP::getDocComment($obj_name, $method);
                $title = $doc['desc'] ?: $obj_name . '::' . $method;
                $option[] = sprintf(
                    '<option value="%s::%s">%s</option>',
                    $obj_name,
                    $method,
                    $title
                );
            }
        }

        return implode('', (array) $option);
    }
    /**
     * [应用字段钩子]
     * @param  [type] $app      [应用]
     * @param  [type] $resource [资源]
     * @param  [type] $hooks    [钩子]
     * @return [type]           [description]
     */
    public static function fields($app, &$resource = null, $hooks)
    {
        if ($hooks) foreach ($hooks as $field => $callArr) {
            foreach ($callArr as $key => $call) {
                $data = iPHP::callback($call, array($resource[$field], &$resource), 'nohook');
                $data != 'nohook' && $resource[$field] = $data;
            }
        }

        return $resource;
    }
}
