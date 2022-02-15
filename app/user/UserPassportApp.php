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

class UserPassportApp
{
    public static $apiList = ['login', 'logout', 'register', 'findpwd'];
    public $methods = [];
    public function __construct($auto = false)
    {
        if (iAPP::$DO == 'logout') {
            return $this->API_logout();
        }
        /**
         * 已登陆,跳转到个人主页
         */
        if (User::$data) {
            $url = Route::routing('{uid}/home', [User::$id]);
            Helper::redirect($url, true);
        } else {
            UserCP::assign();
        }
    }
    public function API_register()
    {
        $name = 'register.close';
        if (User::$config['register']['enable']) {
            // $this->forward('r');
            // User::status($this->forward, "login");
            $name = 'register';
        }
        $path = sprintf('iCMS://user/%s.htm', $name);
        View::display($path);
    }
    public function ACTION_register()
    {
        User::$config['register']['enable'] or iJson::error('user:register:enable');
        $data = Request::post();
        $account = &$data['account'];
        $phone = $data['phone'];
        $email = $data['email'];
        $data['signup-terms'] == 'on' or  iJson::error('user:register:terms_on');

        if (User::$config['register']['verify']['phone']) {
            if (User::$config['register']['captcha']) {
                try {
                    PluginSmsCode::check($data['phone'], $data['smscode']);
                } catch (\sException $ex) {
                    $msg = $ex->getMessage();
                    iJson::error($msg);
                }
            } else {
                iJson::error('user:register:open_captcha');
            }
        } else {
            //存在短信验证码的情况 不在对图形验证码再次进行验证
            if (User::$config['register']['captcha']) {
                Captcha::check() or iJson::error('iCMS:captcha:error');
            }
        }

        if ($account) {
            $len = strlen($account);
            $len > 30 && iJson::error('user:account:max');
            $len < 6 && iJson::error('user:account:min');
        }

        if ($email) {
            preg_match("/^[\w\-\.]+@[\w\-]+(\.\w+)+$/i", $email) or iJson::error('user:email:error');
            empty($account) && $account = $email;
            User::check($email, 'email') && iJson::error('user:email:exist');
        }
        if ($phone) {
            self::isPhone($phone) or iJson::error('user:phone:error');
            ($account == $email) && $account = $phone;
            empty($account) && $account = $phone;
            User::check($phone, 'phone') && iJson::error('user:phone:exist');
        }

        $ip = Request::ip();
        try {
            self::checkInterval(['regip' => $ip], 'register', 'regdate');
        } catch (\sException $ex) {
            $msg = $ex->getMessage();
            iJson::error($msg);
        }
        $account or iJson::error('user:account:empty');
        User::check($account, 'account') && iJson::error('user:account:exist');

        trim($data['password']) or iJson::error('user:password:empty');
        trim($data['password-confirm']) or iJson::error('user:password-confirm:empty');
        $data['password'] == $data['password-confirm'] or iJson::error('user:password:unequal');

        self::register($data);
        $data['auth'] = User::setCookie($data);
        unset($data['password'], $data['password-confirm']);
        $msg = 'user:register:success';
        if (User::$config['register']['verify']['email']) {
            try {
                $code = auth_encode($data['uid'] . USER_AUTHASH . time());
                $active_url = Route::routing('user/email/active', 'code=' . $code);
                PluginEmail::send([
                    'subject' => Lang::get('user:email:active:subject'),
                    'body' => Lang::get(
                        'user:email:active:body',
                        ['name' => '用户', 'url' => $active_url]
                    ),
                    'address' => [$data['email'], $account]
                ]);
                $data['verify:email'] = true;
                $msg = 'user:email:active:wait';
                // $data['forward'] = Route::routing('user/email/active', 'code=' . $code)
            } catch (\Exception $ex) {
                // $msg = $ex->getMessage();
                // iJson::error($msg);
            }
        }
        iJson::success($data, $msg, $data['forward']);

        // $openid && user_openid::save($data['uid'], $openid, $type, $appid);

        // if ($avatar) {
        //     $avatar = Http::isSafe($avatar);
        //     $avatar && $avatarData = Http::remote($avatar);
        //     if ($avatarData) {
        //         $avatarpath = FilesClient::getRoot(get_user_pic($uid));
        //         File::mkdir(dirname($avatarpath));
        //         File::put($avatarpath, $avatarData);
        //     }
        // }

        //User::set_cache($uid);
        // iJson::display(['code' => 1, 'forward' => $this->forward]);
    }
    public function API_logout()
    {
        User::logout();
        iJson::success('user:logout');
    }
    public function API_login()
    {
        $name = 'login.close';
        if (User::$config['login']['enable']) {
            // $this->forward('r');
            if (Request::get('sign')) {
                // $this->openid();
                $social = new UserSocialApp;
                $social->login();
            }
            // User::status($this->forward, "login");
            $name = 'login';
        }
        $path = sprintf('iCMS://user/%s.htm', $name);
        View::display($path);
    }
    public function ACTION_login()
    {
        User::$config['login']['enable'] or iJson::error('user:login:enable');

        if (User::$config['login']['captcha']) {
            Captcha::check() or iJson::error('iCMS:captcha:error');
        }

        $post = Request::post();
        $gateway = $post['gateway'];
        $forward = $post['forward'];
        $post['remember'] && User::$cookieTime = 14 * 86400;
        try {
            if ($gateway == 'account') {
                $result = self::loginAccount($post);
            } elseif ($gateway == 'phone') {
                $result = self::loginPhone($post);
            }

            if ($result) {
                $forward = $forward ?: Route::routing('{uid}/home', [$result['uid']]);
                iJson::success($result, 'user:login:success', $forward);
            } else {
                iJson::error('user:login:forbidden');
            }
        } catch (\sException $ex) {
            $msg = $ex->getMessage();
            iJson::error($msg);
        }
    }
    public static function loginAccount($data)
    {
        $data = array_map('trim', $data);
        if (empty($data['account']) || empty($data['password'])) {
            throw new sException('user:login:empty');
        }
        return self::login($data['account'], User::password($data['password']));
    }
    public static function loginPhone($data)
    {
        $data = array_map('trim', $data);
        PluginSmsCode::check($data['phone'], $data['smscode']);
        return self::login($data['phone']);
    }
    public static function register(&$data, $status = 1)
    {
        $time = time();
        $ip = Request::ip();
        $account = $data['account'];
        $data = $data + [
            'role_id'       => User::$config['register']['role'],
            'regip'         => $ip,
            'regdate'       => $time,
            'lastloginip'   => $ip,
            'lastlogintime' => $time,
            'setting'       => ['inbox' => ['receive' => 'follow']],
            'status'        => $status,
        ];
        if (empty($data['password'])) {
            $data['password'] = md5($account . uniqid() . time());
        }
        $data['password'] = User::password($data['password']);
        if (empty($data['nickname'])) {
            $data['nickname'] = self::isPhone($account) ?
                substr_replace($account, '****', 3, 4) :
                $account;
        }
        User::check($account, 'account') && iJson::error('user:account:exist');

        DB::beginTransaction();
        try {
            $data['uid'] = UserModel::create($data, true);
            DB::commit();
        } catch (\sException $ex) {
            DB::rollBack();
            $msg = $ex->getMessage();
            iJson::error('iCMS:DB:error');
        }
        AppsApp::hooked($data, 'user.register');
        return $data;
    }

