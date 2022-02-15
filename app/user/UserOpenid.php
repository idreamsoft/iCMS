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

class UserOpenid
{
    public static $platformMap = [
        1 => 'wx',
        2 => 'qq',
        3 => 'wb',
        4 => 'tb',
        5 => 'wxa',
    ];

    public static function platform(&$platform)
    {
        if (!is_numeric($platform)) {
            // $platformMap = array_flip(self::$platformMap);
            $platform = array_search($platform, self::$platformMap);
        }
    }
    public static function save($userid, $openid = '', $platform = 0, $appid = '')
    {
        self::platform($platform);
        $data = compact('userid', 'openid', 'platform', 'appid');
        $data['id'] = UserOpenidModel::create($data, true);
        return $data;
    }
    public static function update($id, $openid = '')
    {
        return UserOpenidModel::update(compact('openid'), $id);
    }
    public static function get($userid = 0, $platform = 0, $appid = null, $field = 'openid')
    {
        self::platform($platform);
        $where  = compact('userid', 'platform');
        $appid && $where['appid'] = $appid;
        return UserOpenidModel::field($field)->where($where)->value();
    }

    public static function userid($openid = 0, $platform = 0, $appid = null)
    {
        self::platform($platform);
        $where  = compact('openid', 'platform');
        $appid && $where['appid'] = $appid;
        return UserOpenidModel::field('userid')->where($where)->value();
    }
}
