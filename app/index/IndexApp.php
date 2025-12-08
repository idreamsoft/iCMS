<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class IndexApp extends AppsBase
{
    const RULE = 'index{EXT}';
    const PAGE = 'index_{P}{EXT}'; //只能设置

    public function do_index()
    {
        $tpl = Adapter::$device['index'];
        if (strpos($tpl, '::') !== false) {
            return iPHP::invoke($tpl);
        }
        $app = 'index';
        $rules = $this->rules();
        $route = (array) Route::get($app, $rules);
        $rules['rule'] == '{PHP}' or Route::setPageUrl($route);
        if (self::$CONFIG['mode']) {
            self::redirectToHtml($route);
        }
        return View::render($tpl, $app, $route);
    }

    public static function page()
    {
        $path = IndexBoot::$PATH;
        $ext = iCMS::$config['route']['ext'];
        $ext = preg_quote($ext, '.');
        $pattern = str_replace(['{P}', '{EXT}'], ['(\d+)', $ext], self::PAGE);
        $needle  = str_replace(['{P}', '{EXT}'], ['', ''], self::PAGE);
        if (stripos($path, '/' . $needle) === 0) {
            if (preg_match("/$pattern/", $path, $matches)) {
                if ($matches[1]) {
                    $_GET['page'] = (int)$matches[1];
                }
            }
        }
        return $needle;
    }

    public function rules()
    {
        // $file = Adapter::$device['file'] ?: 'index';
        $file = 'index';
        $rules = ['rule'=>'{PHP}'];
        if (View::$gateway == 'html' || self::$CONFIG['rewrite']) {
            $rules = ['rule'=> self::RULE, 'page'=> self::PAGE];
            self::page();
        }
        return $rules;
    }
}
