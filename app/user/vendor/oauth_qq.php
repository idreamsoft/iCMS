<?php

class oauth_qq extends UserOauth
{
    public $scope = 'get_user_info,add_topic,add_one_blog,add_album,upload_pic,list_album,add_share,check_page_fans,do_like,get_tenpay_address,get_info,get_other_info,get_fanslist,get_idolist,add_idol';

    public function getAuthUrl($state)
    {
        $url = 'https://graph.qq.com/oauth2.0/authorize?response_type=code'
            . '&client_id=' . $this->appid
            . '&redirect_uri=' . urlencode($this->redirect_uri)
            . '&state=' . $state
            . '&scope=' . $this->scope;
        return $url;
    }

    public function getToken()
    {
        $url = 'https://graph.qq.com/oauth2.0/token?grant_type=authorization_code'
            . '&client_id=' . $this->appid
            . '&redirect_uri=' . urlencode($this->redirect_uri)
            . '&client_secret=' . $this->appkey
            . '&code=' . $_GET['code'];

        $response = $this->get_url_contents($url);

        if (strpos($response, 'callback') !== false) {
            $lpos = strpos($response, '(');
            $rpos = strrpos($response, ')');
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
            $msg = json_decode($response);
            if (isset($msg->error)) {
                // $this->cleanCookie();
                // trigger_error($msg->error_description,E_USER_ERROR);
                $this->login();
            }
        }
        $params = [];
        parse_str($response, $params);
        $this->access_token = $params['access_token'];
        self::storage('access_token', $params['access_token']);

        $this->openid($params['access_token']);
    }

    public function openid($access_token = '')
    {
        $url = 'https://graph.qq.com/oauth2.0/me?access_token=' . $access_token;
        $response = $this->get_url_contents($url);
        if (strpos($response, 'callback') !== false) {
            $lpos = strpos($response, '(');
            $rpos = strrpos($response, ')');
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
        }

        $me = json_decode($response);
        if (isset($me->error)) {
            // trigger_error($me->error_description,E_USER_ERROR);
            $this->login();
        }

        $this->openid = $me->openid;
        self::storage('openid', $me->openid);
    }

    public function getUser()
    {
        $url = 'https://graph.qq.com/user/get_user_info?'
            . 'access_token=' . $this->access_token
            . '&oauth_consumer_key=' . $this->appid
            . '&openid=' . $this->openid
            . '&format=json';

        $info = $this->get_url_contents($url);
        $arr = json_decode($info, true);
        if ($arr['ret'] != '0') {
            trigger_error($arr['msg'], E_USER_ERROR);
        }

        $arr['avatar'] = $arr['figureurl_2'];
        $arr['gender'] = $arr['gender'] == '??' ? '1' : 0;
        return $arr;
    }

    public function clean()
    {
        self::storage('access_token', '', -31536000);
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
