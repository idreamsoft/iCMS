<?php

// $a = new smsBao([
//     'u' => 'xxxx',
//     'p' => 'oooooo'
// ]);

// print_r($a->send('1350000000',['code'=>123456]));

class smsBao
{
    const API_URL = 'https://api.smsbao.com/sms?';
    public $params = [];
    public $u = '';
    public $p = '';
    public $TemplateCode = '';
    public $statusMap = [
        '0'  => '短信发送成功',
        '-1' => '参数不全',
        '-2' => '服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！',
        '30' => '密码错误',
        '40' => '账号不存在',
        '41' => '余额不足',
        '42' => '帐户已过期',
        '43' => 'IP地址限制',
        '50' => '内容含有敏感词'
    ];
    public $debug = null;

    public function __construct($config)
    {
        $this->u = $config['AccessKeyId'];
        $this->p = $config['AccessKeySecret'];
        $this->TemplateCode = $config['TemplateCode'];
        isset($config['debug']) && $this->debug = $config['debug'];
    }

    public function send($phone, $TemplateParam = [])
    {
        $params['u'] = $this->u; //短信平台注册的用户名
        $params['p'] = $this->p; //手机号
        $params['m'] = $phone; //手机号
        // $params['g'] = $g ?: $this->g; //短信模版id
        $content = $this->TemplateCode;
        foreach ($TemplateParam as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        $params['c'] = $content; //模版内容
        $pieces = [];
        foreach ($params as $key => $value) {
            $pieces[] = $key . '=' . urlencode($value);
        }
        $url = self::API_URL . implode('&', $pieces);
        if (isset($this->debug)) {
            return $this->debug ? true : [];
        }
        $code = $this->remote($url);

        if ($code == '0') {
            return true;
        } else {
            return $this->statusMap[$code];
        }
    }

    public function remote($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $code = curl_exec($ch);
        return $code;
    }
}
