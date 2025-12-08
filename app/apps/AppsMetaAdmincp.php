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

class AppsMetaAdmincp extends AdmincpBase
{
    /**
     * 保存自定义数据
     *
     * @return void
     */
    public function done_saveKv()
    {
        $appid = Request::spost('appid');
        $id = Request::spost('id');
        empty($id) && self::alert('自定义数据,项目ID不能为空');
        AppsMeta::saveData($appid, $id);
        return true;
    }

    /**
     * 获取自定义数据
     *
     * @return void
     */
    public function done_getKv()
    {
        $appid = Request::spost('appid');
        $id = Request::spost('id');
        $data = AppsMeta::get($appid, $id);
        return ['data'=>$data ? array_values($data) : []];
    }
}
