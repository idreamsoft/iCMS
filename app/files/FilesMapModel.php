<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilesMapModel extends Model
{
    // public static function set($appid, $indexid, $value, $field = 'path')
    // {
    //     $name = File::filename($value);
    //     switch ($field) {
    //         case 'path':
    //         case 'name':
    //             $info     = Files::get('name', $filename, false, 'id');
    //             $fileid   = $info->id;
    //             break;
    //         case 'id':
    //             $fileid   = $value;
    //             break;
    //     }
    //     if ($fileid) {
    //         $userid  = Files::$userid;
    //         $addtime = time();
    //         $data    = compact('fileid', 'userid', 'appid', 'indexid', 'addtime');
    //         self::add($data);
    //     }
    // }
    // public static function add($data, $where = null)
    // {
    //     if ($where) {
    //         return self::update($data, $where);
    //     }
    //     return self::create($data, true);
    // }
    // public static function setIndexid($content, $indexid, $appid)
    // {
    //     if (empty($content)) return;

    //     is_array($content) && $content = implode('', $content);
    //     $content = stripslashes($content);
    //     $array   = FilesPic::findImg($content, $match);
    //     foreach ($array as $key => $value) {
    //         if (stripos($value, iCMS_FS_HOST) === false) {
    //             continue;
    //         }
    //         self::set($appid, $indexid, $value, 'path');
    //     }
    // }
}
