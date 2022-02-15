<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilesFunc extends AppsFuncCommon
{
    public static function widget($vars = null)
    {
        echo FilesWidget::setData($vars['setData'])->
        picBtn($vars['picBtn'][0], $vars['picBtn'][1]);
    }
}
