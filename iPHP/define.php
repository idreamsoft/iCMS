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
defined('iPHP') OR define('iPHP', TRUE);
//----------------------------------------
defined('iPHP_KEY') OR define('iPHP_KEY','Jq4UDnkVkcywhv4BgfpcWemBAFKc5khQ');
defined('iPHP_CHARSET') OR define('iPHP_CHARSET','UTF-8');
//---------------cookie设置-------------------------
defined('iPHP_COOKIE_DOMAIN') OR define ('iPHP_COOKIE_DOMAIN','');
defined('iPHP_COOKIE_PATH') OR define ('iPHP_COOKIE_PATH','/');
defined('iPHP_COOKIE_PRE') OR define ('iPHP_COOKIE_PRE','iPHP_');
defined('iPHP_COOKIE_TIME') OR define ('iPHP_COOKIE_TIME','31536000');
defined('iPHP_AUTH_IP') OR define ('iPHP_AUTH_IP',true);
defined('iPHP_UAUTH_IP') OR define ('iPHP_UAUTH_IP',false);
//---------------时间设置------------------------
defined('iPHP_TIME_ZONE') OR define('iPHP_TIME_ZONE',"Asia/Shanghai");
defined('iPHP_DATE_FORMAT') OR define('iPHP_DATE_FORMAT','Y-m-d H:i:s');
defined('iPHP_TIME_CORRECT') OR define('iPHP_TIME_CORRECT',"0");
//---------------启用多站点设置------------------------
defined('iPHP_MULTI_SITE') OR define('iPHP_MULTI_SITE',false);
defined('iPHP_MULTI_DOMAIN') OR define('iPHP_MULTI_DOMAIN',false);
//---------------DEBUG------------------------
//defined('iPHP_DEBUG') OR define('iPHP_DEBUG',false);
//defined('iPHP_TPL_DEBUG') OR define('iPHP_TPL_DEBUG',false);
//defined('iPHP_URL_404') OR define('iPHP_URL_404','');
//-----------------框架相关路径-----------------------
define('iPHP_CORE',   __DIR__."/core");
define('iPHP_LIB',    __DIR__."/library");
define('iPHP_VENDOR', __DIR__."/vendor");
//-----------------应用根目录-----------------------
define('iPHP_PATH',strtr(realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR,'\\',DIRECTORY_SEPARATOR));
//-----------------应用相关路径-----------------------
define('iPHP_APP_DIR',    iPHP_PATH."app");
define('iPHP_APP_CORE',   iPHP_PATH."core");
define('iPHP_APP_VENDOR', iPHP_PATH."core/vendor");
define('iPHP_APP_LIB',    iPHP_PATH."core/library");
define('iPHP_APP_CACHE',  iPHP_PATH."cache");
define('iPHP_TPL_DIR',    iPHP_PATH."template");
define('iPHP_CONFIG_DIR', iPHP_PATH."config");
define('iPHP_TPL_CACHE',  iPHP_PATH."cache/template");
//composer 目录
define('iPHP_COMPOSER_DIR',iPHP_PATH."vendor");
//---------------系统设置------------------------
defined('iPHP_APP') OR define('iPHP_APP',"iPHP");//应用名
defined('iPHP_APP_INIT') OR define('iPHP_APP_INIT',true);//运行初始化
defined('iPHP_APP_DEFINE') OR define('iPHP_APP_DEFINE',null);//自定义运行应用 null/MY_
defined('iPHP_APP_MAIL') OR define('iPHP_APP_MAIL',"master@iiiphp.com");
defined('iPHP_MEMORY_LIMIT') OR define('iPHP_MEMORY_LIMIT', '128M');
//-----------------模板-----------------------
defined('iPHP_TPL_VAR') OR define('iPHP_TPL_VAR',iPHP_APP);
defined('iPHP_TPL_FUN') OR define('iPHP_TPL_FUN',iPHP_APP_DIR.'/func');
//-----------------其它-----------------------
defined('iPHP_GET_PREFIX') OR define('iPHP_GET_PREFIX', 'do_');
defined('iPHP_POST_PREFIX') OR define('iPHP_POST_PREFIX', 'ACTION_');
defined('iPHP_ERROR_HEADER') OR define('iPHP_ERROR_HEADER',true);
defined('iPHP_SHELL') OR define('iPHP_SHELL',PHP_SAPI=='cli'?true:false);
defined('iPHP_PROTOCOL') OR define('iPHP_PROTOCOL',iPHP_APP.'://');
defined('iPHP_CORE_CLASS') OR define('iPHP_CORE_CLASS',
    'File,Http,Request,'.
    'Cache,FileCache,'.
    'Utils,Etc,'.
    'Handler,Cookie,Vendor,'.
    'Adapter,Route,Hooks,'.
    'DB,Model,'.
    'View,TemplateLite,'.
    'Picture,Thumb,Gmagick,Captcha,'.
    'iDB,iJson,iString,iDefine,Pinyin,iQuery,'.
    'Script,Lang,'.
    'Pages,Paging,'.
    'Security,Waf'
);

defined('iPHP_CALLBACK_CONFIG') or define('iPHP_CALLBACK_CONFIG', null);
defined('iPHP_PAGEBREAK') or define('iPHP_PAGEBREAK', '#--'.iPHP_APP.'.PageBreak--#');
defined('iPHP_FILE_HEAD') or define('iPHP_FILE_HEAD', '<?php defined("iPHP") OR exit("What are you doing?");?>');
