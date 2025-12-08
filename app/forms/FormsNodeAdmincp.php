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
 * 表单分类
 */
class FormsNodeAdmincp extends NodeAdmincp
{
    public function __construct()
    {
        parent::__construct(iCMS_APP_FORMS);
        $this->app     = 'forms';
        $this->title   = '表单';
        $this->primary = 'node_id';

        $this->CONTENT_MODEL = new FormsModel();
        $this->NODE_NAME = "分类";
    }
    public function adding(&$rs = [])
    {
        $rs['status'] = '2';
    }
}
