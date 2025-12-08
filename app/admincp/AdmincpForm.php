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

class AdmincpForm
{
    public $view = null;

    public function __construct($view = null)
    {
        $this->view = $view;
    }

    public static function tableSearch($html = null)
    {
        include AdmincpView::view('widget/table/block', 'admincp');
    }

    public static function tableSearchDefault($html = null)
    {
        $EXTENDS = AdmincpView::getExtends('widget/table', Admincp::$APP, false);
        include AdmincpView::view('widget/table/default', 'admincp');
    }

    public static function submit($text='Submit')
    {
        include AdmincpView::view('widget/form.submit', 'admincp');
    }

    public static function foot()
    {
        include AdmincpView::view('widget/form.footer', 'admincp');
    }
}
