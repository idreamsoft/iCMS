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

class Member
{
    const SUPER_ID = 1;
    public static $statusMap = array(
        '0' => '禁用',
        '1' => '正常',
        '2' => '黑名单',
        '3' => '登录封禁',
    );
    public static $id           = 0; //管理员ID
    public static $user_id      = 0; //绑定的前台用户ID
    public static $nickname     = NULL;
    public static $DATA         = array();
    public static $ROLE         = array();
    public static $ACCESS       = array();
    public static $callback     = array();
    public static $GATEWAY      = false;
    public static $AUTH         = 'ADMIN_AUTH';
    public static $LOGIN_PAGE   = 'login.php';
    private static $LOGIN_COUNT = 0;

    //登录验证
    public static function auth($fn = null)
    {
        try {
            self::login();
        } catch (\Exception $ex) {
            self::logout();
            return $fn ?
                call_user_func($fn) :
                self::display();
        }
    }
    public static function check($account, $password)
    {
        if (empty($account) && empty($password)) {
            return false;
        }

        $member = MemberModel::where(array(
            'account' => $account,
            'password' => $password,
            'status' => 1
        ))->get();

        if (empty($member)) {
            return false;
        }

        self::$ROLE = Role::get($member['role_id']);
        self::$DATA = $member;

        unset($member['password']);
        self::$id       = $member['id'];
        self::$user_id  = $member['user_id'] ?: 1;
        self::$nickname = $member['nickname'] ? $member['nickname'] : $member['account'];
        self::$ACCESS['app']  = self::mergeAccess($member['access']['app'], self::$ROLE['access']['app']);
        self::$ACCESS['node'] = self::mergeAccess($member['access']['node'], self::$ROLE['access']['node']);
        return true;
    }
    public static function post($flag = false)
    {
        $a = Request::post('iAccount');
        $p = Request::post('iPassWord');
        return $flag ? ($a && $p) : array($a, $p);
    }
    //登录验证
    public static function login()
    {
        //        self::$LOGIN_COUNT = (int)auth_decode(Cookie::get('iCMS_LOGIN_COUNT'));
        //        if(self::$LOGIN_COUNT>iCMS_LOGIN_COUNT) exit();
        list($account, $password) = self::post();
        $ip  = Request::ip();
        $sep = iPHP_AUTH_IP ? '#=iCMS[' . $ip . ']=#' : '#=iCMS=#';
        if (empty($account) && empty($password)) {
            $auth = Cookie::get(self::$AUTH);
            list($account, $password) = explode($sep, auth_decode($auth));
            $result = self::check($account, $password);
        } else {
            $password = Member::password($password);
            $result = self::check($account, $password);
            if ($result) {
                MemberModel::update(array(
                    'lastloginip' => $ip,
                    'lastlogintime' => time(),
                    'logintimes' => array('+', 1),
                ), self::$id);
                $life = Config::get('member.life') ?: 86400;
                Cookie::set(self::$AUTH, auth_encode($account . $sep . $password), $life);
            }
        }
        if (Request::post('action') == 'login') {
            $result ?
                iJson::success() :
                iJson::error('登录失败！账号或密码错误。');
        }
        $result or iPHP::throwError('MEMBER::NOLOGIN', -9999);
    }

    //注销
    public static function logout()
    {
        Cookie::set(self::$AUTH, '', -31536000);
    }
    public static function display()
    {
        $message = '请先登录';
        Request::isAjax() && iJson::error($message, -9999);
        if (Request::param('frame')) {
            Script::alert($message, null, 10);
        }
        include AdmincpView::display("login", 'member');
        exit;
    }
    private static function mergeAccess($p1, $p2)
    {
        $array = array_merge((array) $p1, (array) $p2);
        return array_unique($array);
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
    public static function isSuperRole()
    {
        return Role::isSuper(self::$DATA['role_id']);
    }
    public static function password($password)
    {
        // return md5($password);
        return Security::secureToken($password);
    }
}
