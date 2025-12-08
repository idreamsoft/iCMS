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

class UserUtils
{
    public static $forward = null;

    public static function avatar($id = null)
    {
        empty($id) && $id = (int)Request::get('id');
        FilesMark::$enable = false;
        Files::$check_data = false;
        FilesCloud::$enable = false;
        FilesClient::$config['allow_ext'] = 'jpg,png,jpeg';
        $path = User::getPic($id);
        $udir = dirname($path);
        return FilesClient::upload('upfile', $udir, $id, 'jpg');
    }
    public static function forward($flag = null)
    {
        self::$forward = Request::param('forward');
        if (empty(self::$forward)) {
            self::$forward = Cookie::get('forward');
            if (empty(self::$forward)) {
                self::$forward = $_SERVER['HTTP_REFERER'];
                self::$forward or self::$forward = iCMS_URL;
            }
            if (strpos(self::$forward, 'forward=') !== false) {
                $query = parse_url(self::$forward, PHP_URL_QUERY);
                parse_str($query, $qs);
                $qs['forward'] && self::$forward = $qs['forward'];
            }
            $flag === 'c' && Cookie::set('forward', self::$forward);
            if ($flag === 'r' && User::$config['forward']) {
                $url = Route::make('forward=' . self::$forward);
                Utils::jumpto($url);
            }
        }
        View::assign('forward', self::$forward);
        return self::$forward;
    }
}
