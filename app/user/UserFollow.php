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

class UserFollow extends AppsFuncCommon
{
    public static function is($userid = 0, $fuid = 0)
    {
        $fuid = UserFollowModel::field('fuid')->where(compact('userid', 'fuid'))->value();
        return $fuid ? $fuid : false;
    }
    public static function gets($userid = 0, $fuid = 0)
    {
        if ($userid === 'all') { //all fans
            $model = UserFollowModel::field('userid,name')->where('fuid', $fuid);
        } elseif ($fuid === 'all') { // all follow
            $model = UserFollowModel::field('fuid AS userid', 'fname AS name')->where('userid', $userid);
        }
        $data = $model->pluck('name', 'userid');
        return $data;
    }
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
        self::init($vars, $model, $where, $whereNot);
        self::setApp(User::APPID, User::APP);

        self::orderby([], 'id');
        self::where();
        return self::getResource(__METHOD__, [__CLASS__, 'resource']);
    }
    public static function resource($vars, $idsArray = null)
    {
        $vars['ids'] && $idsArray = $vars['ids'];

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
                $value['avatar'] = User::route($value['userid'], 'avatar');
                $value['url']    = User::route($value['userid'], 'url');
            } else {
                $value['avatar'] = User::route($value['fuid'], 'avatar');
                $value['url']    = User::route($value['fuid'], 'url');
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
