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

class NodePagesAdmincp extends NodeAdmincp
{
    public function __construct()
    {
        parent::__construct(iCMS_APP_NODE);
        $this->app = 'node';
        $this->title = '单页';
        $this->NODE_NAME = '页面';
        $this->VIEW_DIR  = 'node/pages';
        $this->ROUTE  = array(
            'index' => array(
                'label' => '页面',
                'template' => '{iTPL}/node.pages.htm',
                'rule' => '{PAGES}',
                'tips' => false,
                'info' => '',
            )
        );
    }

    public function adding(&$rs = [])
    {
        $rs['mode'] = '2';
        $rs['template']['index'] = '{iTPL}/node.pages.htm';
        $rs['rule']['index'] = '{PAGES}';
        $rs['status'] = '1';
    }
}
