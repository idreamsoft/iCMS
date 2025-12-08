<?php

// namespace iPHP\core;
/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
class Etc
{
    public static $callback = null;
    public static $paths = [];

    public static function getBoot($app)
    {
        if (self::$callback) {
            return call_user_func_array(self::$callback, [$app]);
        } else {
            return iPHP::getAppPath($app);
        }
    }

    public static function path($app, $name = 'route', $flag = false)
    {
        $path = self::getBoot($app);
        //'menu/admincp.%s||menu/admincp.%s.*'
        if (strpos($name, '||') !== false) {
            $names = explode('||', $name);
            $result = [];
            foreach ($names as $key => $name) {
                $pattern = sprintf('%setc/%s.json', $path, $name);
                iPHP_DEBUG && iDebug::$DATA['Etc::path'][] = [$app, $name, $pattern];
                $files = glob($pattern, GLOB_BRACE);
                $result = array_merge($result, $files);
            }
            return $result;
        }
        $file = sprintf('%setc/%s.json', $path, $name);
        iPHP_DEBUG && iDebug::$DATA['Etc::path'][] = [$app, $name, $file];
        if ($flag) {
            return $file;
        }

        if ($app == '*' || strpos($name, '*') !== false) {
            return glob($file, GLOB_BRACE);
        } else {
            return is_file($file) ? $file : null;
        }
    }

    public static function exclude(&$paths, $filter = [])
    {
        is_array($filter) or $filter = (array)$filter;
        $paths = array_filter($paths, function ($file) use ($filter) {
            foreach ($filter as $key => $value) {
                if (strpos($file, $value) !== false) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * 读取多个配置文件
     *
     * @param string|array $app
     * @param string $name
     * @param boolean $flag
     * @param string $sort
     * @param array $exclude
     * @return array
     */

    public static $throw = true;

    public static function many($app, $name = 'route', $flag = false, $sort = 'sort', $exclude = [])
    {
        self::$paths = [];
        if (is_array($app)) {
            $paths = $app;
        } else {
            $paths = self::path($app, $name);
        }
        $exclude && self::exclude($paths, $exclude);
        iPHP_DEBUG && iDebug::$DATA['Etc::many'][$name][] = [
            $app,
            $name,
            $paths
        ];
        // var_dump($app, $name);
        $result = [];
        if ($paths) {
            $paths = (array)$paths;
            usort($paths, 'sortLen');

            foreach ($paths as $key => $path) {
                $key = short_md5($path);
                if (isset(self::$paths[$key])) {
                    continue;
                }
                if (is_file($path)) {
                    $json = file_get_contents($path);
                    $array = json_decode($json, true);
                    // var_dump($json);
                    // exit;
                    // var_dump($array);
                    // exit;
                    // $array = array_column($array, null, 'id');
                    if (json_last_error()) {
                        $msg = sprintf('%s %s', $path, json_last_error_msg());
                        // iDebug::$DATA['Etc::many']['json_last_error']=[$path,$msg];
                        if (self::$throw) {
                            throw new sException($msg);
                        } else {
                            iDebug::$DATA['Etc::many']['json_last_error'][] = [$path, $msg];
                            continue;
                        }
                    }
                    $path = str_replace(iPHP_APP_PATH, '', $path);
                    self::$paths[$key] = $path;
                    if ($array) {
                        if ($flag === true) {
                            $result = array_merge($result, $array);
                        } elseif ($flag === 'recursive') {
                            $result = custom_array_merge_recursive($result, $array);
                        } elseif ($flag === 'recursive2') {
                            $result = array_merge_recursive($result, $array);
                        } elseif ($flag === 1) {
                            $result[$path] = $array;
                        } else {
                            $result[] = $array;
                        }
                    }
                }
            }
            if ($flag === true) {
                $sort && sortKey($result, $sort);
            }
        }
        return $result;
    }

    public static function get($app, $name = 'route')
    {
        return self::data($app, $name);
    }

    public static function set($app, $name = 'route', $data = null)
    {
        return self::data($app, $name, $data);
    }

    public static function data($app, $name = 'route', $data = null)
    {
        $path = self::path($app, $name, true);

        if ($data === null) {
            if (is_file($path)) {
                $json = file_get_contents($path);
                return json_decode($json, true);
            }
            return [];
        } else {
            if (empty($data)) {
                return is_file($path) ? File::rm($path) : false;
            } else {
                File::mkdir(dirname($path));
                is_array($data) or $data = json_decode($data, true);
                // $json = jsonFormat($data);
                $json = json_encode($data, JSON_UNESCAPED_UNICODE);
                file_put_contents($path, $json);
                return $path;
            }
        }
    }

    public static function mergeRecursive($many, &$array, $unique = true)
    {
        foreach ($many as $source_array) {
            $array = self::smartMerge($array, $source_array);
        }
        $unique && array_walk($array, [__CLASS__, 'arrayUnique']);
    }

    private static function smartMerge($array1, $array2)
    {
        $result = $array1;

        foreach ($array2 as $key => $value) {
            if (isset($result[$key])) {
                if (is_array($result[$key]) && is_array($value)) {
                    // 如果两个都是数组，递归合并
                    $result[$key] = self::smartMerge($result[$key], $value);
                } else {
                    // 如果存在重复键但不是数组，创建数组来存储多个值
                    if (!is_array($result[$key])) {
                        $result[$key] = [$result[$key]];
                    }
                    $result[$key][] = $value;
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public static function arrayUnique(&$items)
    {
        if (is_array($items)) {
            foreach ($items as $key => $value) {
                if (is_array($value)) {
                    // 检查是否是关联数组（有字符串键）还是索引数组
                    $has_string_keys = false;
                    foreach (array_keys($value) as $k) {
                        if (is_string($k)) {
                            $has_string_keys = true;
                            break;
                        }
                    }

                    if ($has_string_keys) {
                        // 关联数组，递归处理
                        self::arrayUnique($value);
                        $items[$key] = $value;
                    } else {
                        // 索引数组，检查元素是否相同
                        $unique_values = array_unique($value, SORT_REGULAR);
                        if (count($unique_values) == 1 && !is_array(reset($unique_values))) {
                            // 所有值都相同且不是数组，取第一个值
                            $items[$key] = reset($unique_values);
                        } else {
                            $items[$key] = $unique_values;
                        }
                    }
                }
            }
        }
    }
}
