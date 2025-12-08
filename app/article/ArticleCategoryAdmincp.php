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
 * 文章栏目
 */
class ArticleCategoryAdmincp extends NodeAdmincp
{
    public function __construct()
    {
        parent::__construct(iCMS_APP_ARTICLE);
        $this->app     = 'article';
        $this->title   = '文章';
        $this->primary = 'cid';

        $this->CONTENT_MODEL  = new ArticleModel();
        $this->NODE_NAME  = "栏目";
  
        /**
         *  URL规则选项
         */
        // $this->setRule();
    }
}
