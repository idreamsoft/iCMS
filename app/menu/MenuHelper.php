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

    public static function app($id)
    {
        try {
            $app = Apps::getData($id);
            $array = [];
            $app['menu'] && $array = self::get($app);
            return $array;
        } catch (\FalseEx $ex) {
            return [];
        }
    }

    /**
     * 获取菜单文件
     *
     * @param [type] $apps
     * @return array
     */
    public static function get($apps)
    {
        $menus = [];
        $pos = $apps['menu'];
        $app = $apps['app'];
        $pattern = 'menu/admincp*';
        // var_dump($apps['apptype']);
        if ($apps['apptype'] == Apps::CONTENT_TYPE) {
            $menus = Etc::many($app, $pattern);
            if (empty($menus)) {
                $app = 'content';
                $pattern = sprintf('menu/admincp.%s||menu/admincp.%s.*', $apps['app'], $apps['app']);
            }
            // var_dump($apps['app'],$pattern);
        }
        if ($apps['app'] == 'content') {
            return [];
        }
        if (empty($pos)) {
            return [];
        }
        empty($menus) && $menus = Etc::many($app, $pattern);
        if ($menus) {
            empty($pos) && $pos = 'default';
            self::pos($menus, $pos);
            self::merge($menus);
            self::vars($menus, $apps);
            $sort = $apps['id'] * self::SORT_WEIGHT;
            self::itemUnique($menus);
            self::addAccess($menus, $sort);
        }
        return (array)$menus;
    }

    public static function itemUnique(&$arrays)
    {
        if (!is_array($arrays)) {
            return;
        }
        foreach ($arrays as $key => &$value) {
            if (is_array($value)) {
                if (in_array($key, ['id', 'access', 'icon'], true)) {
                    $value = current($value);
                } elseif ($key === 'sort') {
                    $value = max($value);
                } else {
                    self::itemUnique($value);
                }
            }
        }
    }

    public static function itemSort(&$variable)
    {
        sortKey($variable);
        foreach ($variable as &$value) {
            if ($value['children']) {
                self::itemSort($value['children']);
            }
        }
    }

    public static function addAccess(&$vars, $sort = 0, $parent = null, $level = 0)
    {
        if ($vars) {
            foreach ($vars as $k => &$v) {
                ++$sort;
                isset($v['sort']) or $v['sort'] = $sort;
                //权限
                $v['access'] = $v['id'] ? $v['id'] : $v['href'];
                if ($v['caption'] == '-') {
                    $v['access'] = $parent . '-' . $level . '-' . $v['sort'];
                    ++$level;
                }
                if ($v['children']) {
                    self::addAccess($v['children'], $sort, $v['id'], $level);
                }
            }
        }
    }

    public static function pos(&$menu, $pos)
    {
        $menu[0] = array_values($menu[0]);
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

    public static function children_callback(&$array)
    {
        $array = array_column($array, null, 'id');
        foreach ($array as $key => &$value) {
            if(is_array($value)){
                if (is_string($value['children']) && strpos($value['children'], '::') != false) {
                    $children = iPHP::callback($value['children']);
                    $value['children'] = is_array($children) ? $children : [];
                }
                if ($value['children']) {
                    self::children_callback($value['children']);
                }
            }
        }
    }

    public static function merge(&$array)
    {
        $result = array_shift($array);
        $result && self::children_callback($result);
        if ($array) {
            foreach ($array as $key => $value) {
                self::children_callback($value);
                $result = array_merge_recursive($result, $value);
            }
        }
        $array = $result;
    }

    public static function vars(&$menu, $a)
    {
        $json = json_encode($menu);
        $json = AppsHelper::replace($json, $a);
        $menu = json_decode($json, true);
    }

    public static function sortData(&$menuArray)
    {
        foreach ($menuArray as $key => &$value) {
            if ($value['children']) {
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
