<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class DeveloperAdmincp extends AdmincpBase
{
    public function __construct()
    {
        AdmincpView::set('breadcrumb', false);
    }
    public static function do_bugs()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $json = DeveloperApi::post('bugs', $post);
            echo $json;
            return false;
        }
        include self::view("bugs", 'developer');
    }

    public function do_goto()
    {
        $config = Developer::getConfig();
        $url = DeveloperApi::url('auth', ["token" => $config['auth']]);
        Helper::redirect($url);
    }
    public function do_login()
    {
        $html = DeveloperApi::get('login', $_GET);
        include self::view("main");
    }
    public function do_register()
    {
        $html = DeveloperApi::get('register', $_GET);
        include self::view("main");
    }

    public function do_signup()
    {
        return $this->post('signup');
    }
    public function do_comment()
    {
        $json = DeveloperApi::post('comment', $_POST);
        echo $json;
        return false;
    }
    public function do_accountLogin()
    {
        return $this->post('accountLogin');
    }
    public function do_phoneLogin()
    {
        return $this->post('phoneLogin');
    }
    public function post($method)
    {
        $json = DeveloperApi::post($method, $_POST);
        $array = json_decode($json, true);
        if ($array['data']) {
            Developer::setConfig($array['data']);
        }
        Cookie::destroy('captchaToken');
        echo $json;
        return false;
    }
    public function do_sendSMSCaptcha()
    {
        $json = DeveloperApi::post('sendSMSCaptcha', $_POST);
        Cookie::destroy('captchaToken');
        echo $json;
        return false;
    }
    public function do_getCaptcha()
    {
        Http::set('CURLOPT_HEADER', 1);
        DeveloperApi::get('getCaptcha');
        Cookie::set('captchaToken', Http::getHeader('Captcha-Token'));
        header('Content-type:image/jpeg');
        echo Http::getBody();
        return false;
    }
}
