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

class UserHook
{
    /**
     * Application::run begin回调
     *
     * @return void
     */
    public static function run_begin()
    {
        if (User::status()) {
            Files::$userid = User::$id;
            UserOrder::checkAndUpdateUserRole();
            View::assign('ME', User::$data);
            View::assign('isLogin', true);
        } else {
            View::assign('isLogin', false);
        }
    }

    /**
     * Application::run after回调
     * 需要更新系统设置缓存才能生效
     *
     * @return void
     */
    public static function run_after()
    {
        self::checkLogin();
    }

    /**
     * 判断请求是否需要登陆
     *
     * @return void
     */
    public static function checkLogin()
    {
        if (property_exists(Application::$CLASS, 'LOGIN')) {
            $login = Application::$CLASS::$LOGIN;
            if (isset($login)) {
                if (is_array($login)) {
                    $flag = in_array(Application::$DO, $login);
                } else {
                    $flag = ($login === true);
                }
                $flag && User::isLogin();
            }
        }
    }
}
