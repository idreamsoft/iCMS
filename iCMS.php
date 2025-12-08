<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
define('iPHP', true);
define('iPHP_APP', 'iCMS'); //应用名
define('iPHP_APP_MAIL', 'support@icmsdev.com'); //支持邮箱

require_once __DIR__ . '/config/define.php'; //定义常量
require_once __DIR__ . '/config/secretkey.php'; //密钥
require_once __DIR__ . '/config/git.php'; //GIT版本
require_once __DIR__ . '/config/version.php'; //iCMS版本
require_once __DIR__ . '/iPHP/bootstrap.php'; //iPHP框架
require_once __DIR__ . '/core/iCMS.php'; //iCMS
require_once __DIR__ . '/core/function.php'; //常用函数
iCMS::bootstrap();
