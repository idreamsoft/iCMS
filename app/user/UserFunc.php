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
    public static function social($vars = null)
    {
        $config = Config::get(self::$app);
        $social = $config['social']?:[];
        foreach ($social as $key => &$value) {
            if(!$value['enable']){
                unset($social[$key]);
                continue;
            }
            $value['key'] = $key;
            $value['url'] = Route::routing('user/login/'.strtolower($key));
            $value['icon'] = $value['icon']?:'fa fa-fw fa-'.$key;
            $value['svg']  = 'iCMS://svg-icon/'.$key.'.svg';
        }
        
        return array_values($social);
    }
    public static function cookie($vars = null)
    {
        return User::$data ?: [];
    }

    public static function data($vars = null)
    {
        if ($vars['cookie']) {
            return User::$data;
        }
        $vars['id'] or self::msg(['func_param', ['iCMS:user:data', 'id']]);
        $id = $vars['id'];
        $id == '@me' && $id = User::$id ?: 0;
        $user = User::get($id);
        return $user;
    }

    public static function lists($vars = null)
    {
        $whereNot = [];
        $resource = [];
        $model = UserModel::field('id');
        $status = isset($vars['status']) ? (int) $vars['status'] : 1;
        $where = [['status', $status]];

        
        if(isset($vars['userid'])){
            $uid = $vars['userid'];
            $uid == '@me' && $uid = User::$id;
            $where[] = ['id', $uid];
        }
        isset($vars['role_id']) && $where[] = ['role_id', $vars['role_id']];
        isset($vars['type']) && $where[] = ['type', $vars['type']];
        self::inited($vars, $model, $where, $whereNot);

        self::props();
        self::keywords();
        self::orderby([
            'id'    => 'id',
            'hot'   => 'hits',
            'yday'  => 'hits_yday',
            'week'  => 'hits_week',
            'month' => 'hits_month'
        ], 'id');
        self::where();
        return self::getResource(__METHOD__);
    }

    public static function resource($idsArray = null)
    {
        $vars = self::$vars;
        $resource = UserModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
        if ($vars['data']) {
            $idArray = array_column($resource, 'id');
            $idArray && $user_data = (array) UserData::gets($idArray);
        }
        if ($resource) {
            $userInfo = User::column($resource, 'id', 'nickname');
            foreach ($resource as $key => $value) {
                unset($value['password']);
                $value['url'] = User::route('url', $value['id']);
                $value['urls'] = User::route('urls', $value['id']);
                $value += $userInfo[$value['id']];
                if ($vars['facesize']) {
                    $value['avatar'] = User::route('avatar', $value['id'], $vars['facesize']);
                }
                // $value['gender'] = $value['gender'] ? 'male' : 'female';
                if ($vars['data'] && $user_data) {
                    $value['data'] = (array)$user_data[$value['id']];
                }
                $resource[$key] = $value;
            }
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
    public static function free($vars = null)
    {
        $vars['type'] = 'free';
        return UserOrderFunc::free($vars);
        // return UserUsageFunc::get($vars);
    }
}
