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
    const TYPE_WEB = 0;
    const TYPE_WEIXIN = 1;
    const TYPE_QQ = 2;
    const TYPE_WEIBO = 3;
    const TYPE_TAOBAO = 4;
    const TYPE_WXA = 5;
    const TYPE_QRCODE = 6;
    const TYPE_GOOGLE = 11;
    const TYPE_GITHUB = 12;
    public static $typeMap = [
        'web' => self::TYPE_WEB,
        'weixin' => self::TYPE_WEIXIN,
        'wx' => self::TYPE_WEIXIN,//兼容
        'qq' => self::TYPE_QQ,
        'weibo' => self::TYPE_WEIBO,
        'wb' => self::TYPE_WEIBO,//兼容
        'taobao' => self::TYPE_TAOBAO,
        'tb' => self::TYPE_TAOBAO,
        'wxa' => self::TYPE_WXA,
        'qrcode' => self::TYPE_QRCODE,
        'qr' => self::TYPE_QRCODE,
        'google' => self::TYPE_GOOGLE,
        'github' => self::TYPE_GITHUB,
    ];

    public static function platform(&$pf)
    {
        if (!is_numeric($pf) && isset(UserOpenid::$typeMap[$pf])) {
            $pf = UserOpenid::$typeMap[$pf];
        }
        return $pf;
    }
    public static function save($userid, $openid = '', $platform = 0, $appid = '')
    {
        self::platform($platform);
        $data = compact('userid', 'openid', 'platform', 'appid');
        $data['id'] = UserOpenidModel::create($data, true);
        return $data;
    }
    public static function update($id, $data = '', $field = 'openid')
    {
        is_array($data) or $data = [$field => $data];
        return UserOpenidModel::update($data, compact('id'));
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
