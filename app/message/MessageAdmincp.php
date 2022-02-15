<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class MessageAdmincp extends AdmincpCommon
{
    public function __construct()
    {
        parent::__construct();
    }
    public function do_manage($appid = 0)
    {
        $appid = (int)Request::get('appid');
        $iid = (int)Request::get('iid');
        $userid = (int)Request::get('userid');
        $ip = Request::get('ip');

        $appid && $where['appid'] = $appid;
        $iid && $where['iid'] = $iid;
        $userid && $where['userid'] = $userid;
        $ip && $where['ip'] = $ip;

        $status = Request::get('status');
        is_numeric($status) && $where['status'] = $status;

        if ($cid = (int)Request::get('cid')) {
            Node::makeWhere($where,$cid);
        }

        $keywords = Request::get('keywords');
        $keywords && $where['CONCAT(username,title)'] = array('REGEXP', $keywords);

        $orderby = self::setOrderBy();
        $result = MessageModel::where($where)
            ->orderBy($orderby)
            ->paging();

        Node::$APPID = $appid;

        include self::view("manage");
    }
}
