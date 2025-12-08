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
 * 标签分类
 */
class TagCategoryAdmincp extends NodeAdmincp
{
    public function __construct()
    {
        parent::__construct(iCMS_APP_TAG);
        $this->app = 'tag';
        $this->title = '标签';
        $this->primary = 'tcid';

        $this->CONTENT_MODEL = new TagModel();
        $this->NODE_NAME = '分类';
        /**
         *  URL规则选项
         */
    }

    public function adding(&$rs = [])
    {
        $rs['status'] = '2';
    }
}
