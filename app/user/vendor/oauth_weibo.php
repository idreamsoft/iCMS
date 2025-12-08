<?php

class oauth_weibo extends UserOauth
{
    public $scope = 'promotion,item,usergrade';
    private static $info = '';

    public function getAuthUrl($state)
    {
        $url = 'https://api.weibo.com/oauth2/authorize?response_type=code&'
            . 'client_id=' . $this->appid
            . '&redirect_uri=' . urlencode($this->redirect_uri)
            . '&state=' . $state
            . '&scope=' . $this->scope;

        return $url;
    }

    public function getToken()
    {
        $post_data = 'grant_type=authorization_code&'
            . 'client_id=' . $this->appid
            . '&redirect_uri=' . urlencode($this->redirect_uri)
            . '&client_secret=' . $this->appkey
            . '&code=' . $_GET['code'];

        $response = Http::post('https://api.weibo.com/oauth2/access_token', $post_data);
        $token = json_decode($response, true);
        if ($token['error']) {
            AppsBase::alert($token['error_description']);
        } else {
            // self::storage('token', $token['access_token'], $token['expires_in']);
            // self::storage('openid', $token['uid'], $token['expires_in']);
            $this->access_token = $token['access_token'];
            $this->openid = $token['uid'];
        }
    }

    public function getUser()
    {
        // var_dump($this->access_token);
        // var_dump($this->openid);

        // $this->access_token = self::storage('token');
        // $this->openid = self::storage('openid');

        $url = 'https://api.weibo.com/2/users/show.json'
        . '?access_token=' . $this->access_token
        . '&uid=' . $this->openid;
        $info = Http::get($url);
        $arr = json_decode($info, true);

        return [
            'nickname' => $arr['screen_name'],
            'avatar'   => $arr['avatar_large'],
            'gender'   => $arr['gender'] == 'm' ? '1' : '0'
        ];
    }

    public function clean()
    {
        self::storage('access_token', '', -31536000);
        self::storage('openid', '', -31536000);
        self::storage('state', '', -31536000);
    }

    public function getUrl($url, $access_token = '')
    {
        $headers[] = 'Authorization: OAuth2 ' . $access_token;
        $headers[] = 'API-RemoteIP: ' . $_SERVER['REMOTE_ADDR'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Sae T OAuth2 v0.1');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function postUrl($url, $POSTFIELDS)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Sae T OAuth2 v0.1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);
        $res = curl_exec($ch);
        //$info = curl_getinfo($ch);
        curl_close($ch);
        return $res;
    }
}
