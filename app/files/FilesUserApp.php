<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilesUserApp extends UserContentApp
{
    public function API_add()
    {
        View::display('iCMS://user/file/add.htm');
    }

    public function API_browse()
    {
        View::display('iCMS://user/file/browse.htm');
    }

    public function API_preview()
    {
        Request::get('pic') && $src = FilesClient::getUrl(Request::get('pic'));
        View::assign('src', $src);
        View::display('iCMS://user/file/preview.htm');
    }

    public function api_avatar()
    {
        UserUtils::avatar(User::$id);
        return ['avatar'=>User::route('avatar')];
        // return ['avatar'=>User::route('avatar'), 'url'=>'reload'];
    }

    public function ACTION_upload()
    {
        $app = new FilesAdmincp();
        $app->done_upload();
    }
}
