<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
abstract class UserOauth
{
    public $redirect_uri = '';
    public $openid = '';
    public $appid = '';
    public $appkey = '';
    public $config = [];
    public $name = '';
    public $access_token = '';

    public function __construct()
    {
        $name = get_called_class();
        $this->name = $name = substr($name, 6);
        $this->config = User::$config['social'][$name];
        $this->appid = trim($this->config['appid']);
        $this->appkey = trim($this->config['appkey']);
        $redirect = $this->config['redirect'] ? rtrim($this->config['redirect'], '/') : '';
        $this->redirect_uri = User::getLoginUrl($name,$redirect) ;
    }

    /**
     * Undocumented function
     *
     * @param string $name
     * @return oauth_qq|oauth_weixin|oauth_taobao|oauth_weibo|oauth_qrcode|oauth_google|oauth_github
     */
    public static function getInstance($name)
    {
        $class_name = 'oauth_' . strtolower($name);
        $path = sprintf('%s/vendor/%s.php', __DIR__, $class_name);
        if (!is_file($path)) {
            return false;
        }
        include_once $path;
        $instance = new $class_name();
        // UserUtils::$forward && $instance->redirect_uri .= '?forward=' . urlencode(UserUtils::$forward);
        return $instance;
    }

    public function login()
    {
        $state = md5(uniqid(rand(), true)); //CSRF protection
        self::storage('state', $state);
        $url = $this->getAuthUrl($state);
        header("Location:$url");
    }

    public function getOpenid()
    {
        $this->start();
        // $this->openid = self::storage('openid');
        // if (empty($this->openid)) {
        // }
        return $this->openid;
    }

    public function start()
    {
        $state = self::storage('state');
        if (!($_GET['state'] && $state && $_GET['state'] == $state) && empty($_GET['code'])) {
            return $this->login();
        }
        return $this->getToken();
    }

    public function storage($key, $value = null, $expire = 7200)
    {
        $key = sprintf('%s_oauth_%s', $this->name, $key);
        if (is_null($value)) {
            $value = Cookie::get($key);
            return $value ? auth_decode($value) : null;
        } else {
            Cookie::set($key, $value ? auth_encode($value) : '', $expire);
        }
    }

    abstract protected function getAuthUrl($state);

    abstract protected function getToken();

    abstract protected function getUser();

    abstract protected function clean();
}
