<?php

class oauth_github extends UserOauth
{
    public $scope = 'user:email';
    public $data = [];

    public function getAuthUrl($state)
    {
        $url = 'https://github.com/login/oauth/authorize?' . http_build_query([
            'scope'         => $this->scope,
            'client_id'     => $this->appid,
            'redirect_uri'  => $this->redirect_uri
        ]);

        return $url;
    }

    public function getToken()
    {
        $token_url = 'https://github.com/login/oauth/access_token';
        $post_data = [
            'client_id'     => $this->appid,
            'client_secret' => $this->appkey,
            'code'          => $_GET['code']
        ];
        Http::$CURLOPT_HTTPHEADER[] = 'Accept: application/json';
        $response = Http::post($token_url, http_build_query($post_data));
        $token = json_decode($response, true);
        if ($token['error']) {
            AppsBase::alert($token['error_description']);
        } else {
            $this->access_token = $token['access_token'];
            self::storage('token', $this->access_token);
        }
        return $this->getUser();
    }



    public function getUser()
    {
        if ($this->data) {
            return $this->data;
        }

        $access_token = $this->access_token;
        $url = 'https://api.github.com/user';
        Http::$CURLOPT_HTTPHEADER[] = sprintf('Authorization: token %s', $access_token);
        $response = Http::get($url);
        $data = json_decode($response, true);
        if (empty($data['id'])) {
            AppsBase::alert($data['message'] . ' [status:' . $data['status'] . ']');
        }
        $this->openid = $data['id'];
        self::storage('openid', $data['id']);
        $this->data = [
            'openid'   => $data['id'],
            'nickname' => $data['login'],
            'email'    => $data['email'],
            'avatar'   => $data['avatar_url'],
        ];
        return $this->data;
    }

    public function clean()
    {
        self::storage('token', '', -31536000);
        self::storage('openid', '', -31536000);
        self::storage('state', '', -31536000);
    }
}
