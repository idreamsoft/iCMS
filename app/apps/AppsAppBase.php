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

abstract class AppsAppBase
{
    public $config            = array();
    public static $appConfig  = array();
    public static $appId      = 0;
    public static $POST       = array();
    public static $GET        = array();

    public function __construct($appid = null)
    {
        self::$appId     = $appid ?: iApp::$ID;
        self::$appConfig = Config::get(iApp::$NAME);
        self::$POST      = Request::post();
        self::$GET       = Request::get();
    
        $this->id        = (int)Request::get('id');
        $this->config    = self::$appConfig;
    }

    public static function error($msg, $state = 'error')
    {
        return Admincp::exception($msg, $state);
    }
    /**
     * [alert description]
     *
     * @return iJson::error
     */
    public static function alert($msg)
    {
        return Admincp::exception($msg, 'alert');
    }
    /**
     * [success description]
     *
     * @return iJson::success
     */
    public static function success()
    {
        $args = func_get_args();
        if (Request::param('frame') || Request::post() || Request::file()) {
            iJson::$jsonp = 'AdmSuccess';
            if (Request::param('modal')) {
                iJson::$jsonp = true;
            }
        }
        if (Request::isAjax()) {
            iJson::$jsonp = false;
        }
        DB::commit();
        return call_user_func_array(array('iJson', 'success'), $args);
    }

}
