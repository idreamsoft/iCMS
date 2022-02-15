<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class TagFunc  extends AppsFuncCommon implements AppsFuncBase
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
        self::init($vars, $model, $where, $whereNot);
        self::setApp(Tag::APPID, Tag::APP);

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
        return self::getResource(__METHOD__, [__CLASS__, 'resource']);
    }
    public static function resource($vars, $idsArray = null)
    {
        $vars['ids'] && $idsArray = $vars['ids'];
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
                Script::warning('iCMS&#x3a;tag&#x3a;array 标签出错! 缺少参数"id"或"name".');
            }
        }
        if ($resource) {
            if ($vars['meta']) {
                $idArray = array_column($resource, 'id');
                $idArray && $meta_data = (array)AppsMeta::data('tag', $idArray);
                unset($idArray);
            }
            foreach ($resource as $key => $value) {
                try {
                    if ($vars['meta'] && $meta_data) {
                        $value += (array)$meta_data[$value['id']];
                    }
                    $resource[$key] = TagApp::values($value, $vars);
                } catch (\FalseEx $fex) {
                    continue;
                }
            }
        }
        return $resource;
    }
}
