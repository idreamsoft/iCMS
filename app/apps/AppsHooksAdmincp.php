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

/**
 * 钩子管理
 *
 * @role 1
 */
class AppsHooksAdmincp extends AdmincpBase
{
    /**
     * 钩子管理
     * @return void
     */
    public function do_index()
    {
        $config = AppsHooks::get();
        include self::view();
    }

    /**
     * 保存应用字段钩子
     */
    public function done_save(&$data = [])
    {
        //删除时提交
        if ($remove = Request::post('remove')) {
            $config = (array)Request::post('config');
            AppsHooks::set($config);
            return true;
        }
        //新增
        if ($data = (array)Request::post('hooks')) {
            $hooks = [];
            $config = (array)Config::data(iCMS_APP_APPS, 'hooks');
            if ($data['method'] && $data['app'] && $data['field']) {
                $hooks['fields'][$data['app']][$data['field']][] = $data['method'];
                $config = array_merge_recursive($config, $hooks);
                $methods = &$config['fields'][$data['app']][$data['field']];
                $methods = array_unique($methods);
                ksort($methods);
                AppsHooks::set($config);
                return 'url:reload:1000';
            }
        }
    }

    /**
     * 获取带有钩子执行方法的应用
     *
     * 获取从*App中获取带有hooked钩子执行方法的应用
     *
     * @return void
     */
    public function done_apps()
    {
        $keyField = Request::post('keyField') ?: 'id';

        foreach (Apps::getTableArray() as $key => $value) {
            list($path, $obj_name) = Apps::getPathArr($value['app'], 'app');
            if (is_file($path) && method_exists($obj_name, 'hooked')) {
                $name = $value['name'];
                $list[] = iJson::listItem(['app' => $value[$keyField]], $name);
            }
        }

        return iJson::listData($list);
    }

    /**
     * 获取所有*Hooks.php
     *
     * @return void
     */
    public function done_hooks()
    {
        $prefix = Request::get('sdo') ?: 'field_hook';
        $methods = AppsHooks::find('*Hook', function ($class, $method) use ($prefix) {
            return strpos($method, $prefix) === 0;
        });
        $list = [];
        if ($methods) {
            foreach ($methods as $key => $call) {
                if (!$call) continue;
                
                $doc = Helper::getDocComment($call[0], $call[1]);
                $hook = implode('::', $call);
                $title = $doc['title'] ?: $hook;
                $list[] = iJson::listItem(['hook'=>$hook], $title);
            }
        }
        return iJson::listData($list);
    }

    /**
     * 获取应用配置表中table配置的表字段
     *
     * @return void
     */
    public function done_field()
    {
        $list = [];
        $appid = Request::post('appid');
        $where = Request::post('where');
        if(isset($where['appid'])){
            $appid = $where['appid'];
        }
        $appid<1 && self::alert("请先选择应用");
        // $appid = isset($where['appid']) ? $where['appid'] : 0;
        $tables = Apps::getTable($appid, false);
        if ($tables) {
            foreach ((array) $tables as $key => $table) {
                $name = $table['name'];
                if (DB::hasTable($name)) {
                    // $option[] = '<optgroup label="' . $table['label'] . '表">';
                    $fullFields = DB::table($name)->fullFields();
                    // $fullFields  = AppsTable::fullFields($name);
                    foreach ((array) $fullFields as $field => $value) {
                        $text = ($value['comment'] ? $value['comment'] . ' (' . $field . ')' : $field);
                        // $option[] = sprintf(
                        //     '<option value="%s">%s</option>',
                        //     $field,
                        //     $text
                        // );
                        $text = $name . '.' . $field;
                        $list[] = iJson::listItem($field, $text, $text. ' --- ' . $value['comment']);
                        // $list[$table['label'].'表'][] = iJson::listItem(['field'=>$field],$text);
                    }
                    // $option[] = '</optgroup>';
                }
            }
        }
        $param = Request::param();
        $pageSize = $param['pageSize'] ? (int)$param['pageSize'] : 10;
        $offset = ($param['pageNumber'] - 1) * $pageSize;
        $result = array_slice($list, $offset, $pageSize);
        $totalRow = count($list);
        $totalPage = ceil($totalRow / $pageSize);
        return iJson::listData($result, $pageSize, $param['pageNumber'], $totalRow, $totalPage);
    }
}
