<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class EditorVditorApp
{
    public function api_fetch()
    {
        $input = Request::input();
        $url = FilesClient::remote($input['url']);
        return [
            'originalURL' => $input['url'],
            'url'         => $url,
        ];
    }

    public function api_upload()
    {
        $data = FilesClient::upload('upfile');
        return  compact('data');
    }
}
