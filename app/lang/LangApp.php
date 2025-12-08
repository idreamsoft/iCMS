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

class LangApp
{
    public function __construct()
    {
        $name = Request::get('name');
        $out = Request::get('out');
        $name && Lang::load($name,'usercp');
        $lang = (array)$this->__lang();
        Lang::merge($lang);
        Files::cacheControl();
        if($out=='json'){
            exit(cnjson_encode($lang));
        }
        exit(sprintf('define(%s)', cnjson_encode($lang)));
    }

    protected function __lang()
    {
        return [];
    }
}
