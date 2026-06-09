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
    public $stable_token = '';

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
        $code = Request::get('code');
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code';
        $url = sprintf($url, $this->config['appid'], $this->config['appkey'], $code);
        $json = Http::get($url);
        $response = json_decode($json, true);

        if (is_array($response) && $response['access_token']) {
            // Cookie::set('WXQR_ACCESS_TOKEN', auth_encode($data['access_token']));
            // Cookie::set('WXQR_REFRESH_TOKEN', auth_encode($data['refresh_token']));
            // Cookie::set('WXQR_OPENID', auth_encode($data['openid']));
            $this->access_token = $response['access_token'];
            $this->openid = $response['openid'];
        } else {
            if (in_array($response['errcode'], ['40163', '40029'])) {
                return $this->connect();
            }
            $msg = sprintf('%s (errcode=%s)', $response['errmsg'], $response['errcode']);
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
            $state = Request::get('state');
            $forward = Request::get('forward');
            $rurl = Route::routing('user/login/qrcode');
            $rurl = Route::make(['bind'=>'qr', 'state'=>$state, 'forward'=>$forward], $rurl);

            $query = [
                'appid'=> $this->config['appid'],
                'ref'  => $rurl
            ];
            if ($revolve = $this->config['revolve']) {
                // $query['do'] = 'authorize';
                $query['state'] = $state;
                $query['url'] = $rurl;
                // $url = Route::make($query, $revolve);
                // header("Location: $url");
                $query['do'] = 'transfer';
                $query['app'] = 'weixin';
                $rurl = Route::make($query, $revolve);
            } else {
                // $this->authorize($rurl, $state);
                // $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_base&state=%s#wechat_redirect';
                // $url = sprintf($url, $this->config['appid'], urlencode($rurl), $state);
                // header("Location: $url");
            }
            $this->authorize($rurl, $state);
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
    /**
     * 获取稳定版接口调用凭据
     *
     * @return mixed
     */
    public function getStableToken()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/stable_token';
        $json = Http::post($url, json_encode([
            'grant_type'   => 'client_credential',
            'appid'        => $this->config['appid'],
            'secret'       => $this->config['appkey'],
            'force_refresh'=> false
        ]));
        $response = json_decode($json, true);
        if ($response['errcode']) {
            $msg = sprintf('getStableToken %s (errcode=%s)', $response['errmsg'], $response['errcode']);
            throw new sException($msg, 0);
        }
        $this->stable_token = $response['access_token'];
        return $this->stable_token;
    }
    //$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
    public function getJsApiTicket()
    {
        $stable_token = $this->getStableToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=' . $stable_token;
        $json = Http::get($url);
        $response = json_decode($json, true);
        if ($response['errcode']) {
            $msg = sprintf('getJsApiTicket %s (errcode=%s)', $response['errmsg'], $response['errcode']);
            throw new sException($msg, 0);
        }
        return $response['ticket'];
    }

    public function getSignPackage($url = '')
    {
        $jsapiTicket = $this->getJsApiTicket();

        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
        empty($url) && $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket={$jsapiTicket}&noncestr={$nonceStr}&timestamp={$timestamp}&url={$url}";

        $signature = sha1($string);

        $signPackage = [
            'appId'     => $this->config['appid'],
            'nonceStr'  => $nonceStr,
            'timestamp' => $timestamp,
            'url'       => $url,
            'signature' => $signature,
            // 'rawString' => $string
        ];
        return $signPackage;
    }
    private function createNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    public function getUser()
    {
    }

    public function clean()
    {
    }
}
