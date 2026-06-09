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

define('ADMINCP', true);
define('ADMINCP_URL', iPHP_SELF);

// use iPHP\core\Security;
// use iPHP\core\Script;

class Admincp
{
    public static $APP = null;
    public static $APP_CLASS = null;
    public static $APP_ID = null;
    public static $APP_INSTANCE = null;
    public static $APP_NAME = null;
    public static $APP_SNAME = null;
    public static $APP_DO = null;
    public static $APP_SDO = null;
    public static $APP_METHOD = null;
    public static $APP_PATH = '';
    public static $APP_FILE = null;
    public static $APP_DATA = [];
    public static $APP_PARAMS = null;
    public static $DATA = [];

    public static $METHOD_BEFOR = null;
    public static $METHOD_RUN = true;
    public static $METHOD_AFTER = null;
    public static $RESPONSE = null;
    public static $RC = null;
    public static $LANGUAGE = null;

    public static function init($app = null, $do = null, $prefix = null)
    {
        empty($app) && $app = Request::sparam('app');
        empty($do) && $do = Request::sget('do');
        empty($app) && $app = 'admincp';

        if (is_null($prefix)) {
            if (Request::isPost()) {
                $prefix = iPHP_POST_PREFIX;
                if ($action = Request::sparam('action')) {
                    $do = $action;
                    $prefix = iPHP_POST_PREFIX;
                }
            } else {
                $prefix = iPHP_GET_PREFIX;
            }
        }
        $do or $do = 'index';
        //$do = do_save;
        if (strpos($do, iPHP_GET_PREFIX) === 0 || strpos($do, iPHP_POST_PREFIX) === 0) {
            list($prefix, $do) = explode('_', $do);
        }
        // if (Request::isAjax()) { //do_index_data
        // 	$do.= '_data';
        // }
        strpos($app, '..') !== false && self::error('what the fuck');

        self::$APP_NAME = $app;
        self::$APP_DO = $do;
        self::$APP_SDO = Request::sget('sdo');
        self::$APP_METHOD = $prefix . $do;

        //TestAdmincp => TestAdmincp.php
        $className = sprintf('%sAdmincp', ucfirst($app));
        $appFile = sprintf('%s.php', $className);
        //admincp.php?app=test_node
        //admincp.php?app=testNode
        $app = Application::get($app, $sub);
        //test_node => testNodeAdmincp.php
        //testNodeAdmincp => testNodeAdmincp.php
        if ($sub) {
            $className = sprintf('%sAdmincp', ucfirst($app) . ucfirst($sub));
            $appFile = sprintf('%s.php', $className);
        }
        //根应用
        //应用 articleCategory
        //根应用 article 用于识别应用所在目录
        self::$APP = $app;
        self::$APP_PATH = Application::path($app);
        self::$APP_SNAME = $sub;
        self::$APP_FILE = self::$APP_PATH . $appFile;
        self::$APP_DATA = Apps::get($app);
        self::$APP_ID = self::$APP_DATA['id'];

        self::define($prefix);

        //程序文件不存为自定义APP，指向content应用
        if (!is_file(self::$APP_FILE) && self::$APP_DATA['apptype'] == Apps::CONTENT_TYPE) {
            if (self::$APP_DATA) {
                $sub && $sub = ucfirst($sub);
                $className = sprintf('Content%sAdmincp', $sub);
                $appFile = sprintf('%s.php', $className);
                self::$APP_PATH = Application::path('content');
                self::$APP_FILE = self::$APP_PATH . $appFile;
            } else {
                self::error([
                    'Unable to load app data <b>%s</b>(%s)',
                    [self::$APP_FILE, $app]
                ]);
            }
        }

        is_file(self::$APP_FILE) or self::error([
            'Unable to find admincp file %s (%s)',
            [$appFile, self::$APP_FILE]
        ]);
        if ($startup = self::startup($prefix)) {
            $className = $startup;
        }
        self::$APP_CLASS = $className;
        return $className;
    }

    public static function run($app = null, $do = null, $params = null, $prefix = null)
    {

        try {
            DB::version();
            Session::init('admincp');
            $className = self::init($app, $do, $prefix);
            // testHook::admincp_run_start
            self::hooks('run', 'start');
            AdmincpHooks::onInit(); //相关应用初始化配置
            self::$APP_INSTANCE = iPHP::getInstance($className, $rc);
            AdmincpHooks::onAppInit();
            $params === null && $params = self::$APP_PARAMS;
            if ($params === 'object') {
                return self::$APP_INSTANCE;
            }
            $method = self::$APP_METHOD;
            if ($rc && !$rc->hasMethod($method)) {
                // if (strpos($method, iPHP_GET_PREFIX) === 0) {
                //     var_dump($method,iPHP_GET_PREFIX);
                //     Admincp::$APP_METHOD = $method = iPHP_GET_PREFIX.'general';
                // }else{
                self::error([
                    'Call to undefined method <b>%s::%s</b>',
                    [$className, $method]
                ]);
                // }
            }
            //验证权限
            AdmincpHooks::onPermission();
            self::callMethod($className, $method, $params, $rc);
            AdmincpHooks::onMethodEnd(); //应用方法执行后
            return self::respond(self::$RESPONSE, $className, $method);
        } catch (\Exception $ex) {
            try {
                DB::rollBack();
            } catch (\PDOException $pdo) {
            }
            // testHook::admincp_run_exception
            self::hooks('run', 'exception', null, [$ex]);
            return AdmincpHooks::onException($ex);
        }
    }

