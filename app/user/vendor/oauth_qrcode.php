<?php

class oauth_qrcode
{
    public $redirect_uri = '';
    public $openid = '';
    public $appid = '';
    public $appkey = '';
    public $config = [];
    public $name = 'qrcode';
    public $access_token = '';

    public function __construct()
    {
        $this->config = User::$config['qrcode'];
        $this->appid = trim($this->config['appid']);
        $this->appkey = trim($this->config['appkey']);
        $redirect_uri = $this->config['redirect'] ? rtrim($this->config['redirect'], '/') : '';
        $this->redirect_uri = User::getLoginUrl($redirect_uri) . '/sign/' . $this->name;
    }

    public function getToken()
    {
        $code = Request::sget('code');
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code';
        $url = sprintf($url, $this->config['appid'], $this->config['appkey'], $code);
        $response = Http::get($url);
        $data = json_decode($response, true);
        if (is_array($data) && !isset($data['errcode'])) {
            // Cookie::set('WXQR_ACCESS_TOKEN', auth_encode($data['access_token']));
            // Cookie::set('WXQR_REFRESH_TOKEN', auth_encode($data['refresh_token']));
            // Cookie::set('WXQR_OPENID', auth_encode($data['openid']));
            $this->openid = $data['openid'];
        } else {
            if (in_array($data['errcode'], ['40163', '40029'])) {
                return $this->connect();
            }
            $msg = sprintf('%s (errcode=%s)', $data['errmsg'], $data['errcode']);
            throw new sException($msg, 0);
        }
    }

    /**静默登陆使用 */
    public function authorize($rurl, $state = '')
    {
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_base&state=%s#wechat_redirect';
        $url = sprintf($url, $this->config['appid'], urlencode($rurl), $state);
        header("Location: $url");
    }

    public function connect()
    {
        if ($this->config['enable']) {
            $state = Request::sget('state');
            $forward = Request::get('forward');
            $rurl = Route::routing('user/login/qrcode');
            $rurl = Route::make(['bind'=>'qr', 'state'=>$state, 'forward'=>$forward], $rurl);

            $query = [
                'appid'=> $this->config['appid'],
                'ref'  => $rurl
            ];
            if ($revolve = $this->config['revolve']) {
                $query['do'] = 'authorize';
                $query['state'] = $state;
                $url = Route::make($query, $revolve);
                header("Location: $url");
            } else {
                $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_base&state=%s#wechat_redirect';
                $url = sprintf($url, $this->config['appid'], urlencode($rurl), $state);
                header("Location: $url");
            }
        }
    }

    public function getOpenid()
    {
        $code = $_GET['code'];
        if ($code) {
            $this->getToken();
        } else {
            $this->make();
        }
    }

    public function make()
    {
        if ($this->config['enable']) {
            if (stripos($_SERVER['HTTP_USER_AGENT'], 'micromessenger') !== false) {
                $this->connect();
            } else {
                $state = Request::sget('state');
                $url = Route::routing('user/login/qrcode');
                $url = Route::make([
                    'state'  => $state,
                    'forward'=> UserUtils::$forward
                    // 'forward'=> 'http://v8.icmsdev.com/article.php?id=19971'
                ], $url);
                // var_dump($url);
                PluginQRcode::make($url);
            }
        } else {
            PluginQRcode::make('微信扫码登陆未开启');
        }
        exit;
    }

    public function getUser()
    {
    }

    public function clean()
    {
    }
}
