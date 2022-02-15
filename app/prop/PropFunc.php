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
    public static function lists($vars = null)
    {
        $whereNot  = array();
        $resource  = array();
        $model     = PropModel::field('id');

        isset($vars['rootid']) && $where['rootid'] = $vars['rootid'];
        isset($vars['field']) && $where['field'] = $vars['field'];
        isset($vars['appid']) && $where['appid'] = $vars['appid'];
        isset($vars['sapp']) && $where['sapp'] = $vars['sapp'];
        self::init($vars, $model, $where, $whereNot);

        self::nodes('cid');
        self::orderby([
            'new' => 'id',
            'sort' => 'sortnum'
        ], 'id');
        self::where();
        return self::getResource(__METHOD__, [__CLASS__, 'resource']);
    }
    public static function resource($vars, $idsArray = null)
    {
        $vars['ids'] && $idsArray = $vars['ids'];
        $resource = PropModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
        $resource = PropApp::items($vars, $resource);
        return $resource;
    }
    public static function data($vars)
    {
        $field    = $vars['field'];
        $sapp     = $vars['sapp'];
        $variable = propApp::value($field, $sapp, $vars['sort']);
        $offset = $vars['start'] ? $vars['start'] : 0;
        if ($variable) {
            $vars['row'] && $variable = array_slice($variable, $offset, $vars['row']);
            $variable = PropApp::items($vars, $variable);
            sort($variable);
        }
        return $variable;
    }
}
