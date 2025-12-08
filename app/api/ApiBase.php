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

class ApiBase
{
    public static function getUser($text)
    {
        $text = rawurldecode($text);
        $data = null;
        if ($text) {
            $user = User::deAuth($text);
            if ($user) {
                $data = UserModel::where([
                    'account'  => $user['account'],
                    'password' => $user['password'],
                    'status'   => '1'
                ])->get();
            }
        }
        return $data;
    }
}
