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

class UserSocialApp
{
    public function __construct()
    {
        $this->config = User::$config['open'];
    }
    public function login()
    {
        $sign  = Request::get('sign');
        $code  = Request::get('code');
        $state = Request::get('state');
        $platform = $this->config[strtoupper($sign)];
        if ($platform) {
            $class_name   = 'oauth_' . strtoupper($sign);
            include_once __DIR__.'/vendor/'.$class_name.'.php';

            $open = new $class_name;
            $open->appid = $platform['appid'];
            $open->appkey = $platform['appkey'];
            $redirect_uri = rtrim($platform['redirect'], '/');
            $open->url = user::getLoginUrl($redirect_uri) . 'sign=' . $sign;
            $this->forward && $open->url .= '&forward=' . urlencode($this->forward);

            if (isset($_GET['bind']) && $_GET['bind'] == $sign) {
                $open->get_openid();
            } else {
                $open->callback();
            }

            $userid = user_openid::uid($open->openid, $platform);
            if ($userid) {
                $user = user::get($userid, false);
                user::set_cookie($user['username'], $user['password'], array(
                    'uid' => $userid,
                    'username' => $user['username'],
                    'nickname' => $user['nickname'],
                    'status' => $user['status'],
                ));
                $open->cleancookie();
                iPHP::redirect($this->forward);
            } else {
                if (isset($_GET['bind'])) {
                    $user = array();
                    $user['openid'] = $open->openid;
                    $user['platform'] = $platform;
                    $open->cleancookie();
                    iView::assign('user', $user);
                    iView::display('iCMS://user/login.htm');
                } else {
                    $user = $open->get_user_info();
                    $user['openid'] = $open->openid;
                    $user['platform'] = $platform;
                    user::check($user['nickname'], 'nickname') && $user['nickname'] = $sign . '_' . $user['nickname'];
                    $open->cleancookie();
                    iView::assign('user', $user);
                    iView::assign('query', compact(array('sign', 'code', 'state', 'bind')));
                    iView::display('iCMS://user/register.htm');
                }
                exit;
            }
        }
    }
}
