<?php

class oauth_weixin extends UserOauth
{
    public $scope = 'snsapi_login';

    public function getAuthUrl($state)
    {
        $url = 'https://open.weixin.qq.com/connect/qrconnect?response_type=code'
            . '&appid=' . $this->appid
            . '&redirect_uri=' . urlencode($this->redirect_uri)
            . '&state=' . $state
            . '&scope=' . $this->scope;
        return $url;
    }

    public function getToken()
    {
        $token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?grant_type=authorization_code&'
            . 'appid=' . $this->appid
            . '&secret=' . $this->appkey
            . '&code=' . $_GET['code'];

        $response = $this->get_url_contents($token_url);
        $token = json_decode($response, true);
        if (is_array($token) && !isset($token['errcode'])) {
            self::storage('access_token', $token['access_token']);
            self::storage('refresh_token', $token['refresh_token']);
            self::storage('openid', $token['openid']);
            $this->openid = $token['openid'];
        } else {
            $this->login();
            exit;
        }
    }

    public function getUser()
    {
        $access_token = self::storage('access_token');
        $openid = self::storage('openid');
        $get_user_info = 'https://api.weixin.qq.com/sns/userinfo?'
            . 'access_token=' . $access_token
            . '&openid=' . $openid;

        $info = $this->get_url_contents($get_user_info);
        $arr = json_decode($info, true);
        $arr['avatar'] = $arr['headimgurl'];
        $arr['gender'] = $arr['sex'];
        return $arr;
    }

    public function clean()
    {
        self::storage('access_token', '', -31536000);
        self::storage('refresh_token', '', -31536000);
        self::storage('openid', '', -31536000);
        self::storage('state', '', -31536000);
    }

    public function get_url_contents($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);
        curl_close($ch);
        //-------请求为空
        if (empty($response)) {
            die('可能是服务器无法请求https协议</h2>可能未开启curl支持,请尝试开启curl支持，重启web服务器，如果问题仍未解决，请联系我们');
        }
        return $response;
    }
}
