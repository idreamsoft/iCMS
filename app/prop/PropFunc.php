<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class PropFunc extends AppsFuncCommon
{
    public static function group($vars = null)
    {
        empty($vars['group']) && $vars['group'] = 'field';
        $separator = $vars['separator'] ?: ',';
        $whereAs = $vars['whereAs'] ?: 'WHERE';
        $resource = self::lists($vars);
        $groups = [];
        $where = [];
        $appid = $vars['appid'];
        $apps = Apps::getData($appid);
        $fieldAll = Former::fields($apps['fields']);
        !isset($vars['judge']) && $vars['judge'] = Request::get();
        $judge = $vars['judge'];

        foreach ($resource as $key => $item) {
            $k = $item[$vars['group']];
            $field = $item['field'];
            $fields = $fieldAll[$field];
            if (!isset($groups[$k])) {
                $groups[$k] = [];
                $groups[$k]['key'] = $field . '-null';
                $groups[$k]['field'] = $field;
                $groups[$k]['name'] = $field;
                $groups[$k]['title'] = $item['title'] ? $item['title'] : Lang::get($item['app'] . ':' . $field);
                $groups[$k]['url'] = Route::make([$field=>null]);
                $groups[$k]['active'] = true;
                $groups[$k]['type'] = 'radio';
                if (in_array($fields['type'], ['checkbox', 'multiple', 'checkbox_prop'])) {
                    $groups[$k]['type'] = 'checkbox';
                }
            }
            self::item($item, $where, $fields, $judge, $vars, $separator);
            $groups[$k]['items'][] = $item;
        }

        foreach ($where as $k => $value) {
            $groups[$k]['active'] = $value ? false : true;
        }

        View::append($whereAs, $where, true);
        return $groups ? array_values($groups) : [];
    }

    protected static function item(&$item, &$where, $fields, $judge, $vars, $separator = ',')
    {
        $field = $item['field'];
        $item['key'] = $field . '-' . substr($item['hash'], 8, 16);

        $selected = [];
        if (isset($judge[$field])) {
            $selected = $judge[$field];
            $selected = explode($separator, $selected);
        }

        $active = $selected && in_array($item['val'], $selected) ? true : false;
        $item['active'] = $active;
        if (in_array($fields['type'], ['checkbox', 'multiple', 'checkbox_prop'])) {
            $urlVals = $selected;
            $urlVals[] = $item['val'];
            $urlVals = array_unique($urlVals);
            if ($item['active']) {
                $where[$field][] = [$field, 'FIND_IN_SET', $item['val']];
                $k = array_search($item['val'], $urlVals);
                unset($urlVals[$k]);
            }
            $item['ui_type'] = 'checkbox';
        } else {
            $item['ui_type'] = 'radio';
            $item['active'] && $where[$field] = $item['val'];
            $urlVals = [$item['val']];
        }
        $item['url'] = Route::make([$field=> implode($separator, $urlVals)], $vars['url']);
    }

    public static function lists($vars = null)
    {
        $whereNot = [];
        $resource = [];
        $model = PropModel::field('id');

        isset($vars['rootid']) && $where['rootid'] = $vars['rootid'];
        isset($vars['field']) && $where['field'] = $vars['field'];
        isset($vars['appid']) && $where['appid'] = $vars['appid'];
        isset($vars['sapp']) && $where['app'] = $vars['sapp'];
        self::inited($vars, $model, $where, $whereNot);

        self::nodes('cid');
        self::orderby([
            'new'  => 'id',
            'sort' => 'sortnum'
        ], 'id');
        self::where();
        return self::getResource(__METHOD__);
    }

    public static function resource($idsArray = null)
    {
        $vars = self::$vars;
        $resource = PropModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
        PropApp::items($vars, $resource);
        return $resource;
    }

    public static function data($vars)
    {
        $field = $vars['field'];
        $sapp = $vars['sapp'];
        $resource = propApp::value($field, $sapp, $vars['sort']);
        $offset = $vars['start'] ? $vars['start'] : 0;
        if ($resource) {
            $vars['row'] && $resource = array_slice($resource, $offset, $vars['row']);
            PropApp::items($vars, $resource);
            sort($resource);
        }
        if ($vars['col']) {
            $resource = array_column($resource, null, $vars['col']);
        }
        return $resource;
    }
}
