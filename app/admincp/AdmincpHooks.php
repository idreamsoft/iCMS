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

class AdmincpHooks
{
    /**
     * 后台初始化
     * Admincp::$HOOKS['onInit']
     */
    public static function onInit()
    {
        Script::$dialog['title'] = iPHP_APP;

        self::captcha(1); //验证码
        Member::auth();  //登录验证
        Admincp::$DATA = array(
            'userid'   => Member::$user_id, //管理员绑定的用户ID
            'username' => Member::$DATA['account'],
            'nickname' => Member::$DATA['nickname']
        );
        Files::init(Admincp::$DATA); //文件应用初始配置
        Former::init(Admincp::$DATA); //自定义表单、应用
        Menu::init(); //后台菜单初始配置
    }
    /**
     * 验证权限
     * Admincp::$HOOKS['onAuth']
     */
    public static function onAuth()
    {
        return AdmincpAccess::auth();
    }

    /**
     * 应用实例化前
     * Admincp::$HOOKS['onAppBegin']
     */
    public static function onAppBegin($className)
    {
        AdmincpAccess::security(Member::$user_id); //检验CSRF
    }
    /**
     * 应用实例化后
     * Admincp::$HOOKS['onAppInit']
     */
    public static function onAppInit($className)
    {
        self::history(); //访问记录
        self::process(); //前置处理
    }
    /**
     * 应用方法执行前
     * Admincp::$HOOKS['onMethodBegin']
     */
    public static function onMethodBegin($className, $params)
    {
        AdmincpAccess::accessUri(APP_URL_QS); //检查URL权限
        AdmincpAccess::accessApp(
            Admincp::$APP_NAME,
            Admincp::$APP_INSTANCE,
            Admincp::$APP_METHOD,
            $params
        ); //检查APP权限

        ob_start();
    }
    /**
     * 应用方法执行后
     * Admincp::$HOOKS['onMethodEnd']
     */
    public static function onMethodEnd($className, &$response)
    {
        $html = ob_get_contents();
        ob_end_clean();
        AdmincpAccess::html($html);
        echo $html;
    }
    /**
     * 访问历史记录
     * Admincp::$HOOKS['onHistory']
     */
    public static function history($get = false)
    {
        $Cache   = Cache::newfileCache();
        $key     = iPHP_APP_SITE . '/menu/history' . Member::$user_id;
        $history = (array)$Cache->get($key);
        if ($get) {
            return $history;
        }
        array_unshift($history, APP_DOURL);
        $history = array_unique($history);
        if (count($history) > 20) {
            array_pop($history);
        }
        $Cache->add($key, $history, 0);
    }
    /**
     * 通用前置处理
     * Admincp::$HOOKS['onProcess']
     */
    public static function process()
    {
        //Fixes 83
        $_GET['perpage'] > 10000 && $_GET['perpage'] = 10000;

        if (Admincp::$APP_METHOD == 'do_batch') {
            $bmIds = $_POST['bmIds'];
            if (isset($_POST['bmIds']) && $bmIds) {
                $_POST['id'] = explode(',', $bmIds);
            }
        }
        admincpAdmincp::createLog();
    }

    /**
     * 后台验证码
     */
    public static function captcha($flag = null)
    {
        //显示
        if (Request::get('do') == 'captcha') {
            Captcha::get();
            exit;
        }
        //检查
        if ($flag) {
            if (Request::post('action') == 'login') {
                $captcha = Request::post('captcha');
                if ($captcha === iPHP_KEY) {
                    return true;
                }

                Captcha::check($captcha) or iJson::error('验证码错误', '.captcha-img');
            }
        }
    }
}
