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
 * Vditor编辑器
 *
 */
class EditorVditorAdmincp extends AdmincpBase
{
    /**
     * 编辑器抓取远程图片
     *
     * @return void
     */
    public function done_fetch()
    {
        $input = Request::input();
        $url = FilesClient::remote($input['url']);
        return ['data'=> [
            'originalURL' => $input['url'],
            'url'         => $url,
        ]];
    }

    /**
     * 编辑器上传图片
     *
     * @return void
     */
    public function done_upload()
    {
        $data = FilesClient::upload('upfile');
        return  compact('data');
    }
    // public function do_editor(){
    //     include self::view();
    // }
}
