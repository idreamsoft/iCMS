<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class PublicApp
{
    public $methods = [
        'sitemapindex', 'sitemap', 'captcha', 'crontab', 'time', 'qrcode',
        'privacy', 'terms',
        'smscode', 'password'
    ];

    public function api_smscode()
    {
        $phone = Request::spost('phone');
        $captcha = Request::spost('captcha');
        $name = Request::spost('name');
        empty($captcha) && iJson::error('Captcha code is empty');
        try {
            Captcha::check($captcha, $name);
            // PluginSmsCode::$debug = iPHP_DEBUG;
            $sign = PluginSmsCode::send($phone);
            return [true, $sign];
        } catch (\sException $ex) {
            $msg = $ex->getMessage();
            iJson::error($msg);
        }
    }

    // public function api_tt()
    // {
    //     // echo Lang::get('captcha:error');
    //     echo Lang::get('empty:id');

    //     $row = PluginVerify::field('create_time')->where([
    //         'account'    => $account,
    //         'status'     => '1',
    //         'create_time'=> ['>=', '1697598107']
    //     ])->orderby('id DESC')->get();

    //     echo PluginVerify::getLastSql();
    //     var_dump($row);
    //     // exit;
    //     // iJson::error('user:not_found');
    //     // $verify = PluginVerify::generate('idreamsoft@qq.com');
    //     // var_dump($verify);
    //     // exit;
    // }

    public function api_emailcode()
    {
        $email = Request::post('email');
        $captcha = Request::post('captcha');
        $name = Request::spost('name');
        empty($captcha) && iJson::error('auth:enter_captcha_code');

        try {
            Captcha::check($captcha);
            $modifypwd_url = Route::routing('user/manage/modifypwd');
            $subject = Lang::get('plugin:email:verify:subject', ['{title}']);
            $body = Lang::get(
                'plugin:email:verify:body',
                ['{title}', '{code}', '{expire}', $modifypwd_url]
            );
            $result = PluginEmail::send([
                'address' => [$email, ''],
                'subject' => $subject,
                'body'    => $body
            ], true);
        } catch (\sException $ex) {
            $msg = $ex->getMessage();
            iJson::error($msg);
        }
    }

    public function api_template()
    {
        $paths = Request::post('paths');
        $app = Request::post('vapp');
        $params = Request::post('params');
        $paths[2] && User::isLogin();
        $paths = Security::safeStr($paths, '.');
        $tpl = sprintf('iCMS://%s/%s.htm', $paths[0], $paths[1]);
        View::$app = $app ?: $paths[0];
        $ciphertext = $params['ciphertext'];
        //有密文字段时,直接解密
        $ciphertext && $params = Crypt::decrypt($ciphertext);
        View::assign('ciphertext', $ciphertext);
        View::assign('params', $params);
        return ['html'=>View::fetch($tpl)];
    }

    public function api_privacy()
    {
        View::display('iCMS://user/privacy.htm');
    }

    public function api_terms()
    {
        View::display('iCMS://user/terms.htm');
    }

    public function api_sitemapindex()
    {
        header('Content-type:text/xml');
        View::display('/tools/sitemap.index.htm');
    }

    public function api_sitemap()
    {
        $cid = Request::sget('cid');
        $sdo = Request::sget('sdo');
        header('Content-type:text/xml');
        View::assign('cid', (int) $cid);
        if($sdo == 'baidu'){
            View::display('/tools/sitemap.baidu.xml');
        }
        View::display('/tools/sitemap.google.xml');
    }

    public function api_resetAdmin()
    {
        View::display('/tools/reset.admin.htm');
        // echo Admin::password('123456');
    }

    public function api_crontab()
    {
        exit();
    }

    public function api_captcha()
    {
        $name = Request::get('name');
        Captcha::get($name);
    }

    public function api_qrcode($url = null)
    {
        $url === null && $url = Request::get('url');
        echo PluginQRcode::make($url);
    }

    public static function qrcode_base64($text)
    {
        $image = PluginQRcode::make($text, true);
        return 'data:image/png;base64,' . base64_encode($image);
    }

    public static function qrcode_url($url)
    {
        $query = [
            'app' => 'public',
            'do'  => 'qrcode',
            'url' => $url
        ];
        isset($vars['cache']) && $query['cache'] = true;
        return Route::url('api', $query);
    }

    public static function url($app = null, $query = null)
    {
        if (empty($app)) {
            return iCMS_PUBLIC_URL;
        }

        $url = iCMS_API_URL . ($app ? $app : 'public');
        $query && $url = Route::make($query, $url);
        return $url;
    }

    public static function captcha($vars = [])
    {
        View::assign('captcha', $vars);
        return View::fetch('iCMS://public.captcha.htm');
    }
}
