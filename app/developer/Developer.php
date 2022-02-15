<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
defined('iPHP') or exit('What are you doing?');

class Developer
{
    const APP = 'developer';
    const APPID = iCMS_APP_DEVELOPER;
    const CONFIG_NAME =  'icmsdev';

    public static function replace(&$html)
    {
        $array = [
            '{ADMINCP_URL}'   => ADMINCP_URL,
            '{DEVELOPER_URL}' => ADMINCP_URL . '=developer',
            '{APP_URL}'       => APP_URL,
            '{APP_DOURL}'     => APP_DOURL,
            '__PAGEBASE__?'   => Route::make(['page' => null]) . '&',
            '__PAGEBASE__'    => Route::make(['page' => null]),
            '{BASEURL}'       => Route::make(['pageSize' => null, 'orderBy' => null])
        ];

        $html = str_replace(array_keys($array), array_values($array), $html);
    }
    public static function post($url, $args = null)
    {
        $param = self::param($args);
        // $url  = Route::make($param, $url);
        iDebug::$DATA[__METHOD__]['url'] = $url;
        iDebug::$DATA[__METHOD__]['param'] = $param;
        $response = Http::remote($url, $param, 'raw');
        return $response;
    }
    public static function get($url, $args = null)
    {
        $param = self::param($args);
        $url  = Route::make($param, $url);
        iDebug::$DATA[__METHOD__]['url'] = $url;
        iDebug::$DATA[__METHOD__]['param'] = $param;
        $response = Http::remote($url);
        self::replace($response);
        return $response;
    }

    public static function setConfig($data)
    {
        Config::vset($data, self::CONFIG_NAME);
    }
    public static function getConfig()
    {
        return Config::vget(self::CONFIG_NAME);
    }
    public static function systemInfo()
    {
        include AdmincpBase::view("info", 'developer');
    }
    public static function param($args = null)
    {
        Http::$CURL_HTTP_CODE = [200, 500];

        $config = self::getConfig();

        Http::$CURLOPT_TIMEOUT        = 60;
        Http::$CURLOPT_CONNECTTIMEOUT = 10;
        Http::$CURLOPT_REFERER        = $_SERVER['HTTP_REFERER'];
        Http::$CURLOPT_USERAGENT      = $_SERVER['HTTP_USER_AGENT'];
        Http::$CURLOPT_HTTPHEADER     = [
            'AUTHORIZATION: ' . $config['auth']
        ];
        $param  = array(
            'iCMS_VERSION'    => iCMS_VERSION,
            'iCMS_RELEASE'    => iCMS_RELEASE,
            'iCMS_HASH'       => iCMS_HASH,
            'iCMS_GIT_COMMIT' => iCMS_GIT_COMMIT,
            'iCMS_GIT_TIME'   => iCMS_GIT_TIME,
            'iCMS_HOST'       => $_SERVER['HTTP_HOST'],
            'account'         => $config['account'],
        );
        ksort($param);
        $param['iCMS_SIGN'] = md5(http_build_query($param) . $config['auth']);
        $param['captchaToken'] = Cookie::get('captchaToken');
        is_array($args) && $param = array_merge($param, $args);
        return $param;
    }
}
