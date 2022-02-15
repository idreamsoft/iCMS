<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class FormsPackage
{
    public static $LocalFormat    = "%s%s.FORMS.%s-v%s.%s";
    public static $LocalDataFile  = "iCMS.FORMS.DATA.php";
    public static $LocalTableFile = "iCMS.FORMS.TABLE.php";

    public static function install()
    {
        $app = AppsStore::$DATA['application'];
        $files = AppsStore::unzip();
        $files && AppsStore::setup_data($files, '表单', self::$LocalDataFile, 'FormsModel');  //安装应用数据
        $files && AppsStore::setup_table($files, self::$LocalTableFile); //创建应用表

        AppsStore::$IS_TEST && AppsStore::$MESSAGES['TEST']['rm'] = AppsStore::$PKG_PATH;
        AppsStore::$IS_TEST or File::rm(AppsStore::$PKG_PATH);
        AppsStore::$MESSAGES[] = '表单安装完成';
        return true;
    }
}
