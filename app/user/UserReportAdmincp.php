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
 * 用户举报
 */
class UserReportAdmincp extends AdmincpCommon
{
    public static $orderBy = [
        'id'    => "ID",
        'create_time' => "时间",
    ];
    public function do_detail()
    {
        $rs = UserReportModel::get($this->id);
        $reason = UserReport::getReason();
        $rs['reason_text'] = $reason[$rs['reason']];
        $fields = UserReportModel::fullFields();
        include self::view();
    }
    public function do_index()
    {
        if (Request::isAjax()) {
            return $this->index();
        }
        include self::view();
    }
    public function index()
    {
        $keywords = Request::get('keywords');
        $keywords && $where['content'] = array('like', "%{$keywords}%");
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
