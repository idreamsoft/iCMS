<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class PublicFunc
{
    /**
     * const $APP = {}
     */
    public static $APP = [];

    public static function setApp($vars = [])
    {
        self::$APP = array_merge_recursive(self::$APP, $vars);
    }

    /**
     * <!--{iCMS:public:app output="json"}-->
     *
     * @param array $vars
     * @return void
     */
    public static function app($vars = [])
    {
        $generic = View::getGeneric();
        $default = [
            'init'       => true,
            'bootstrap'  => 'cms',
            'URL'        => iCMS_URL,
            'ASSETS'     => $generic['URLS']['assets'],
            'API_URL'    => $generic['API_URL'],
            'SCRIPT_NAME'=> $generic['API_URL'],
            'CSRF_TOKEN' => $generic['CSRF_TOKEN'],
            'lang'       => $generic['LANGUAGE'],
            'do'         => $generic['APP']['DO'],
            'name'       => $generic['APP']['NAME'],
            'file'       => [
                'url'=> iCMS::$config['file']['url']
            ],
            'modPath'      => $generic['APP']['MODPATH'],
            'appModPath'   => $generic['APP']['APPMODPATH'],
            'jspath'       => $generic['APP']['JSPATH'],
            'jspaths'      => $generic['APP']['JSPATHS'],
            'paths'        => $generic['paths'],
            'params'       => View::getParams(),
            'USER'         => User::data(),
            'debug'        => iPHP_DEBUG,
            'jsdebug'      => iPHP_JS_DEBUG,
            'gitTime'      => short_md5(iCMS_GIT_TIME),
            'release'      => short_md5(iCMS_RELEASE . date('Ym'))
        ];
        return array_merge($default, self::$APP);
    }

    public static function ui($vars = null)
    {
        View::assign('public_ui', $vars);
        isset($vars['template_dir']) && View::$template_dir = $vars['template_dir'];
        View::display('iCMS://public.ui.htm');
    }

    public static function css($vars = null)
    {
        View::display('iCMS://public.css.htm');
    }

    public static function js($vars = null)
    {
        View::display('iCMS://public.js.htm');
    }

    public static function captcha($vars = null)
    {
        $html = PublicApp::captcha($vars);
        return [true, $html];
    }

    public static function dialog($vars = null)
    {
        View::assign('public_dialog', $vars);
        View::display('iCMS://public.dialog.htm');
    }

    public static function crontab($vars = null)
    {
        $url = Route::create('api', 'app=public&do=crontab');
        $html = '<img src="' . $url . '" style="display: none;" />';
        if ($vars === true) {
            return $html;
        }
        return [true, $html];
    }

    public static function qrcode($vars = null)
    {
        if ($vars['base64']) {
            $html = PublicApp::qrcode_base64($vars['data']);
        } else {
            $html = PublicApp::qrcode_url($vars['data']);
        }
        return [true, $html];
    }
}
