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
    const LOGIN_QRCODE_KEY = 'loginQr';
    const LOGIN_ERROR_TIMES = 'error/login.%s';

    public static $apiList = ['login', 'loginQr', 'logout', 'register', 'findpwd', 'wxOpenid', 'activate'];
    public $methods = [];
    public static $AUTO_NAME = '微信用户';

    public function __construct()
    {
        if (Application::$DO == 'logout') {
            return $this->api_logout();
        }

        /**
         * 已登陆,跳转到个人主页
         */
        $checkLogin = User::$id ? true : false;
        $state = Request::sparam('state');
        //扫码时不判断
        if ($state && stripos($state, self::LOGIN_QRCODE_KEY) !== false) {
            $checkLogin = false;
        }

        if ($checkLogin) {
            $url = User::route('url');
            Utils::jumpto($url);
        } else {
            UserCenter::assign();
        }
    }

    public function done_findpwd()
    {
        $post = Request::post();
        Captcha::check($post['captcha2']);
        PluginVerify::verify($post['email'], $post['emailcode'], 'email');

        trim($post['passwd']) or iJson::error('user:password:empty:new');
        trim($post['passwd-confirm']) or iJson::error('user:password:empty:confirm');
        $post['passwd'] == $post['passwd-confirm'] or iJson::error('user:password:unequal');

        $user = UserModel::where(['email'=>$post['email']])->get();
        empty($user) && iJson::error('user:not_exist');
        User::checkStatus($user['status']);
        $password = User::password($post['passwd']);
        UserModel::update(compact('password'), $user['id']);

        return ['message'=>'Successfully updated', 'url'=>Route::routing('user/login')];
    }

    public function done_findPwdStep()
    {
        $post = Request::post();
        // PluginEmail::$debug = iPHP_DEBUG;
        PluginVerify::email($post['email'], $post['code']);
    }
    public $activate_time = 86400;

    /**
     * 重新发送验证邮件
     *
     * @return void
     */
    public function done_resend_activate()
    {
        $code = Request::post('code');
        $ref = $_SERVER['HTTP_REFERER'];
        $decode = auth_decode($code);
        empty($code) && iJson::error('request illegal');
        if ($decode) {
            list($userid, $expire_time) = explode(USER_AUTHASH, $decode);
            if (time() - $expire_time < $this->activate_time) {
                $user = UserModel::get($userid);
                $this->send_verify_email($user);
                return true;
            } else {
                iJson::error('user:activate:normal');
            }
        } else {
            iJson::error('request illegal');
        }
    }

    public function api_activate()
    {
        $code = Request::get('code');
        $decode = auth_decode($code);
        $msg = 'user:activate:expire';
        $status = -1;
        if ($decode) {
            list($userid, $expire_time) = explode(USER_AUTHASH, $decode);
            if (time() - $expire_time < $this->activate_time) {
                $user = UserModel::get($userid);
                $status = $user['status'];
                if ($status === 4) {
                    $flag = UserModel::where($userid)->update(['status'=> 1]);
                    if ($flag) {
                        $status = 1;
                        // User::setCookie($user);
                    }
                    $msg = $flag ? 'user:activate:success' : 'user:activate:failure';
                } elseif ($status === 1) {
                    $msg = 'user:activate:activated';
                } else {
                    $msg = 'user:activate:failure';
                    // $msg = 'user:status:'.$status;
                }
            }
        }
        View::assign([
            'code'   => $code,
            'status' => $status,
            'msg'    => Lang::get($msg)
        ]);
        View::display('iCMS://user/activate.htm');
    }

    public function api_check()
    {
        $data = Request::post();
        $without = Request::get('without');
        $account = $data['account'];
        $phone = $data['phone'];
        $email = $data['email'];
        if (isset($data['phone'])) {
            $check = User::check($data['phone'], 'phone');
        }
        if (isset($data['email'])) {
            $check = User::check($data['email'], 'email');
        }
        if (isset($data['account'])) {
            $check = User::check($data['account'], 'account');
        }
        if (isset($without)) {
            exit($check ? 'true' : 'false');
        }
        exit($check ? 'false' : 'true');
    }

    public function api_register()
    {
        $name = 'register.close';
        if (User::$config['register']['enable']) {
            $name = 'register';
        }
        $this->registerPage($name);
    }

    public function registerPage($name = 'register')
    {
        $mode = User::$config['register']['mode'];
        $modes = [];
        if (in_array('account', $mode)) {
            $modes['account'] = true;
        }
        if (in_array('phone', $mode)) {
            if (isset($modes['account'])) {
                unset($modes['account']);
            }
            $modes['phone'] = true;
        }
        if (in_array('email', $mode)) {
            $modes['email'] = true;
        }

        View::assign('modes', $modes);
        $path = sprintf('iCMS://user/%s.htm', $name);
        View::assign('mode', $mode);
        View::display($path);
    }

    public function done_register()
    {
        User::$config['register']['enable'] or iJson::error('user:register:enable');
        $data = Request::post();
        $account = &$data['account'];
        $phone = $data['phone'];
        $email = $data['email'];
        $data['signup-terms'] == 'on' or iJson::error('user:register:terms_on');

        if(empty($data['oauth'])){
            if (in_array('phone',User::$config['register']['mode']) && User::$config['register']['verify']['phone']) {
                if (User::$config['register']['captcha']) {
                    PluginVerify::phone($data['phone'], $data['smscode']);
                } else {
                    iJson::error('user:register:open_captcha');
                }
            } else {
                //存在短信验证码的情况 不在对图形验证码再次进行验证
                if (User::$config['register']['captcha']) {
                    Captcha::check();
                }
            }
        }else{
            $oauth = Crypt::decrypt($data['oauth']);
            if (User::$config['register']['captcha']) {
                Captcha::check();
            }
        }

        if ($account) {
            $len = strlen($account);
            $len > 30 && iJson::error('user:account:max');
            $len < 6 && iJson::error('user:account:min');
        }

        $status = 1;
        if (User::$config['register']['verify']['email']) {
            $status = 4;
            if ($email) {
                Utils::isEmail($email) or iJson::error('user:email:error');
                User::check($email, 'email') && iJson::error('user:email:exist');
                empty($account) && $account = $email;
            } else {
                if ($oauth) {
                    $status = 1;
                } else {
                    iJson::error('user:email:empty');
                }
            }
        }

        if ($phone) {
            Utils::isPhone($phone) or iJson::error('user:phone:error');
            User::check($phone, 'phone') && iJson::error('user:phone:exist');
            ($account == $email) && $account = $phone;
            empty($account) && $account = $phone;
        }

        $ip = Request::ip();
        self::checkInterval(['regip' => $ip], 'register', 'regdate');

        $account or iJson::error('user:account:empty');
        User::check($account, 'account') && iJson::error('user:account:exist');

        trim($data['password']) or iJson::error('user:password:empty');
        trim($data['password-confirm']) or iJson::error('user:password-confirm:empty');
        $data['password'] == $data['password-confirm'] or iJson::error('user:password:unequal');

        self::register($data, $status);
        $status === 1 && User::setCookie($data);
        unset($data['password'], $data['password-confirm']);
        $msg = 'user:register:success';
        if (User::$config['register']['verify']['email']) {
            if (empty($oauth)) {
                $this->send_verify_email($data);
                $msg = 'user:email:active:wait';
            }
        }
        $forward = Route::routing('user/home');
        return [[], $msg, $forward];
        // iJson::success($data, $msg, $data['forward']);

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
        // iJson::display(['code' => 1, 'forward' => UserUtils::$forward]);
    }

    public function done_logout()
    {
        return $this->api_logout();
    }

    public function api_logout()
    {
        User::logout();
        if (Request::isAjax()) {
            return 'user:logout';
        } else {
            Utils::jumpto(UserUtils::$forward);
        }
    }

    public function api_login()
    {
        $name = 'login.close';
        if (User::$config['login']['enable']) {
            // UserUtils::forward('r');
            if ($sign = Request::sget('sign')) {
                if (User::$config['social'][strtolower($sign)]) {
                    return $this->loginSocial();
                }
                if ($sign == 'qrcode' && User::$config['qrcode']['enable']) {
                    return $this->loginQrcode();
                }
                if ($sign == 'silent') {
                    return $this->silentLogin2();
                    // exit;
                    // return $this->loginQrcode();
                }
            }
            $name = 'login';
        }
        $path = sprintf('iCMS://user/%s.htm', $name);
        View::display($path);
    }

    public static function getLoginQrKey($state)
    {
        return self::LOGIN_QRCODE_KEY . '/' . $state;
    }

    public function setLoginQrData($key, $user)
    {
        $data = array_filter_keys($user, 'id,account,password,nickname,status');
        $data['forward'] = UserUtils::$forward;
        Cache::set($key, $data);
    }

    public function api_loginQr()
    {
        $state = Request::spost('state');
        $key = self::getLoginQrKey($state);
        $data = Cache::get($key);
        if ($data) {
            User::setCookie($data);
            Cache::delete($key);
            return [
                'data'   => ['userid'=>$data['id']],
                'url'    => $data['url'],
                'message'=> 'user:login:success',
            ];
        }
    }

    public function done_login()
    {
        User::$config['login']['enable'] or iJson::error('user:login:enable');

        $post = Request::post();
        $gateway = $post['gateway'];
        if (User::$config['login']['captcha']) {
            $prefix = $gateway == 'account' ? 'account_login' : 'phone_login';
            Captcha::check($post['captcha'], $prefix);
        }
        $forward = $post['forward'];
        $post['remember'] && User::$cookieTime = 14 * 86400;
        if ($gateway == 'account') {
            $result = self::loginAccount($post);
        } elseif ($gateway == 'phone') {
            $result = self::loginPhone($post);
        }
        $forward = $forward ?: Route::routing('{userid}/home', [$result['id']]);

        if ($oauth = $post['oauth']) {
            $oauth = Crypt::decrypt($oauth);
            if (is_array($oauth) && $oauth['openid']) {
                UserOpenid::save($result['id'], $oauth['openid'], $oauth['platform'], $oauth['appid']);
                if ($oauth['QRCODE_KEY']) {
                    $user = User::get($result['id'], false);
                    self::setLoginQrData($oauth['QRCODE_KEY'], $user);
                }
            }
        }
        return [$result, 'user:login:success', $forward];
    }

    /**
     * 微信扫码登陆
     *
     * @return void
     */
    public function loginQrcode()
    {
        if ($social = UserOauth::getInstance('qrcode')) {
            $social->getOpenid();//生成图片
            $this->openid($social);
        }
    }

    /**
     * 社交媒体账号登录
     *
     * @return void
     */
    public function loginSocial()
    {
        $sign = Request::sget('sign');
        if ($social = UserOauth::getInstance($sign)) {
            $social->getOpenid();
            $this->openid($social);
        }
    }
    public function forward($state,$params=null){
        $key = $state.'_forward';
        $forward = Cache::get($key);
        if($forward){
            Cache::delete($key);
            if(strpos($forward,'::')!==false){
                $forward = iPHP::callback($forward,$params);
                if(is_null($forward)){
                    return;
                }
            }
            // testHook::app_user_openid_forward
            // Application::hooks('user', 'forward','openid',[&$forward,$state,$params]);

            if($forward=='close'){
                list($info,$user,$social) = $params;
                $package = $social->getSignPackage();
                View::assign('wxJsApiConfig', json_encode($package));
                View::assign('social', $info);
                View::display('iCMS://weixin.close.htm');
                exit;
            }
            if(Request::isUrl($forward)){
                Utils::jumpto($forward);
            }
        }
        Utils::jumpto(UserUtils::$forward);
    }
    public function openid($social)
    {
        $state = Request::sget('state', '');
        $bind = Request::sget('bind');

        if (empty($social->openid)) {
            AppsBase::alert('OpenID not obtained');
            return;
            // return Utils::jumpto(UserUtils::$forward);
        }
        $userid = UserOpenid::userid($social->openid, $social->name);
        $info = [
            'openid'        => $social->openid,
            'account'       => short_md5($social->openid . $social->appid),
            'appid'         => $social->appid,
            'QRCODE_KEY'    => '',
            'state'         => $state,
            'platform'      => $social->name
        ];

        if (stripos($state, self::LOGIN_QRCODE_KEY) !== false) {
            $info['QRCODE_KEY'] = self::getLoginQrKey($state);
        }
        if ($userid) {
            $user = User::get($userid, false);
            if ($info['QRCODE_KEY']) {
                self::setLoginQrData($info['QRCODE_KEY'], $user);
            }
            User::setCookie($user);
            $social->clean();
            return $this->forward($state,[$info,$user,$social]);
        } else {
            View::assign('oauth', Crypt::encrypt($info));
            View::assign('social', $info);
            View::assign('query', compact(['sign', 'code', 'state', 'bind']));
            $autoRegister = false;
            if($bind=='qr' && User::$config['login']['qrcode_auto_register']){
                $autoRegister = true;
            }
            // testHook::app_user_openid_register
            Application::hooks('user', 'register','openid',[&$autoRegister,$state,[$info,$social]]);

            if($autoRegister){
                $data = [
                    'email'    => sprintf('%s@%s.wxa', $social->openid, $social->appid),
                    'account'  => sprintf('%s@%s', $social->openid, $social->appid),
                    'nickname' => self::$AUTO_NAME . date('Ymd') . random(4),
                    'password' => $social->openid,
                    'type'     => UserOpenid::TYPE_WXA,
                    'oauth'    => $info,
                ];
                $user = self::register($data);
                if ($info['QRCODE_KEY']) {
                    self::setLoginQrData($info['QRCODE_KEY'], $user);
                }
                User::setCookie($user);
                $social->clean();
                UserUtils::$forward = User::route('url');
                return $this->forward($state,[$info,$user,$social]);
            }

            if (isset($bind)) {
                $social->clean();
                View::display('iCMS://user/login.htm');
            } else {
                $user = $social->getUser();
                $social->clean();
                // User::check($user['nickname'], 'nickname') && $user['nickname'] = $sign . '_' . $user['nickname'];
                View::assign('USER', $user);
                $this->registerPage();
            }
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
        PluginVerify::phone($data['phone'], $data['smscode']);
        return self::login($data['phone']);
    }

    public static function register(&$data, $status = 1)
    {
        $time = time();
        $ip = Request::ip();
        $account = $data['account'];
        $data = $data + [
            'role_id'       => User::defaultRole(),
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
            $data['nickname'] = Utils::isPhone($account) ?
                substr_replace($account, '****', 3, 4) :
                $account;
        }
        User::check($account, 'account') && AppsBase::alert('user:account:exist');

        $data['id'] = UserModel::create($data, true);
        if ($data['oauth']) {
            $oauth = $data['oauth'];
            $oauth = is_array($oauth)? $oauth : Crypt::decrypt($oauth);
            if (is_array($oauth) && $oauth['openid']) {
                UserOpenid::save($data['id'], $oauth['openid'], $oauth['platform'], $oauth['appid']);
                UserModel::update([
                    'type'=> UserOpenid::platform($oauth['platform'])
                ], $data['id']);
            }
        }
        AppsBase::hooked($data, 'user.register');
        return $data;
    }

    public static function login($account, $password = null)
    {
        $time = time();
        $ip = Request::ip();

        // self::checkInterval(['lastloginip' => $ip, 'account' => ['<>', $account]],'login','lastlogintime');
        $loginTimes = self::checkLoginTimes($account);

        $where = [];

        if ($password) { //密码登录方式
            if (Utils::isPhone($account)) {
                $where['phone'] = $account;
            } elseif (Utils::isEmail($account)) {
                $where['email'] = $account;
            } else {
                $where['account'] = $account;
            }
            $where['password'] = $password;
        } else { //免密登录 手机号
            $where['phone'] = $account;
        }

        $model = UserModel::where($where);
        $data = $model->field('id,account,password,nickname,status')->get();

        if ($data) {
            User::checkStatus($data['status']);
            $model->update([
                'lastloginip'   => $ip,
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
                AppsBase::alert('user:login:forbidden');
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
                AppsBase::alert($msg);
            }
        }
    }

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
                    AppsBase::alert($msg);
                }
            }
        }
        return $loginTimes;
    }

    public function send_verify_email($user)
    {
        $code = auth_encode($user['id'] . USER_AUTHASH . time());
        $active_url = Route::routing('user/activate', 'code=' . $code);
        PluginEmail::send([
            'subject' => Lang::get('user:email:active:subject'),
            'body'    => Lang::get(
                'user:email:active:body',
                ['name' => '用户', 'url' => $active_url]
            ),
            'address' => [$user['email'], $user['account']]
        ]);
    }
    public static function silentLogin2()
    {
        try {
            if ($social = UserOauth::getInstance('qrcode')) {
                $url = $_SERVER['REQUEST_URL'];
                $code = $_GET['code'];
                $state = $_GET['state'];
                if (empty($code) && empty($state)) {
                    $social->authorize($url, 'silent');
                } else {
                    $social->getToken();
                    $userid = UserOpenid::userid($social->openid, $social->name);
                    if ($userid) {
                        $user = UserModel::get($userid);
                        // User::setCookie($user);
                        // testHook::app_user_silent_login
                        Application::hooks('user', 'login','silent',[$user,$social]);
                    }
                }
            }
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        } 
    }
    public static function silentLogin()
    {
        $config = Config::get('user');
        if ($config && isset($config['login']) && isset($config['login']['auto_wx_login'])) {
            if (Utils::isWeChatBrowser()) {
                if (User::status()) {
                    return true;
                }

                User::$config = $config;
                try {
                    if ($social = UserOauth::getInstance('qrcode')) {
                        $url = $_SERVER['REQUEST_URL'];
                        $code = $_GET['code'];
                        $state = $_GET['state'];
                        if (empty($code) && empty($state)) {
                            $social->authorize($url, 'silent');
                        } else {
                            $social->getToken();
                            $userid = UserOpenid::userid($social->openid, $social->name);
                            if ($userid) {
                                $user = UserModel::get($userid);
                                User::setCookie($user);
                            }
                        }
                    }
                } catch (\Exception $ex) {
                    echo $ex->getMessage();
                }
            }
        }
    }
}
