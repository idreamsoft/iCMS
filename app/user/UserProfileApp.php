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

class UserProfileApp extends UserCenter
{
    /**
     * 修改密码
     *
     * @return void
     */
    public function done_modifypwd()
    {
        Captcha::check();

        $post = Request::post();
        trim($post['password']) or AppsBase::alert('user:password:empty:oorigld');
        trim($post['passwd']) or AppsBase::alert('user:password:empty:new');
        trim($post['passwd-confirm']) or AppsBase::alert('user:password:empty:confirm');
        $post['passwd'] == $post['passwd-confirm'] or AppsBase::alert('user:password:unequal');

        $userid = User::$id;
        if (User::value($userid, 'password') != User::password($post['password'])) {
            AppsBase::alert('user:password:error:orig');
        }

        $password = User::password($post['passwd']);
        UserModel::update(compact('password'), $userid);
        User::logout();
        return ['message'=>'Successfully updated', 'url'=>'reload'];
    }

    /**
     * 修改邮箱
     *
     * @return void
     */
    public function done_modifyEmail()
    {
        $post = Request::post();
        if ($post['email'] == $post['email2']) {
            return true;
        }
        //判断手机号 验证码 是否正确验证过
        PluginVerify::verify($post['email'], $post['emailcode'], 'email');
        PluginVerify::verify($post['email2'], $post['emailcode2'], 'email');

        $email = $post['email2'];
        UserModel::update(compact('email'), User::$id);
        return 'url:reload:1000';
    }

    /**
     * 用户中心修改手机号
     *
     * @return void
     */
    public function done_modifyPhone()
    {
        $post = Request::post();
        if ($post['phone'] == $post['phone2']) {
            return true;
        }
        //判断手机号 验证码 是否正确验证过
        PluginVerify::verify($post['phone'], $post['smscode']);
        PluginVerify::verify($post['phone2'], $post['smscode2']);
        $phone = $post['phone2'];
        UserModel::update(compact('phone'), User::$id);
        return 'url:reload:1000';
    }

    /**
     * 用户中心修改手机号 验证手机验证码
     *
     * @return void
     */
    public function done_modifyPhoneStep()
    {
        $post = Request::post();
        PluginVerify::phone($post['phone'], $post['smscode']);
    }

    /**
     * 用户中心修改邮箱 验证邮箱验证码
     *
     * @return void
     */
    public function done_modifyEmailStep()
    {
        $post = Request::post();
        // PluginEmail::$debug = iPHP_DEBUG;
        PluginVerify::email($post['email'], $post['code']);
    }

    /**
     * 修改个人信息
     *
     * @return void
     */
    public function done_save()
    {
        $post = Request::post();
        $user = array_filter_keys(
            $post,
            ['email', 'nickname', 'gender', 'setting', 'data']
        );
        $user['setting'] = array_filter_keys(
            $user['setting'],
            ['message']
        );
        $data = array_filter_keys(
            $post['data'],
            ['slogan', 'weixin', 'weibo', 'city', 'birthday', 'constellation', 'profession', 'personstyle']
        );
        if ($data['city']) {
            $citys = explode('/', $data['city']);
            $data['province'] = $citys[0];
        }
        if ($data['birthday']) {
            list($data['year'], $data['month'], $data['day']) = explode('-', $data['birthday']);
        }
        $data['userid'] = User::$id;
        UserModel::update($user, User::$id);
        UserData::updateOrCreate($data, User::$id);
    }
}
