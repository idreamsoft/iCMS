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

class UserOrderFunc extends AppsFuncCommon
{
    public static function lists($vars = null)
    {
        $whereNot  = array();
        $resource  = array();
        $model     = UserOrderModel::field('id');

        $where[] = ['userid', $vars['userid']];
        self::init($vars, $model, $where, $whereNot);
        self::setApp(User::APPID, User::APP);

        self::orderby([], 'id');
        self::where();
        return self::getResource(__METHOD__, [__CLASS__, 'resource']);
    }
    public static function resource($vars, $idsArray = null)
    {
        $vars['ids'] && $idsArray = $vars['ids'];
        $resource = UserOrderModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
        return $resource;
    }
}
