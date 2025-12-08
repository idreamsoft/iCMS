<?php

class oauth_taobao extends UserOauth
{
    public $scope = 'promotion,item,usergrade';
    public $url = '';
    public $info = '';

    public function login()
    {
        $state = md5(uniqid(rand(), true)); //CSRF protection
        Cookie::set('TB_STATE', auth_encode($state));
        $login_url = 'https://oauth.taobao.com/authorize?response_type=code&client_id='
            . $this->appid . '&redirect_uri=' . urlencode(CALLBACK_URL . $this->callback)
            . '&state=' . $state
            . '&scope=' . $this->scope;
        header("Location:$login_url");
    }

    public function getCode()
    {
        $state = auth_decode(Cookie::get('TB_STATE'));
        if ($_GET['state'] != $state && empty($_GET['code'])) {
            $this->login();
            exit;
        }

        $POST_FIELDS = 'grant_type=authorization_code&'
            . 'client_id=' . $this->appid . '&redirect_uri=' . urlencode(CALLBACK_URL . $this->callback)
            . '&client_secret=' . $this->appkey . '&code=' . $_GET['code'];

        $response = $this->postUrl('https://oauth.taobao.com/token', $POST_FIELDS);
        $this->info = json_decode($response, true);
        if ($this->info['error']) {
            $this->login();
            exit;
        }
        $this->openid = $this->info['taobao_user_id'];
        Cookie::set('TB_OPENID', $this->openid);
    }

    public function getUserInfo()
    {
        $user['nickname'] = $this->info['taobao_user_nick'];
        $user['gender'] = 0; //$user['gender']=="??"?'1':0;
        $user['avatar'] = ''; //$user['figureurl_2'];
        return $user;
    }

    public function cleanCookie()
    {
        Cookie::set('TB_STATE', '', -31536000);
        Cookie::set('TB_OPENID', '', -31536000);
    }

    public function postUrl($url, $POSTFIELDS)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
}
