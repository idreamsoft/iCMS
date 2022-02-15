<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class EditorAdmincp extends AdmincpBase
{
    public $noMethod = true;
    public function __construct()
    {
        parent::__construct();
        $method = Admincp::$APP_DO;
        $editor = new EditorApi;
        if (method_exists($editor, $method)) {
            return $editor->$method();
        }
    }
}
