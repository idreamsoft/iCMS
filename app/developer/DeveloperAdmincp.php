<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
/**
 * 开发者
 * 
 * @role 1
 */
class DeveloperAdmincp extends AdmincpBase
{
    public function __construct()
    {
        parent::__construct();
        AdmincpView::set('breadcrumb', false);
    }

    public static function done_bugs()
    {
        $post = Request::post();
        $json = DeveloperApiProxy::post('bugs', $post);
        echo $json;
        return false;
    }

    /**
     * 获取远程静态资源
     *
     * @role 1
     * @return string
     */
    public function do_assets()
    {
        if ($js = Request::sget('js')) {
            header(sprintf('Content-Type: %s; charset=UTF-8', FilesMime::get('js')));
            $html = DeveloperApiProxy::get('getjs', ['path'=>$js]);
        }
        if ($css = Request::sget('css')) {
            header(sprintf('Content-Type: %s; charset=UTF-8', FilesMime::get('css')));
            $html = DeveloperApiProxy::get('getcss', ['path'=>$css]);
        }
        exit($html);
    }

    public function do_goto()
    {
        $config = Developer::getConfig();
        $url = DeveloperApiProxy::url('auth', ['token' => $config['auth']]);
        Utils::jumpto($url);
    }

    public function do_login()
    {
        $html = DeveloperApiProxy::get('login', $_GET);
        self::setJsPath('developer/assets/js/login');
        include self::view('main');
    }

    public function do_register()
    {
        $html = DeveloperApiProxy::get('register', $_GET);
        self::setJsPath('developer/assets/js/register');
        include self::view('main');
    }

    public function done_signup()
    {
        return $this->post('signup');
    }

    public function do_comment()
    {
        $json = DeveloperApiProxy::post('comment', $_POST);
        echo $json;
        return false;
    }

    public function done_accountLogin()
    {
        return $this->post('accountLogin');
    }

    public function done_phoneLogin()
    {
        return $this->post('phoneLogin');
    }

    public function post($method)
    {
        $json = DeveloperApiProxy::post($method, $_POST);
        $array = json_decode($json, true);
        if ($array['data']) {
            Developer::setConfig($array['data']);
        }
        Session::uset('captchaToken');
        echo $json;
        return false;
    }

    public function done_sendSMSCaptcha()
    {
        $json = DeveloperApiProxy::post('sendSMSCaptcha', $_POST);
        Session::uset('captchaToken');
        echo $json;
        return false;
    }

    public function do_getCaptcha()
    {
        Http::set('CURLOPT_HEADER', 1);
        DeveloperApiProxy::get('getCaptcha');
        Session::set('captchaToken', Http::getHeader('Captcha-Token'));
        header('Content-type:image/jpeg');
        echo Http::getBody();
        return false;
    }
}
