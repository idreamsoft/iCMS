<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilesMark
{
    public static $enable = true;
    public static $config = null;

    public static function run($fp, $ext = null)
    {
        if (!self::$enable) return;

        self::$config =  Config::get('watermark');;

        if (!self::$config['enable']) return;

        $allow_ext = FilesPic::$EXTS;
        self::$config['allow_ext'] && $allow_ext = explode(',', self::$config['allow_ext']);
        $ext or $ext = File::getExt($fp);
        if (in_array($ext, $allow_ext)) {
            Picture::init(self::$config);
            if (self::$config['mode']) {
                return Picture::mosaics($fp);
            } else {
                return Picture::watermark($fp);
            }
        }
    }
}
