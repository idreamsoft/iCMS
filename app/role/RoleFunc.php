<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class RoleFunc extends AppsFuncCommon
{

    public static function lists($vars)
    {
        $whereNot  = array();
        $resource  = array();
        $model     = RoleModel::field('id');
        $status    = isset($vars['status']) ? (int) $vars['status'] : 1;
        $where     = [['status', $status]];

        isset($vars['type']) && $where[]    = ['type', $vars['type']];

        self::init($vars, $model, $where, $whereNot);
        self::setApp(Role::APPID, Role::APP);
        self::orderby([
            'new' => 'id',
            'sort'  => 'sortnum'
        ]);
        self::where();
        return self::getResource(__METHOD__, [__CLASS__, 'resource']);
    }
    public static function resource($vars, $idsArray = null)
    {
        $vars['ids'] && $idsArray = $vars['ids'];
        $resource = RoleModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
        return $resource;
    }
}
