<?php
/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
defined('iPHP_KEY') or define('iPHP_KEY', 'Jq4UDnkVkcywhv4BgfpcWemBAFKc5khQ');
//---------------cookie设置-------------------------
defined('iPHP_COOKIE_DOMAIN') or define('iPHP_COOKIE_DOMAIN', '');
defined('iPHP_COOKIE_PATH') or define('iPHP_COOKIE_PATH', '/');
defined('iPHP_COOKIE_PRE') or define('iPHP_COOKIE_PRE', 'iPHP_');
defined('iPHP_COOKIE_TIME') or define('iPHP_COOKIE_TIME', '31536000');
defined('iPHP_ADMINCP_IP') or define('iPHP_ADMINCP_IP', true);
defined('iPHP_ADMINCP_UA') or define('iPHP_ADMINCP_UA', true);
defined('iPHP_USERCP_IP') or define('iPHP_USERCP_IP', true);
//---------------时间设置------------------------
defined('iPHP_TIME_ZONE') or define('iPHP_TIME_ZONE', 'Asia/Shanghai');
defined('iPHP_DATE_FORMAT') or define('iPHP_DATE_FORMAT', 'Y-m-d H:i:s');
defined('iPHP_TIME_CORRECT') or define('iPHP_TIME_CORRECT', '0');
//---------------启用多站点设置------------------------
defined('iPHP_MULTI_SITE') or define('iPHP_MULTI_SITE', false);
defined('iPHP_MULTI_DOMAIN') or define('iPHP_MULTI_DOMAIN', false);
//---------------DEBUG------------------------
// defined('iPHP_DEBUG') OR define('iPHP_DEBUG',true);
//defined('iPHP_TEMPLATE_DEBUG') OR define('iPHP_TEMPLATE_DEBUG',false);
//---------------系统设置------------------------
defined('iPHP_APP') or define('iPHP_APP', 'iPHP'); //应用名
defined('iPHP_APP_INIT') or define('iPHP_APP_INIT', true); //运行初始化
defined('iPHP_APP_MAIL') or define('iPHP_APP_MAIL', 'master@icmsdev.com');
defined('iPHP_MEMORY_LIMIT') or define('iPHP_MEMORY_LIMIT', '128M');
//-----------------模板-----------------------
defined('iPHP_TEMPLATE_VAR') or define('iPHP_TEMPLATE_VAR', iPHP_APP);
defined('iPHP_TEMPLATE_FUN') or define('iPHP_TEMPLATE_FUN', iPHP_APP_DIR . '/func');
//-----------------请求方式-----------------------
defined('iPHP_GET_PREFIX') or define('iPHP_GET_PREFIX', 'do_');
defined('iPHP_API_PREFIX') or define('iPHP_API_PREFIX', 'api_');
defined('iPHP_POST_PREFIX') or define('iPHP_POST_PREFIX', 'done_');
defined('iPHP_DELETE_PREFIX') or define('iPHP_DELETE_PREFIX', 'delete_');
defined('iPHP_PUT_PREFIX') or define('iPHP_PUT_PREFIX', 'put_');
defined('iPHP_HEAD_PREFIX') or define('iPHP_HEAD_PREFIX', 'head_');
defined('iPHP_OPTIONS_PREFIX') or define('iPHP_OPTIONS_PREFIX', 'options_');
defined('iPHP_TRACE_PREFIX') or define('iPHP_TRACE_PREFIX', 'trace_');
defined('iPHP_DATA_PREFIX') or define('iPHP_DATA_PREFIX', 'data_');
defined('iPHP_SHELL_PREFIX') or define('iPHP_SHELL_PREFIX', 'shell_');
//-----------------其它-----------------------
defined('iPHP_ERROR_HEADER') or define('iPHP_ERROR_HEADER', true);
defined('iPHP_SHELL') or define('iPHP_SHELL', PHP_SAPI == 'cli' ? true : false);
defined('iPHP_PROTOCOL') or define('iPHP_PROTOCOL', iPHP_APP . '://');
defined('iPHP_PAGEBREAK') or define('iPHP_PAGEBREAK', '#--' . iPHP_APP . '.PageBreak--#');
defined('APP_ASSETS_NAME') or define('APP_ASSETS_NAME', 'assets');
defined('iPHP_ACCESS_LOG') or define('iPHP_ACCESS_LOG', true);
