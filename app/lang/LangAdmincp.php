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
/**
 * 语言包
 * 
 * @login no
 */
class LangAdmincp extends AdmincpBase
{
    public function __construct()
    {
        $name = Request::get('name');
        $name && Lang::load($name);
        $lang = (array)$this->__lang();
        Lang::merge($lang);
        Files::cacheControl();
        exit(sprintf('define(%s)', cnjson_encode($lang)));
    }

    /**
     * @login no
     */
    public function do_index()
    {
    }

    protected function __lang()
    {
        return [];
    }
}
