<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class AppsFunc extends AppsFuncCommon
{
    public static function lists($vars)
    {
        $whereNot  = array();
        $resource  = array();
        $model     = AppsModel::field('id');
        $status    = isset($vars['status']) ? $vars['status'] : 1;
        $where     = [['status', $status],['apptype', '>','0']];

        isset($vars['type']) && $where[] = ['type', $vars['type']];
        self::init($vars, $model, $where, $whereNot);
        self::orderby([], 'id');
        self::where();

        return self::getResource(__METHOD__, [__CLASS__, 'resource']);
    }
    public static function resource($vars, $idsArray = null)
    {
        $vars['ids'] && $idsArray = $vars['ids'];

        $resource = AppsModel::field('*')
            ->where($idsArray)
            ->orderBy('id', $idsArray)
            ->select();

        foreach ($resource as $key => &$value) {
        }
        return $resource;
    }
}
