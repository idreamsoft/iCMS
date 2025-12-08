<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
*
* @author icmsdev <master@icmsdev.com>
* @site https://www.icmsdev.com
* @licence https://www.icmsdev.com/LICENSE.html
*/
try {
    require dirname(__DIR__) . '/iCMS.php';
    IndexBoot::init();
} catch (Exception $ex) {
    IndexBoot::exception($ex);
}