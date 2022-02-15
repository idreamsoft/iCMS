<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class MenuHelper
{
    const SORT_WEIGHT = 100;
    public static $ACCESS_KEY = 0;
    public static function app($id)
    {
        try {
            $app  = Apps::getData($id);
            $array = array();
            $app['menu'] && $array = MenuHelper::get($app);  
            return $array;
        } catch (\FalseEx $ex) {
            return [];
        }
    }
    public static function get($apps)
    {
        $menus = [];
        $pos = $apps['menu'];
        $app = $apps['app'];
        $pattern = 'menu/admincp*';
        if ($apps['apptype'] == Apps::CONTENT_TYPE) {
            $app = 'content';
            $pattern = sprintf('menu/admincp.%s||menu/admincp.%s.*',$apps['app'],$apps['app']) ;
            // var_dump($apps['app'],$pattern);
        }
        if ($menus = Etc::many($app,$pattern)) {
            empty($pos) && $pos = 'default';
            self::pos($menus, $pos);
            self::merge($menus);
            // self::vars($menus, $apps);
            $sort = $apps['id'] * self::SORT_WEIGHT;
            $menus = self::mArray($menus, $sort);
        }
        return $menus;
    }
    public static function mArray($vars, $sort = 0, $parent = null, $level = 0)
    {
        if ($vars) foreach ($vars as $k => $v) {
            ++$sort;
            $key = $v['id'] ?: $k;
            isset($v['sort']) or $v['sort'] = $sort;
            //权限
            $v['access'] = $v['id'] ? $v['id'] : $v['href'];
            if ($v['caption'] == "-") {
                $v['access'] = $parent . '-' . self::$ACCESS_KEY . '-' . $level;
                ++$level;
                ++self::$ACCESS_KEY;
            }

            $array[$key] = $v;
            if ($v['children']) {
                $array[$key]['children'] = self::mArray($v['children'], $sort, $v['id'], $level);
            }
        }
        return $array;
    }
    public static function pos(&$menu, $pos)
    {
        $data = $menu[0][0];
        if ($pos == 'main') {
            $menu = [$data['children']];
        } elseif ($pos != 'default') {
            if (isset($data['id']) && isset($data['children']) && !isset($data['caption'])) {
                $menu[0][0]['id'] = $pos;
            } else {
                $json = sprintf('[{"id": "%s","children":[]}]', $pos);
                $array = json_decode($json, true);
                $array[0]['children'][0] = $menu[0][0];
                $array[1] = $menu[0][1];
                $menu = [$array];
            }
        }
    }

    public static function arrayUnique(&$items)
    {
        if (is_array($items)) {
            foreach ($items as $key => $value) {
                if (in_array($key, array('id', 'name', 'icon', 'caption', 'sort', 'access'))) {
                    if(is_array($value)){
                        $value = array_unique($value);
                        arsort($value);
                        $items[$key] = reset($value);
                    }
                }
                if (is_array($items['children'])) {
                    array_walk($items['children'], __METHOD__);
                }
            }
        }
    }
    public static function children(&$array)
    {
        $array = array_column($array, null, 'id');
        foreach ($array as $key => &$value) {
            if ($value["children"]) {
                self::children($value["children"]);
            }
        }
    }
    public static function merge(&$array)
    {
        $result = array_shift($array);
        self::children($result);
        if ($array) foreach ($array as $key => $value) {
            self::children($value);
            $result = array_merge_recursive($result, $value);
        }
        array_walk($result, array(__CLASS__, 'arrayUnique'));
        $array = $result;
    }

    public static function vars(&$menu, $a)
    {
        $json = json_encode($menu);
        $json  = htmlspecialchars_decode($json);
        $json = str_replace(
            array('{appid}', '{app}', '{name}', '{title}', '{sort}'),
            array($a['id'], $a['app'], $a['name'], $a['title'], $a['id'] * self::SORT_WEIGHT),
            $json
        );
        $menu = json_decode($json, true);
    }
    public static function sortData(&$menuArray){
        foreach ($menuArray as $key => &$value) {
            if($value['children']){
                $children = [];
                foreach ($value['children'] as $idx => $val) {
                    $children[] = $val;
                }
                $value['children'] = $children;
                self::sortData($value['children']);
            }
        }
    }
}
