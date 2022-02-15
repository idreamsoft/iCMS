<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class IndexApp
{
    public function __construct()
    {
        Index::init();
    }
    public function do_iCMS()
    {
        return $this->display();
    }
    public function API_iCMS()
    {
        return $this->display();
    }
    public function display($a = [])
    {
        $config = Config::get('template.index');
        $name = $a[1] ?: $config['name'];
        $tpl  = $a[0] ?: $config['tpl'];

        $rule = '{PHP}';
        empty($name) && $name = 'index';
        if (View::$gateway == "html" || $config['rewrite']) {
            $rule = $name . Config::get('route.ext');
        }
        $route = (array) Route::get('index', array('rule' => $rule));
        $rule == '{PHP}' or Route::getPageUrl($route);
        if ($config['mode'] && iPHP_DEVICE == "desktop") {
            AppsApp::redirectToHtml($route);
        }
        View::setGlobal($route, 'iURL');
        $view = View::render($tpl, 'index');
        if ($view) return array($view, $route);
    }
}
