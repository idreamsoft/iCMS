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

class UserModel extends Model
{
	public static $statusMap = array(
		'0' => '禁用',
		'1' => '正常',
		'2' => '黑名单',
		'3' => '登录封禁',
		'4' => '邮箱验证',
	);

    protected $casts = [
        // 'pid' => 'array',
        'setting' => 'array'
    ];
    /**
     * 根据openid获取用户
     */
    public static function getByOpenid($openid)
    {
        $open = UserOpenidModel::where(['openid' => $openid])->first();
        if ($open) {
            return User::get($open['userid']);
        }
		return [];
    }

    /**
     * 根据手机号获取用户
     */
    public static function getByPhone($phone, $userType = null)
    {
        $where = ['phone' => $phone];
        if ($userType !== null) {
            $where['type'] = $userType;
        }
        return self::where($where)->first();
    }
}
