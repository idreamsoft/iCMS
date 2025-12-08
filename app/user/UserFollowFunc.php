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

class UserFollowFunc extends AppsFuncCommon
{
    protected static $app = User::APP;
    protected static $appid = User::APPID;

    public static function lists($vars = null)
    {
        $whereNot  = array();
        $resource  = array();
        $model     = UserFollowModel::field('id');
        if ($vars['fuid']) {
            $where = [['fuid', $vars['fuid']]]; //fans
        } else {
            $where = [['userid', $vars['userid']]]; //follow
        }
        self::inited($vars, $model, $where, $whereNot);

        self::orderby([], 'id');
        self::where();
        return self::getResource(__METHOD__);
    }
    public static function resource($idsArray = null)
    {
        $vars = self::$vars;

        $resource = UserFollowModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
        if ($vars['data']) {
            $uidArray1 = array_column($resource, 'userid');
            $uidArray2 = array_column($resource, 'fuid');
            $uidArray = array_merge($uidArray1, $uidArray2);
            if ($uidArray) {
                $uidArray = array_unique($uidArray);
                $user_data = (array) UserData::gets($uidArray);
            }
        }

        $vars['followed'] && $follow_data = self::gets($vars['followed'], 'all');

        if ($resource) foreach ($resource as $key => $value) {
            if ($vars['fuid']) {
                $value['avatar'] = User::route('avatar',$value['userid']);
                $value['url']    = User::route('url',$value['userid']);
            } else {
                $value['avatar'] = User::route('avatar',$value['userid']);
                $value['url']    = User::route('url',$value['userid']);
                $value['userid']    = $value['fuid'];
                $value['name']   = $value['fname'];
            }
            if ($vars['data'] && $user_data) {
                $value['data']  = (array)$user_data[$value['userid']];
            }
            $vars['followed'] && $value['followed'] = $follow_data[$value['userid']] ? 1 : 0;
            $resource[$key] = $value;
        }

        return $resource;
    }
}
