<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
*
* @author icmsdev <master@icmsdev.com>
* @site https://www.icmsdev.com
* @licence https://www.icmsdev.com/LICENSE.html
*/
define('iPHP_DEBUG', true);
define('iPHP_ERROR_HEADER', false);
define('iPHP_DEVICE_REDIRECT', false);
define('iPHP_WAF_SKIP_POST', true);//后台跳过POST

require dirname(__FILE__) . '/iCMS.php';
DB::debug(2);
Admincp::run();
