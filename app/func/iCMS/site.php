<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
// use iPHP\core\Adapter;
// use iPHP\core\View;

function iCMS_site($vars = [])
{
    $site = Config::get('site');
    $site['meta'] = is_array($site['meta']) ? array_filter($site['meta']) : [];

    $site['title'] = $site['name'];
    $site['404'] = iCMS_URL_404;
    $site['url'] = iCMS_URL;
    $site['host'] = iCMS_URL_HOST;
    $site['tpl'] = View::$template_dir;
    $site['page'] = isset($_GET['p']) ? (int)$_GET['p'] : (int)$_GET['page'];
    $site['urls'] = Config::get('URLS');
    $site['iurl'] = Adapter::site();
    Route::getUrls($site);

    if (isset($vars['return'])) {
        return $site;
    }
    $key = $vars['key'] ?: 'site';
    View::assign($key, $site);
}
