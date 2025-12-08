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
 * 用户财务记录
 */
class UserOrderAdmincp extends AdmincpCommon
{
    public static $orderBy = [
        'id'          => 'ID',
        'create_time' => '时间',
    ];

    public function do_detail()
    {
        $rs = UserOrderModel::get($this->id);
        $fields = UserOrderModel::fullFields();
        include self::view();
    }

    public function do_index()
    {
        if (Request::isAjax()) {
            return $this->index();
        }
        include self::view();
    }

    public function done_delete()
    {
        self::alert('用户财务记录禁止删除！');
    }

    public function index()
    {
        $keywords = Request::get('keywords');
        $keywords && $where['content'] = ['like', "%{$keywords}%"];
        return  self::tableData(['where' => $where], function ($rows) {
            $reason = UserReport::getReason();
            $rows = array_map(function ($row) use ($reason) {
                $row['reason_text'] = $reason[$row['reason']];
                return $row;
            }, $rows);
            return $rows;
        });
    }
}