    public static function login($account, $password = null)
    {
        $time = time();
        $ip = Request::ip();

        // self::checkInterval(['lastloginip' => $ip, 'account' => ['<>', $account]],'login','lastlogintime');
        try {
            $loginTimes = self::checkLoginTimes($account);
        } catch (\sException $ex) {
            $msg = $ex->getMessage();
            iJson::error($msg);
        }
        $where = ['status' => '1'];

        if ($password) { //密码登录方式
            if (self::isPhone($account)) {
                $where['phone'] = $account;
            } else {
                $where['account'] = $account;
            }
            $where['password'] = $password;
        } else { //免密登录 手机号
            $where['phone'] = $account;
        }

        $model = UserModel::where($where);
        $data = $model->field('uid,account,password,nickname,status')->get();
        // var_dump(DB::getQueryLog());
        // $openid && user_openid::save(User::$id, $openid, $platform, $appid);

        if ($data) {
            $model->update([
                'lastloginip' => $ip,
                'lastlogintime' => $time
            ]);
        } else {
            //免密登录自动注册
            if (is_null($password) && User::$config['login']['auto_register']) {
                $data = ['account' => $account, 'phone' => $account];
                self::register($data);
            } else {
                self::setLoginTimes($account, $loginTimes);
                //密码登录方式 登录失败
                iJson::error('user:login:forbidden');
            }
        }
        self::setLoginTimes($account, null);
        $data['auth'] = User::setCookie($data);
        unset($data['password']);
        return $data;
    }
    //判断同IP登录或注册时间间隔
    public static function checkInterval($where, $type = 'register', $field = 'regdate')
    {
        $time = time();
        if (User::$config[$type]['interval']) {
            //查找同个IP，最后的时间
            $_time = UserModel::where($where)->max($field);
            $interval = $time - (int)$_time;
            if ($interval < User::$config[$type]['interval']) {
                $msg = format_time(User::$config[$type]['interval'] - $interval, 'cn');
                $msg = Lang::get('user:' . $type . ':interval', [$msg]);
                throw new sException($msg);
            }
        }
    }
    const LOGIN_ERROR_TIMES = 'error/login.%s';
    //记录登录错误次数
    public static function setLoginTimes($account, $loginTimes)
    {
        $time = time();
        $ip = Request::ip();
        if (User::$config['login']['times']) {
            $cacheKey = sprintf(self::LOGIN_ERROR_TIMES, md5($account));
            if ($loginTimes) {
                ++$loginTimes[1];
                $loginTimes[2] = $time;
            } else {
                is_null($loginTimes) or $loginTimes = [$account, 1, $time, $ip];
            }
            Cache::set($cacheKey, $loginTimes, User::$config['login']['interval']);
        }
    }
    //登录错误次数判断
    public static function isPhone($text)
    {
        return preg_match("/^1[34578]\d{9}$/i", $text);
    }
    public static function checkLoginTimes($account)
    {
        $time = time();
        $loginTimes = [];
        if (User::$config['login']['times']) {
            //登录错误次数判断
            $cacheKey = sprintf(self::LOGIN_ERROR_TIMES, md5($account));
            $cache = Cache::get($cacheKey);
            if ($cache) {
                $loginTimes = $cache;
                $interval = $time - (int)$cache[2]; //登录错误时间间隔
                $times = $cache[1]; //登录错误次数
                if (
                    $times >= User::$config['login']['times'] &&
                    $interval < User::$config['login']['interval']
                ) {
                    $msg = format_time(User::$config['login']['interval'] - $interval, 'cn');
                    $msg = Lang::get('user:login:times', [$msg]);
                    throw new sException($msg);
                }
            }
        }
        return $loginTimes;
    }
}