    /**
     * 程序执行完毕后执行
     * destruct_do_METHOD_NAME
     *
     * @return void
     */
    public static function destruct()
    {
        $method = 'destruct_' . self::$APP_METHOD;
        // $key = 'is_' . $method;
        // $isrun = self::$callback[$key];
        if (self::$APP_INSTANCE && method_exists(self::$APP_INSTANCE, $method)) {
            // self::$callback[$key] = true;
            self::$APP_INSTANCE->$method();
        }
        self::hooks(self::$APP, 'destruct');
    }

    public static function hooks($app, $type = '', $do = '', $params = [])
    {
        $hooks = isset(iCMS::$config['hooks']['admincp']) ? iCMS::$config['hooks']['admincp'] : '';
        // var_dump($app.'_'.$do.'_'.$type);
        if (empty($hooks)) {
            return;
        }
        $callback = '';
        if ($app && $do && $type) {
            $callback = $hooks[$app][$do][$type];
        } elseif ($app && $type) {
            $callback = $hooks[$app][$type];
        } elseif ($app) {
            // $callback = self::$callback['hooks'][$app];
        }
        is_callable($callback) && iPHP::callback($callback, $params, E_USER_ERROR);
    }

    public static function callMethod($className, $method, $params = [], $rc = null)
    {
        $commit = Request::isPost();
        if (isset(self::$APP_INSTANCE->noCommit) && self::$APP_INSTANCE->noCommit === true) {
            $commit = false;
        }
        $commit && DB::beginTransaction();
        AdmincpHooks::onMethodBegin($className, $params); //应用方法执行前
        // testHook::admincp_$app_start
        self::hooks(self::$APP, 'start');
        is_null($rc) && $rc = new ReflectionClass($className);
        //__do_add
        self::callPrefix('__%s', $rc, $className, $method, $params);
        // testHook::admincp_$app_$do_start
        self::hooks(self::$APP, 'start', self::$APP_DO);
        //do_add
        if (self::$METHOD_RUN) {
            self::$RESPONSE = self::invoke($className, $method, $params);
        }
        // testHook::admincp_$app_$do_start
        self::hooks(self::$APP, 'end', self::$APP_DO);
        //do_add__
        self::callPrefix('%s__', $rc, $className, $method, $params);
        try {
            $commit && DB::commit();
        } catch (\PDOException $pdo) {
        }
        // testHook::admincp_$app_end
        self::hooks(self::$APP, 'end');
    }

    public static function callPrefix($format, $rc, $className, $method, $params = [])
    {
        $met = sprintf($format, $method);
        // if (!$rc->hasMethod($METHOD_AFTER)) {
        //     $METHOD_AFTER = self::$METHOD_AFTER ?: '';
        // }
        if ($rc->hasMethod($met)) {
            $_response = self::invoke($className, $met, $params);
            is_null($_response) or self::$RESPONSE = $_response;
        }
    }

    public static function invoke($className, $method, &$params = [])
    {
        $variable = iPHP::getMethodParams($className, $method, $params);
        $params = [];
        foreach ($variable as $key => $value) {
            if (strpos($key, 'ref:') !== false) {
                $idx = substr($key, 4);
                $params[$idx] = &$value;
            } else {
                $params[$key] = $value;
            }
        }
        return call_user_func_array([self::$APP_INSTANCE, $method], $params);
    }

    // public static function runMethod($method,$params,$flag=true){
    // 	if (method_exists(self::$APP_INSTANCE,$method) && $flag) {
    // 		$_response = call_user_func_array(array(self::$APP_INSTANCE, $method), (array) $params);
    // 		is_null($_response) OR $response = $_response;
    // 	}
    // }
    public static function define($prefix = iPHP_GET_PREFIX)
    {
        defined('APP_URL_DO') or define('APP_URL_DO', strtolower(substr($prefix, 0, -1)));
        defined('APP_URL') or define('APP_URL', self::url(self::$APP_NAME));
        defined('APP_DOURL') or define('APP_DOURL', self::url([self::$APP_NAME, self::$APP_DO]));
        defined('APP_MAINID') or define('APP_MAINID', 'cms:' . self::$APP_NAME . ':' . self::$APP_DO);

        // define('APP_ASSETS', '/assets/admincp/app/');
        // define('APP_ASSETS_DIR', APP_ASSETS . self::$APP);
        // define('APP_ASSETS_ROOT', iPHP_PUBLIC . APP_ASSETS_DIR);

        defined('APP_ASSETS') or define('APP_ASSETS', ADMINCP_URL . '/');
        defined('APP_ASSETS_DIR') or define('APP_ASSETS_DIR', sprintf('%s/%s', self::url(self::$APP), APP_ASSETS_NAME));
        defined('APP_ASSETS_ROOT') or define('APP_ASSETS_ROOT', Admincp::$APP_PATH . APP_ASSETS_NAME);
    }

