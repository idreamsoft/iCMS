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
defined('iPHP') or define('iPHP', true);
define('iPHP_PATH', strtr(realpath(__DIR__) . DIRECTORY_SEPARATOR, '\\', '/'));
define('iPHP_CORE', __DIR__ . '/core');
define('iPHP_LIB', __DIR__ . '/library');
define('iPHP_VENDOR', __DIR__ . '/vendor');
define('iPHP_MAIL', 'support@iiiphp.com'); //支持邮箱
//-----------------应用根目录-----------------------
define('iPHP_APP_PATH', strtr(realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR, '\\', '/'));
define('iPHP_APP_DIR', iPHP_APP_PATH . 'app');
define('iPHP_APP_CORE', iPHP_APP_PATH . 'core');
define('iPHP_APP_VENDOR', iPHP_APP_PATH . 'core/vendor');
define('iPHP_APP_LIB', iPHP_APP_PATH . 'core/library');
define('iPHP_APP_CACHE', iPHP_APP_PATH . 'cache');
define('iPHP_PUBLIC', iPHP_APP_PATH . 'public');
define('iPHP_TEMPLATE_DIR', iPHP_APP_PATH . 'template');
define('iPHP_CONFIG_DIR', iPHP_APP_PATH . 'config');
define('iPHP_TEMPLATE_CACHE', iPHP_APP_PATH . 'cache/template');
define('iPHP_COMPOSER_DIR', iPHP_APP_PATH . 'vendor'); //composer 目录
define('iPHP_CACHE_DIR', iPHP_APP_PATH . 'cache');
define(
    'iPHP_CORE_CLASS',
    'File,Http,Request,' .
    'Cache,FileCache,' .
    'Utils,Etc,' .
    'Handler,Cookie,Session,Vendor,' .
    'Adapter,Route,Hooks,' .
    'DB,Model,ModelShard,' .
    'View,TemplateLite,' .
    'Picture,Thumb,Gmagick,Captcha,' .
    'iDB,iJson,iString,iDefine,Pinyin,iQuery,' .
    'Script,Lang,' .
    'Pages,Paging,' .
    'Crypt,Security,Waf'
);
define('iPHP_FILE_HEAD', '<?php defined("iPHP") OR exit("What are you doing?");?>');

require_once __DIR__ . '/defined.php';  //可定义常量
