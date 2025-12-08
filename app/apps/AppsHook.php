<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class AppsHook
{
    /**
     * 获取前端应用对应的JS
     * /assets/index/js/index.js
     * /assets/js/app/index.js
     *
     * @param array $names
     * @param array $paths
     * @return string
     */
    public static function getJsPath($names, &$paths = [])
    {
        list($n, $s) = $names;
        if ($s) {
            $paths[] = sprintf('%s/js/%s', $s, $s);
            $paths[] = sprintf('%s/js/%s', $n, $s);
            $paths[] = sprintf('%s/js/%s', $n, $n);
            $paths[] = sprintf('js/app/%s', $s);
        } else {
            $paths[] = sprintf('%s/js/%s', $n, $n);
        }
        $paths[] = sprintf('js/app/%s', $n);

        foreach ($paths as $key => $value) {
            $jsrpath = iPHP_PUBLIC . '/assets/' . $value . '.js';
            if (is_file($jsrpath)) {
                return $value;
            }
        }
    }

    public static function init()
    {
        $APPID = [];
        foreach ((array)iCMS::$config['apps'] as $_app => $_appid) {
            $APPID[strtoupper($_app)] = $_appid;
        }
        // $dir           = trim(Config::get('route.dir'),'/');
        // $template_url  = iCMS_URL.'/'.($dir?$dir.'/':'').'template';
        $base_url = rtrim(iCMS_URL, '/');
        // if (Adapter::$device['agent'] == 'DIR') {
        //     $base_url = rtrim(Adapter::$main_url, '/');
        // }
        // $template_url = $base_url . '/template';
        $assets = iCMS_PUBLIC_URL . '/assets';
        $URLS = [
            // 'template' => $template_url,
            'tpl'      => $assets . '/' . View::$template_dir,
            'public'   => iCMS_PUBLIC_URL,
            'user'     => iCMS_USER_URL,
            'res'      => iCMS_FILE_URL,
            // 'app'      => $base_url . '/app',
            'assets'   => iCMS_PUBLIC_URL . '/assets',
            // 'ui'       => iCMS_PUBLIC_URL . '/ui',
            'avatar'   => iCMS_FILE_URL . 'avatar/',
        ];

        View::generic([
            'v' => short_md5(iCMS_GIT_TIME),
            'VERSION' => iCMS_VERSION,
            'API'     => iCMS_API_NAME,
            'API_URL' => iCMS_API_URL,
            'DEVICE'  => iPHP_DEVICE,
            'CONFIG'  => iCMS::$config,
            'URLS'    => $URLS,
            'APPID'   => $APPID
        ]);
        iCMS::$config['URLS'] = $URLS;
        View::call_func_system('site', true);
        View::assign('_GET', Request::get());
        View::assign('_POST', Request::post());
        View::assign('_REQUEST', Request::param());
    }

    public static function begin()
    {
        $JSPATHS = [];
        $jspath = self::getJsPath([Application::$NAME, Application::$SNAME], $JSPATHS);
        $jspath or $JSPATHS[] = $jspath = 'app@undefined';
        $lang = Lang::getLang();
        $vars = [
            'MOBILE'     => iPHP_MOBILE,
            'COOKIE_PRE' => iPHP_COOKIE_PRE,
            'CSRF_TOKEN' => Security::csrf_token(Request::ip()),
            'REFERER'    => iPHP_REFERER,
            'LANGUAGE'   => $lang,
            'LANGUAGE_TEXT'   => Lang::$languages[$lang][0],
            'ADAPTER'    => Adapter::$device,
            'APP'        => [
                'MODPATH'     => 'js/app/{{module}}',
                'APPMODPATH'  => '{{module}}/js/{{module}}',
                'JSPATH'      => $jspath,
                'JSPATHS'     => $JSPATHS,
                'NAME'        => Application::$NAME,
                'DO'          => Application::$DO,
                'METHOD'      => Application::$METHOD,
            ],
        ];
        View::generic($vars);
    }
}
