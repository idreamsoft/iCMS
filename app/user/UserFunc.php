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
class UserFunc extends AppsFuncCommon
{
    public static function cookie($vars = null)
    {
        return User::$data ?: [];
    }
    public static function config($vars = null)
    {
        $config = Config::get('user');
        return $config;
    }
    public static function data($vars = null)
    {
        if ($vars['cookie']) {
            return User::$data;
        }

        $vars['id'] or Script::warning('iCMS&#x3a;user&#x3a;data 标签出错! 缺少"id"属性或"id"值为空.');

        $uid = $vars['id'];
        $uid == '@me' && $uid = User::$id ?: 0;
        $user = User::get($uid);
        if (isset($user['uid'])) {
            $vars['data'] && $user['data'] = (array)UserData::gets($uid);
        } else {
            if ($vars['data']) {
                $userdata = UserData::gets($uid);
                if ($userdata) foreach ($user as $key => $value) {
                    $user[$key] = (array)$value;
                    $user[$key]['data'] = (array)$userdata[$key];
                }
            }
        }
        return $user;
    }

    public static function lists($vars = null)
    {
        $whereNot  = array();
        $resource  = array();
        $model     = UserModel::field('id');
        $status    = isset($vars['status']) ? (int) $vars['status'] : 1;
        $where     = [['status', $status]];

        isset($vars['userid']) && $where[] = ['uid', $vars['userid']];
        isset($vars['role_id']) && $where[] = ['role_id', $vars['role_id']];
        isset($vars['type']) && $where[] = ['type', $vars['type']];
        self::init($vars, $model, $where, $whereNot);
        self::setApp(User::APPID, User::APP);

        self::props();
        self::keywords();
        self::orderby([
            'id'   => 'uid',
            'hot' => 'hits',
            'yday'  => 'hits_yday',
            'week'  => 'hits_week',
            'month' => 'hits_month'
        ], 'uid');
        self::where();
        return self::getResource(__METHOD__, [__CLASS__, 'resource']);
    }
    public static function resource($vars, $idsArray = null)
    {
        $vars['ids'] && $idsArray = $vars['ids'];
        $resource = UserModel::field('*')->where($idsArray)->orderBy('uid', $idsArray)->select();
        if ($vars['data']) {
            $idArray = array_column($resource, 'uid');
            $idArray && $user_data = (array) UserData::gets($idArray);
        }
        if ($resource) foreach ($resource as $key => $value) {
            unset($value['password']);
            $value['url']    = User::route($value['uid'], "url");
            $value['urls']   = User::route($value['uid'], "urls");
            $value += User::info($value['uid'], $value['nickname'], $vars['size']);
            $value['gender'] = $value['gender'] ? 'male' : 'female';
            if ($vars['data'] && $user_data) {
                $value['data']  = (array)$user_data[$value['uid']];
            }
            $resource[$key]  = $value;
        }
        return $resource;
    }
    public static function node($vars = null)
    {
        return UserNodeFunc::lists($vars);
    }
    public static function follow($vars = null)
    {
        return UserFollowFunc::lists($vars);
    }
    public static function order($vars = null)
    {
        return UserOrderFunc::lists($vars);
    }
    public static function stat($vars = null)
    {
    }
}
