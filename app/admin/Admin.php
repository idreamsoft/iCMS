<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
// use iPHP\core\Security;

class Admin
{
    public const SUPER_ID = 1;
    public const SESS_LOGINED = 'admin_logined';//移除
    public const SESS_AUTH = 'admin_auth';

    public const LOGIN_CODE = -9999;

    public static $id = 0; //管理员ID
    public static $user_id = 0; //绑定的前台用户ID
    public static $name = null;
    public static $nickname = null;
    public static $DATA = [];
    public static $ROLE = [];
    public static $ACCESS = [];

    //登录验证
    public static function isLogin()
    {
        try {
            self::status();
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public static function token()
    {
        $AUTHORIZATION = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        if ($AUTHORIZATION) {
            return str_replace('Bearer ', '', $AUTHORIZATION);
        }
        return false;
    }

    public static function status()
    {
        $bearer = self::token();
        empty($bearer) && $bearer = Session::get(self::SESS_AUTH);
        if (empty($bearer)) {
            throw new sException('auth:login:error_0');
        }
        $auth = Crypt::decrypt($bearer);
        if ($auth) {
            $life = self::life();
            $ip = Request::ip();
            $ua = md5($_SERVER['HTTP_USER_AGENT']);
            list($account, $password, $time, $_ip, $_ua) = $auth;

            if (iPHP_ADMINCP_IP && $ip != $_ip) {
                throw new sException('auth:login:error_2');
            }
            if (iPHP_ADMINCP_UA && $ua != $_ua) {
                throw new sException('auth:login:error_3');
            }

            if ($time + $life < $time) {
                throw new sException('auth:login:error_4');
            }
        }

        if (empty($account) || empty($password)) {
            throw new sException('auth:login:error_5');
        }

        self::check($account, $password);
        return self::data();
    }

    public static function data()
    {
        return [
            'id'        => Admin::$id,
            'userid'    => self::$DATA['user_id'] ?: 0, //管理员绑定的用户ID
            'username'  => self::$DATA['account'] ?: '',
            'nickname'  => self::$DATA['nickname'] ?: '',
            'account'   => self::$DATA['account'] ?: '',
            'role_id'   => self::$ROLE['id'],
            'role_name' => self::$ROLE['name'],
        ];
    }

    public static function auth()
    {
        try {
            self::status();
        } catch (\Exception $ex) {
            self::display('auth:login:required');
            // self::logout();
            // Admincp::error(self::LOGIN_MSG, 'auth');
        }
    }

    public static function check($account, $password)
    {
        if (empty($account) && empty($password)) {
            throw new sException('auth:login:empty');
        }

        $admin = AdminModel::where([
            'account'  => $account,
            'password' => $password,
            'status'   => 1
        ])->get();

        if (empty($admin)) {
            throw new sException('auth:login:failure');
        }
        self::init($admin);
    }

    public static function init($admin)
    {
        self::$ROLE = Role::get($admin['role_id']);
        unset($admin['password']);

        self::$DATA = $admin;

        self::$DATA['isSuperAdmin'] = self::isSuperAdmin($admin['id']);
        self::$DATA['isSuperRole'] = self::isSuperRole($admin['role_id']);

        self::$id = $admin['id'];
        self::$user_id = $admin['user_id'] ?: 1;
        self::$name = self::$nickname = $admin['nickname'] ? $admin['nickname'] : $admin['account'];
        self::$ACCESS['app'] = $admin['access']['app'] ?: self::$ROLE['access']['app'] ?: [];
        self::$ACCESS['node'] = $admin['access']['node'] ?: self::$ROLE['access']['node'] ?: [];
    }

    //登录验证
    public static function login($account, $password)
    {
        $ip = Request::ip();
        $password = self::password($password);
        self::check($account, $password); //抛出异常

        $time = time();
        AdminModel::update([
            'lastloginip'   => $ip,
            'lastlogintime' => time(),
            'logintimes'    => ['+', 1],
        ], self::$id);
        $life = self::life();
        $auth = Crypt::encrypt([$account, $password, $time, $ip, md5($_SERVER['HTTP_USER_AGENT'])], $life);
        Session::set(self::SESS_AUTH, $auth);
        return [$auth, $life];
    }

    //注销
    public static function life()
    {
        return Config::get('admin.life') ?: 86400;
    }

    public static function logout()
    {
        Session::destroy();
    }

    public static function display($msg = '')
    {
        if (Request::isAjax()) {
            iJson::fail('auth:login:required', 'reload', self::LOGIN_CODE, 'auth');
        } else {
            $CSRF_TOKEN = Security::csrf_token();
            include AdmincpView::display('login', 'admin');
        }
        exit;
    }

    /**
     * 是否超级管理员，即管理员id为指定的SUPER_ID
     */
    public static function isSuperAdmin($id = null)
    {
        is_null($id) && $id = self::$id;
        return (int)$id === (int)self::SUPER_ID;
    }

    /**
     * 是否为超级管理员角色
     */
    public static function isSuperRole($id = null)
    {
        is_null($id) && $id = self::$DATA['role_id'];
        return Role::isSuper($id);
    }

    /**
     * 检测角色
     *
     * @param array $ids
     * @return bool
     */
    public static function checkRole($ids = [])
    {
        if (self::isSuperRole()) {
            return true;
        }
        if ($ids) {
            $allowedIds = is_array($ids) ? $ids : [$ids];
            $allowedIds = array_map('strtolower', $allowedIds);
            if (in_array('all', $allowedIds)) {
                return true;
            }
            if (!in_array(self::$DATA['role_id'], $allowedIds)) {
                return false;
            }
            return true;
        }
    }

    public static function password($password)
    {
        // return md5($password);
        return Security::secureToken($password);
    }
}
