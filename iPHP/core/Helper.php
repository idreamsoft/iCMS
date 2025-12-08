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

class Helper
{
    public static $time_start = 0;

    public static function timerTask()
    {
        $timestamp = Cache::get('timerTask');
        //list($_today,$_week,$_month) = $timestamp ;
        $time = $_SERVER['REQUEST_TIME'];
        $today = get_date($time, 'Ymd');
        $yday = get_date($time - 86400 + 1, 'Ymd');
        $week = get_date($time, 'YW');
        $month = get_date($time, 'Ym');
        $timestamp[0] == $today or Cache::set('timerTask', [$today, $week, $month], 0);
        return [
            'yday'  => ($today - $timestamp[0]),
            'today' => ($timestamp[0] == $today),
            'week'  => ($timestamp[1] == $week),
            'month' => ($timestamp[2] == $month),
        ];
    }

    /**
     * Starts the timer, for debugging purposes
     */
    public static function timerStart()
    {
        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        self::$time_start = $mtime[1] + $mtime[0];
    }

    /**
     * Stops the debugging timer
     * @return int total time spent on the query, in milliseconds
     */
    public static function timerStop($restart = false)
    {
        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        $time_end = $mtime[1] + $mtime[0];
        $time_total = $time_end - self::$time_start;
        $restart && self::$time_start = $time_end;
        return round($time_total, 4);
    }
    /**
     * 解析注释
     *
     * @param  string $docBlock
     * @return array
     */
    private static function parseDocBlock(string $docBlock)
    {
        $annotations = [];
        if (preg_match_all('/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?[ \t]*\r?$/m', $docBlock, $matches)) {
            $numMatches = count($matches[0]);

            for ($i = 0; $i < $numMatches; $i++) {
                $annotations[$matches['name'][$i]][] = (string) $matches['value'][$i];
            }
        }

        return $annotations;
    }

    /**
     * 清理多行注释
     *
     * @param  string $docComment
     * @return string
     */
    private static function cleanUpMultiLine(string $docComment)
    {
        // removing initial '   * ' for docComment
        $docComment = str_replace("\r\n", "\n", $docComment);
        $docComment = preg_replace('/\n\s*\*\s?/', "\n", $docComment);
        // $docComment = (string) substr($docComment, 0, -1);
        $docComment = rtrim($docComment, '/');

        return rtrim($docComment, "\n");
    }

