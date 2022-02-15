<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class UserWidget
{
    public static function atUserList($content)
    {
        return self::at($content);
    }
    public static function atContent($content)
    {
        return self::at($content, false);
    }
    public static function at($content, $user = true)
    {
        preg_match_all('/@(.+?[^@])\s/is', str_replace('@', "\n@", $content), $matches);
        $user_list = array_unique($matches[1]);
        if ($user_list) {
            foreach ($user_list as $key => $nk) {
                $nickname[] = $nk;
                if (!$user) {
                    $search[$nk]  = '@' . $nk;
                    $replace[$nk] = '@' . $nk;
                }
            }
            $users = UserModel::field('uid,nickname')->where('nickname', $nickname)->select();
            if ($users) foreach ($users as $key => $value) {
                if (!$user) {
                    $info = User::info($value['uid'], $value['nickname']);
                    $replace[$value['nickname']] = $info['at'];
                } else {
                    $remindUser[$value['uid']] = $value['nickname'];
                }
            }
            !$user && $content = str_replace($search, $replace, $content);
        }
        return $user ? $remindUser : $content;
    }
}
