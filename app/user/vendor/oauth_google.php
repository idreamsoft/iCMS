<?php

class oauth_google extends UserOauth
{
    public $scope = 'email profile';
    public $data = [];

    public function getAuthUrl($state)
    {
        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->appid,
            'redirect_uri'  => $this->redirect_uri,
            'scope'         => $this->scope,
            'state'   => $state,
            'access_type'   => 'online',
            'prompt'        => 'consent',
        ]);
        return $url;
    }

    public function getToken()
    {
        $token_url = 'https://oauth2.googleapis.com/token';
        $post_data = [
            'code' => $_GET['code'],
            'client_id' => $this->appid,
            'client_secret' => $this->appkey,
            'redirect_uri' => $this->redirect_uri,
            'grant_type' => 'authorization_code',
        ];

        Http::$CURLOPT_HTTPHEADER[] = 'Content-Type: application/x-www-form-urlencoded';
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

        $url = 'https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $access_token;
        $response = Http::get($url);
        $data = json_decode($response, true);
        if (empty($data['id'])) {
            AppsBase::alert($data['message'] . ' [status:' . $data['status'] . ']');
        }

        $this->openid = $data['id'];
        self::storage('openid', $data['id']);
        $this->data = [
            'openid'   => $data['id'],
            'nickname' => $data['name'],
            'email'    => $data['email'],
            'avatar'   => $data['picture'],
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
