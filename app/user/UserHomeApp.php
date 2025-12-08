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

class UserHomeApp extends UserCenter
{
    public function display($name = 'index')
    {
        if (empty(User::$id) && empty($_GET['id'])) {
            $url = User::getLoginUrl();
            Utils::jumpto($url);
        }
        UserCenter::assign();
        $node = [];
        if ($id = (int)Request::get('cid')) {
            $node = UserNodeModel::where(compact('id', 'appid'))->get();
            View::append('user', compact('node'), true);
        }
        View::display('iCMS://user/home.' . $name . '.htm');
    }

    public function api_home()
    {
        $this->display();
    }

    public function api_fans()
    {
        $this->display('fans');
    }

    public function api_follower()
    {
        $this->display('follower');
    }

    public function api_favorite()
    {
        $this->display('favorite');
    }
}
