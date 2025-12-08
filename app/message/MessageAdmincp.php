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
    use AdmincpCommonTrait;
    public function do_index()
    {
        if (Request::isAjax()) {
            return $this->index();
        }
        self::setContext([
            "params" => [
                'type' => Request::get('type')
            ],
            "tablist" => 'params'
        ]);
        include self::view();
    }
    public function do_inbox()
    {
        include self::view();
    }
    public function index()
    {
        $where = MessageModel::getData();
        $keywords = Request::get('keywords');
        $keywords && $where['CONCAT(username,title)'] = array('REGEXP', $keywords);
        return  self::tableData(['where' => $where], function ($rows) {
            // $rows = array_map(function ($row) {
            //     return $row;
            // }, $rows);
            return $rows;
        });
    }
}