    public static function respond($response, $class, $method)
    {
        // testHook::admincp_run_end
        self::hooks('run', 'end', null, [$response]);
        if (ob_get_length()) {
            $html = ob_get_contents();
            ob_get_clean();
            self::hooks('run', 'html', null, [&$html]);
            // $html = str_replace('src="/assets/', 'src="./assets/', $html);
            // $html = str_replace('href="/assets/', 'href="./assets/', $html);
            echo $html;
            return $html;
        }

        $do = self::$APP_DO;
        $title = AppsAccess::$docMap[$do];
        $doc = Helper::getAppComment($class, $method);
        $doc['title'] && $title = (string) $doc['title'];
        $title = $title ?: $method;
        $title = str_replace('{title}', self::$APP_DATA['title'] ?: '', $title);
        $title = str_replace('{app.name}', self::$APP_DATA['name'] ?: '', $title);

        // $navData = Menu::getNav();
        // $title = $navData[1]['name'];
        return Application::response($response, $title);
    }

    public static function startup($prefix = null)
    {
        $app = ucfirst(strtolower(self::$APP_NAME));
        $prefix = ucfirst(strtolower(rtrim($prefix, '_')));
        $doArray = array_map('ucfirst', explode('_', strtolower(self::$APP_DO)));
        $do = implode('', $doArray);
        // article/articleDoManageAdmincp.php
        // article/articleActionSaveAdmincp.php
        $className = $app . $prefix . $do . 'Admincp';
        $path = self::$APP_PATH . $className . '.php';
        // var_dump([$app , $prefix , $do],$className, $path);
        if (!is_file($path)) {
            // article/articleDoAdmincp.php
            // article/articleActionAdmincp.php
            $className = $app . $prefix . 'Admincp';
            $path = self::$APP_PATH . $className . '.php';
            // var_dump($className, $path);
        }
        if (is_file($path)) {
            require_once $path;
            if (class_exists($className, false)) {
                self::$APP_FILE = $path;
                $rc = new ReflectionClass($className);
                if ($rc->hasMethod(self::$APP_METHOD)) {
                    return $className;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * 错误提示
     *
     * @param string $msg
     * @param string $state
     * @return void
     */
    public static function error($msg, $state = 'error')
    {
        $code = -9;
        if (is_numeric($state)) {
            $code = $state;
            $state = 'error';
        }
        Application::error($msg, $code, $state);
    }

    // public static function makeQS($array)
    // {
    // 	$key = array_search(iPHP_APP, $array);
    // 	if ($key !== FALSE) unset($array[$key]);
    // 	$array = array_filter($array);
    // 	return http_build_query($array);
    // }
    // public static function makePath($array)
    // {
    // 	$key = array_search(iPHP_APP, $array);
    // 	if ($key !== FALSE) unset($array[$key]);
    // 	$array = array_filter($array);
    // 	return http_build_query($array);
    // }
    /**
     * 后台网址生成
     *
     * @param string $uri test,test/add
     * @param array|string|bool $params 为true时，以当前应用为url base
     * @return mixed
     */
    public static function url($uri, $params = '')
    {
        $url = ADMINCP_URL;
        if (empty($uri)) {
            return $url;
        }
        if ($params === true) {
            $url = APP_URL;
            $params = null;
        }
        $uri = str_replace(ADMINCP_URL, '', $uri);
        if (is_string($uri) && strpos($uri, './') !== false) {
            $uri = sprintf('/%s/%s', Admincp::$APP_NAME, substr($uri, 2));
        }
        if (is_array($uri)) {
            foreach ($uri as $key => $value) {
                $url .= '/' . $value;
            }
        } else {
            $uri && $url .= '/' . ltrim($uri, '/');
        }

        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $url .= sprintf('/%s/%s', $key, $value);
            }
        } else {
            is_string($params) && $url .= '/' . ltrim($params, '/');
        }
        return rtrim($url, '/');
    }

    /**
     * /test/aaa
     *
     * @return String
     */
    public static function urlPath($url = null)
    {
        $url === null && $url = $_SERVER['REQUEST_URI'];
        $parse = parse_url($url);
        $path = rtrim($parse['path'], '/');
        return str_replace(ADMINCP_URL, '', $path);
    }
}
