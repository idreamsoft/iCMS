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

class ContentNodeAdmincp extends NodeAdmincp
{
    public $ACCESC_TITLE = '{app.name}栏目';

    public function __construct()
    {
        parent::__construct(Admincp::$APPID);
        $this->NODE_NAME = "栏目";
        $this->app       = Admincp::$APP_DATA['app'];
        $this->title     = Admincp::$APP_DATA['title'];
        $this->primary   = 'cid';

        $this->CONTENT_MODEL = ContentModel::setTable($this->app);

        if (Admincp::$APP_DATA['config']['nodeTable']) {
            NodeModel::setTable($this->app);
        }

        /**
         *  路由规则
         */
        $this->setRoute(array(
            'index'   => array(
                "label" => '首页',
                "template" => sprintf('%s/content.index.htm', View::TPL_FLAG_1),
                "rule" => '/{CDIR}/',
                "tips" => '{CID},{0xCID},{CDIR},{Hash@CID},{Hash@0xCID}'
            ),
            'list'    => array(
                "label" => '列表',
                "template" => sprintf('%s/content.list.htm', View::TPL_FLAG_1),
                "rule" => '/{CDIR}/index_{P}{EXT}',
                "tips" => '{CID},{0xCID},{CDIR},{Hash@CID},{Hash@0xCID}'
            ),
            $this->app => array(
                "label" => $this->title,
                "template" => sprintf('%s/content.htm', View::TPL_FLAG_1),
                "rule" => '/{CDIR}/{YYYY}/{MM}{DD}/{ID}{EXT}',
                "tips" => '{ID},{0xID},{LINK},{Hash@ID},{Hash@0xID}'
            ),
            'tag' => array(
                "label" => '标签',
                "template" => sprintf('%s/tag.htm', View::TPL_FLAG_1),
                "rule" => '/{CDIR}/t-{TKEY}{EXT}',
                "tips" => '{ID},{0xID},{TKEY},{NAME},{ZH_CN},{Hash@ID},{Hash@0xID}'
            ),
        ));
        /**
         *  URL规则选项
         */
        // $this->node_rule_list+= array(

        // );
    }
    // public function do_add(){
    //     $this->VIEW_DIR = $this->_app;
    //     parent::do_add();
    // }
}
