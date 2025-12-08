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

class FormsData
{

    public static $primaryKey   = 'id';
    public static $prefix = 'forms_';
    public static $DATA = array();

    public static function init($data)
    {
        self::$DATA = is_numeric($data) ? Forms::get($data) : $data;
        $table = Forms::getTableName(self::$DATA['app']);
        return Content::model(self::$DATA, $table);
    }

    public static function delete($id = 0)
    {
        try {
            Content::delete($id);
            ContentDataModel::delete($id);
        } catch (\sException $ex) {
            //throw $th;
        }
        return true;
    }
    public static function get($id = 0, $dataId = 0, $userid = 0)
    {
        return Content::data($id);
    }
}