    /**
     * 获取注释
     *
     * @param  string $class  类名
     * @param  string $param 方法或者属性，属性需要加$前缀
     * @return string
     */
    public static function getDocComment($class, $param = null)
    {
        $class = new ReflectionClass($class);
        if ($param) {
            if (strpos($param, '$') !== false) {
                $param = substr($param, 1);
                // 获取属性的注释
                $property = $class->getProperty($param);
                $comment = $property->getDocComment();
            } else {
                // 获取方法的注释
                $method = $class->getMethod($param);
                $comment = $method->getDocComment();
            }
        } else {
            $param = 'class';
            // 获取类的注释
            $comment = $class->getDocComment();
        }

        $comment = self::cleanUpMultiLine($comment);
        $lines = preg_replace(['#^/\*{2}#', '#\*/$#'], '', preg_split('/\r\n|\r|\n/', $comment));
        $lines = array_map(function ($line) {
            return trim(preg_replace('/^\s*\*\s?/', '', $line));
        }, $lines);

        $filteredComments = array_filter($lines, function ($line) {
            if (empty($line)) {
                return false;
            }
            return !preg_match('/@(\w+)/', $line);
        });
        $filteredComments = array_values($filteredComments);

        $result = [];
        if ($filteredComments) {
            $title = $filteredComments[0];
            if (strpos($title, 'iCMS - i Content Management') !== false) {
                $title = '';
            }
            $result['title'] = $title;
            $result['comment'] = implode(PHP_EOL, $filteredComments);
        }

        $blocks = self::parseDocBlock($comment);
        $boolMap = [
            'true' => true, 'false'=> false,
            'yes'  => true, 'no'   => false
        ];
        foreach ($blocks as $name=> $values) {
            $result[$name] = $values;
            if (in_array($name, ['login', 'auth', 'run'])) {
                $pieces = preg_split('/\s+/', $values[0]);
                list($val, $desc) = $pieces;
                $result[$name] = $boolMap[$val];
            } elseif (in_array($name, ['role', 'uri', 'befor', 'after'])) {
                $datas = [];
                foreach ($values as $key => $text) {
                    $pieces = preg_split('/\s+/', $text);
                    list($val, $desc) = $pieces;
                    if($name=='uri'){
                        $datas[$val] = $desc;
                    }else{
                        $_val = strtolower($val);
                        if (isset($boolMap[$_val])) {
                            $datas[] = $boolMap[$_val];
                        } else {
                            if (strpos($val, ',') !== false) {
                                $val = explode(',', $val);
                                $datas = array_merge($datas, $val);
                            } else {
                                if (strpos($val, '::') !== false) {
                                    $val = explode('::', $val);
                                }
                                $datas[] = $val;
                            }
                        }                        
                    }
                }
                $result[$name] = $datas;
            }
        }
// var_dump($result);
        // foreach ($matches as $match) {
        //     $stype = $match[1];
        //     $text = $match[2];
        //     // preg_match_all('/(\\\\?[\w\\\\]+)\s+(\$\w+)\s*(?:\w+\s*)?/', $match[2], $paramMatches, PREG_SET_ORDER);
        //     $pieces = preg_split('/\s+/', $text);
        //     list($type, $name, $desc) = $pieces;
        //     $stype = strtolower($stype);
        //     $data = [
        //         'ref'   => $param,
        //         'name'  => $name,
        //         'type'  => strtolower($type), // 类型，可能是参数类型或返回类型
        //         'desc'  => isset($desc) ? trim($desc) : '', // 说明
        //     ];
        //     if (count($pieces) == 1 || in_array($stype, ['role', 'login', 'auth'])) {
        //         $typeMap = [
        //             'true' => true,
        //             'false'=> false,
        //             'yes'  => true,
        //             'no'   => false
        //         ];
        //         $_type = strtolower($type);
        //         if (isset($typeMap[$_type])) {
        //             $result[$stype] = $typeMap[$_type];
        //         } else {
        //             if (strpos($type, ',') !== false) {
        //                 $type = explode(',', $type);
        //             }
        //             $result[$stype] = $type;
        //         }
        //     } elseif (count($pieces) == 2) {
        //         if (strpos($name, '$') === false) {
        //             $data['type'] = 'string';
        //             $data['name'] = $type;
        //             $data['desc'] = $name;
        //         }
        //         $result[$stype][$data['name']] = $data;
        //     } elseif (empty($name)) {
        //         if (isset($result[$stype])) {
        //             $result[$stype][] = $data;
        //         } else {
        //             $result[$stype] = $data;
        //         }
        //     } else {
        //         $result[$stype][$name] = $data;
        //     }
        // }
        return $result;
    }
    public static function getAppComment($class, $method)
    {
        $doc = self::getDocComment($class, $method);
        if (empty($doc) && strpos($method, iPHP_POST_PREFIX) !== false) {
            $g_method = iPHP_GET_PREFIX . substr($method, strlen(iPHP_POST_PREFIX));
            if (method_exists($class, $g_method)) {
                $doc = self::getDocComment($class, $g_method);
            }
        }
        return $doc;
    }
    public static function ini($input)
    {
        $variable = is_array($input) ? $input : parse_ini_file($input, true);
        $result = [];
        foreach ($variable as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::ini($value);
            } else {
                $_arr = make_multi_array($key, $value);
                $result = array_merge_recursive_unique($result, $_arr);
            }
        }
        // array_walk_recursive($result, function (&$item, $key) {
        //     var_dump($item);
        //     if (strtolower($item) === 'false') {
        //         $item = false;
        //     }
        //     if (strtolower($item) === 'true') {
        //         $item = true;
        //     }
        // });
        return $result;
    }
}
