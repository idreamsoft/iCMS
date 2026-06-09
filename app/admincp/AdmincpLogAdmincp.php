<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
/**
 * 操作记录
 * 
 * @role superadmin
 */
class AdmincpLogAdmincp extends AdmincpCommon
{
    /**
     * 查看后台操作记录
     *
     * @role superadmin
     * @return void
     */
    public function do_detail()
    {
        $rs = AdmincpLogModel::get($this->id);
        $fields = AdmincpLogModel::fullFields();
        include self::view();
    }

    /**
     * 操作记录管理
     * @role superadmin
     * @return void
     */
    public function do_index()
    {
        $keywords = Request::get('keywords');
        $keywords && $where[] = [
            'CONCAT(username,app,uri,useragent,ip,method,referer)',
            'REGEXP',
            $keywords
        ];
        $uid = Request::get('uid');
        $uid && $where[] = ['uid', $uid];

        $sapp = Request::get('sapp');
        $sapp && $where[] = ['app', $sapp];

        $ip = Request::get('ip');
        $ip && $where[] = ['ip', $ip];

        if (Request::isAjax()) {
            return self::tableData('AdmincpLogModel', [
                'where' => $where
            ], function ($rows) {
                $rows = array_map(function ($row) {
                    return $row;
                }, $rows);
                return $rows;
            });
        }

        include self::view();
    }

    public static function create()
    {
        //默认开启
        if (!iPHP_ACCESS_LOG) {
            return;
        }

        if (Admincp::$APP_DO == 'assets') {
            return;
        }
        $data = [
            'uid'           => Admin::$id,
            'username'      => Admin::$nickname,
            'app'           => Admincp::$APP_NAME,
            'ip'            => Request::ip(),
            'uri'           => Request::server('REQUEST_URI'),
            'useragent'     => Request::server('HTTP_USER_AGENT'),
            'method'        => Request::server('REQUEST_METHOD'),
            'referer'       => Request::server('HTTP_REFERER'),
            'create_time'   => Request::server('REQUEST_TIME'),
        ];
        AdmincpLogModel::create($data);
    }
}
