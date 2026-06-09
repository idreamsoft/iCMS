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
     */
    public static function onInit()
    {
        self::language();
        Admin::isLogin();
        //后台菜单初始配置
        Menu::init();
        $admin = Admin::data();
        //文件应用初始配置
        Files::init($admin);
        //自定义表单、应用
        iPHP::callback(['Former', 'init'], [$admin]);
        //站点设置
        Config::setSiteId(1);
        //检验CSRF
        AdmincpAuth::security($admin['id']);
    }

    public static function language()
    {
        // 检查配置项是否存在，避免未定义索引的警告
        $defaultLang = isset(iCMS::$config['language']['admincp']) ? iCMS::$config['language']['admincp'] : Lang::$default;
        $sessionLang = Session::get('language');
        // ADMINCP::$LANGUAGE = $sessionLang ?: $defaultLang;
        ADMINCP::$LANGUAGE = 'zh';
        Lang::init(ADMINCP::$LANGUAGE);
        //{*}/etc/lang/zh.admincp*.json
        Lang::load('*', 'admincp');
        //{$app}/etc/lang/zh.admincp*.json
        Lang::load(Admincp::$APP);
    }

    /**
     * 验证权限
     */
    public static function onPermission()
    {
        // 读取类注释与方法注释：
        // 1. 类级注释可作为统一兜底权限（减少每个接口重复声明）
        // 2. 方法级注释优先级更高，可覆盖类级配置
        $classDoc = (array)Helper::getDocComment(Admincp::$APP_CLASS);
        $methodDoc = (array)Helper::getDocComment(Admincp::$APP_CLASS, Admincp::$APP_METHOD);
        $doc = array_merge_recursive($classDoc, $methodDoc);

        // 无需登录的方法（login = false）直接跳过所有鉴权
        $requireLogin = !($doc && $doc['login'] === false);
        if ($requireLogin) {
            //检测是否登录
            // 1. 必须登录
            Admin::isLogin(true);
            // 2. 角色验证
            //判断需要验证角色权限
            if (isset($doc['role'])) {
                AdmincpAuth::checkRole($doc['role']);
            }

            // 标记是否已完成权限标识鉴权：
            // 若已通过 permission 鉴权，则无需再做 URI 精确鉴权，
            // 解决一个页面多个接口都要逐条配置 URI 的维护问题。
            $permissionChecked = false;

            // 3. 权限验证
            if (isset($doc['permission'])) {
                AdmincpAuth::checkPermission($doc['permission']);
                $permissionChecked = true;
            }

            //判断是否需要验证权限
            //无需鉴权的方法,但需要登录
            AdmincpAuth::init();

            // 4. URI权限验证（默认需要，除非 auth = false）
            $needUriAuth = (!isset($doc['auth']) || $doc['auth'] !== false);
            if ($permissionChecked) {
                $needUriAuth = false;
            }

            if ($needUriAuth) {
                AdmincpAuth::checkUri(APP_DOURL);
                // 判断控制器和方法是否有对应权限
            }
        }
        Admincp::$DATA['access'] = true;
        //@run true|false //是否运行
        isset($doc['run']) && Admincp::$METHOD_RUN = $doc['run'];
        //@befor TestDoc::befor //运行前运行
        // isset($doc['befor']) && Admincp::$METHOD_BEFOR = $doc['befor'];
        // isset($doc['after']) && Admincp::$METHOD_AFTER = $doc['after'];
    }

    /**
     * 应用实例化后
     */
    public static function onAppInit()
    {
        self::history(); //访问记录
        self::process(); //前置处理
    }

    /**
     * 应用方法执行前
     */
    public static function onMethodBegin($className, $params)
    {
        ob_start();
    }

    /**
     * 应用方法执行后
     */
    public static function onMethodEnd()
    {
        $html = ob_get_contents();
        if ($html) {
            ob_end_clean();
            AdmincpBase::parse($html);
            AdmincpAuth::html($html);
            echo $html;
        }
        return $html;
    }

    /**
     * 后台异常处理
     *
     * @param Exception $ex
     * @return void
     */
    public static function onException(Exception $ex)
    {
        Admincp::define();
        $msg = $ex->getMessage();
        $code = $ex->getCode();
        $AE = new AdmincpException($msg, $code, $ex);
        $AE->display();
    }

    /**
     * 访问历史记录
     */
    public static function history($get = false)
    {
        $Cache = Cache::file();
        $key = iPHP_APP_SITE . '/menu/history' . Admin::$user_id;
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
     */
    public static function process()
    {
        AdmincpLogAdmincp::create();
    }
}
