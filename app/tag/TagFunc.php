<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class TagFunc extends AppsFuncCommon implements AppsFuncInterface
{

    public static function value($vars)
    {
    }

    public static function lists($vars)
    {
        $whereNot  = array();
        $resource  = array();
        $model     = TagModel::field('id');
        $status    = isset($vars['status']) ? (int) $vars['status'] : 1;
        $where     = [['status', $status]];

        isset($vars['rootid']) && $where[]    = ['rootid', $vars['rootid']];
        isset($vars['rootid!']) && $whereNot[] = ['rootid', '<>', $vars['rootid!']];
        isset($vars['field']) && $where[]    = ['field', $vars['field']];
        isset($vars['tkey']) && $where[]    = ['tkey', $vars['tkey']];
        isset($vars['appid']) && $where[]    = ['appid', 'FIND_IN_SET', $vars['appid']];
        self::inited($vars, $model, $where, $whereNot);

        self::nodes('tcid');
        self::nodes('cid');
        self::props();
        self::keywords('tkey,name,seotitle,keywords');
        self::orderby([
            'hot'   => 'count',
            'new' => 'id',
            'sort'  => 'sortnum'
        ]);
        self::where();
        return self::getResource(__METHOD__);
    }
    public static function resource($idsArray = null)
    {
        $vars = self::$vars;
        $resource = TagModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
        $resource = self::many($vars, $resource);
        return $resource;
    }

    public static function many($vars, $resource = null)
    {
        if ($resource === null) {
            if (isset($vars['name'])) {
                $array = array($vars['name'], 'name');
            } else if (isset($vars['id'])) {
                $array = array($vars['id'], 'id');
            }
            if ($array) {
                return TagApp::display($array[0], $array[1], false);
            } else {
                self::msg(['func_param', ['iCMS:tag:array', 'id or name']]);
            }
        }
        if ($resource) {
            $idArray = array_column($resource, 'id');
            if ($vars['meta'] && $idArray) {
                $metaData = [];
                AppsMeta::data('tag', $idArray,$metaData);
            }
            foreach ($resource as $key => $value) {
                try {
                    if ($vars['meta'] && $metaData) {
                        $value += $metaData[$value['id']];
                    }
                    $resource[$key] = TagApp::values($value, $vars);
                } catch (\FalseEx $fex) {
                    $value = [];
                    unset($resource[$key]);
                    continue;
                }
            }
        }
        return $resource;
    }
}
