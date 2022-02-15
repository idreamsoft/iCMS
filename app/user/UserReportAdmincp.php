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

class UserReportAdmincp extends AdmincpBase
{
    public $ACCESC_TITLE = '用户举报';

    public function __construct()
    {
        parent::__construct();
    }

    public function do_manage()
    {
        $reasonArray = UserReport::getReason();
        $keywords = Request::get('keywords');
        $keywords && $where['content'] = array('like', "%{$keywords}%");

        $orderby = self::setOrderBy(array(
            'id'    => "ID",
            'create_time' => "时间",
        ));

        $result = UserReportModel::where($where)
            ->orderBy($orderby)
            ->paging();
        include self::view("report.manage");
    }
}
