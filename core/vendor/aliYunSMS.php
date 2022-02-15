<?php

// $a = new aliYunSMS([
//     'AccessKeyId' => 'xxxx',
//     'AccessKeySecret' => 'oooooo',
//     'TemplateCode' => 'SMS_11111',
//     'SignName' => 'iCMS账号'
// ]);

// print_r($a->send('1350000000',['code'=>123456]));

class aliYunSMS
{
    const API_URL = 'http://dysmsapi.aliyuncs.com/?';
    public $params = array();
    public $AccessKeyId = '';
    public $AccessKeySecret = '';
    public $TemplateCode = '';
    public $SignName = '';
    public $debug = null;

    public function __construct($config)
    {
        $this->AccessKeyId = $config['AccessKeyId'];
        $this->AccessKeySecret = $config['AccessKeySecret'];
        $this->TemplateCode = $config['TemplateCode'];
        $this->SignName = $config['SignName'];
        isset($config['debug']) && $this->debug = $config['debug'];
    }

    public function send($phone, $TemplateParam, $SignName = null, $TemplateCode = null)
    {
        $params["PhoneNumbers"]     = $phone; //手机号
        $params["SignName"]         = $SignName ?: $this->SignName; //签名
        $params["TemplateCode"]     = $TemplateCode ?: $this->TemplateCode; //短信模版id
        $params["TemplateParam"]    = json_encode($TemplateParam); //模版内容
        $params["AccessKeyId"]      = $this->AccessKeyId; //key
        $params["RegionId"]         = "cn-hangzhou"; //固定参数
        $params["Format"]           = "json"; //返回数据类型,支持xml,json
        $params["SignatureMethod"]  = "HMAC-SHA1"; //固定参数
        $params["SignatureVersion"] = "1.0"; //固定参数
        $params["SignatureNonce"]   = uniqid(); //用于请求的防重放攻击，每次请求唯一
        date_default_timezone_set("GMT");//设置时区
        $params["Timestamp"]        = date('Y-m-d\TH:i:s\Z'); //格式为：yyyy-MM-dd’T’HH: mm: ss’Z’；时区为：GMT
        $params["Action"]           = 'SendSms'; //api命名 固定子
        $params["Version"]          = '2017-05-25'; //api版本 固定值
        $params["Signature"]        = $this->makeSignature($params); //最终生成的签名结果值
        $pieces = array();
        foreach ($params as $key => $value) {
            $pieces[] = $key . '=' . urlencode($value);
        }
        $url = self::API_URL . implode('&', $pieces);
        if(isset($this->debug)){
            return $this->debug?true:[];
        }
        $response = $this->remote($url);
        if($response['Code']=='OK'){
            return true;
        }else{
            return $response; 
        }
    }

    public function makeSignature($params)
    {
        ksort($params);
        $pieces = array();
        foreach ($params as $key => $value) {
            $pieces[] = $this->strEncode($key) . '=' . $this->strEncode($value);
        }
        $sign = 'GET&%2F&' . $this->strEncode(implode('&', $pieces));
        return base64_encode(hash_hmac('sha1', $sign, $this->AccessKeySecret . "&", true));
    }
    public function strEncode($str)
    {
        $res = urlencode($str);
        $res = preg_replace('/\+/', '%20', $res);
        $res = preg_replace('/\*/', '%2A', $res);
        $res = preg_replace('/%7E/', '~', $res);
        return $res;
    }
    public function remote($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);     
        empty($response) && $response = ['curl_error'=>curl_error($ch)];
        curl_close($ch);
        return json_decode($response,true);
    }
}
