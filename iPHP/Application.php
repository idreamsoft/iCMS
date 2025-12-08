<?php

// namespace iPHP;
/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */

class Application
{
    public static $INSTANCE = null;
    public static $ID = null;
    public static $APP = null;
    public static $NAME = null;
    public static $SNAME = null;
    public static $DO = null;
    public static $PREFIX = null;
    public static $METHOD = null;
    public static $PATH = null;
    public static $PATHS = [];
    public static $FILE = null;
    public static $PARAMS = null;
    public static $DIRS = [];
    public static $CLASS = null;
    public static $RESPONSE = null;
    public static $callback = [];

    public static function init($site = null)
    {
        self::site($site);
        self::database();
    }

    /**
     * 设置数据库
     *
     * @return void
     */
    public static function database()
    {
        $paths = [
            //config/$site/database.php
            //config/iCMS/database.php

            iPHP_APP_CONFDIR,
            iPHP_CONFIG_DIR //config/database.php
        ];
        foreach ($paths as $base) {
            $path = $base . '/database.php';
            if (file_exists($path) && is_readable($path)) {
                // 使用解析器函数安全地解析配置
                $config = require_once $path;
                DB::config($config);
                // DB::version();
                return $config;
            }
        }
        self::error('Cannot find database configuration file.', -899);
    }

    /**
     * 定义应用配置文件，配置目录
     *
     * @param String $site
     * @return string
     */
    public static function site($site = null)
    {
        if ($site === null) {
            $site = iPHP_MULTI_SITE ? $_SERVER['HTTP_HOST'] : iPHP_APP;
            if (iPHP_MULTI_DOMAIN) {
                // preg_match("/[^\.\/][\w\-]+\.[^\.\/]+$/", $site, $matches); //只绑定主域
                if (!preg_match("/[^\.\/][\w\-]+\.[^\.\/]+$/i", $site, $matches)) {
                    self::error('Invalid domain name.', -898);
                }
                $site = $matches[0];
            }
            if (strpos($site, '..') !== false) {
                self::error('What are you doing', -898);
            }
        }
        if (empty($site)) {
            self::error('Unable to find site ', -897);
        }

        defined('iPHP_APP_SITE') or define('iPHP_APP_SITE', $site);
        defined('iPHP_APP_CONFDIR') or define('iPHP_APP_CONFDIR', iPHP_CONFIG_DIR . '/' . iPHP_APP_SITE); //网站配置目录
        defined('iPHP_APP_CONFIG') or define('iPHP_APP_CONFIG', iPHP_APP_CONFDIR . '/config.php'); //网站配置文件
        return $site;
    }

    /**
     * 获取配置
     *
     * @param String $site
     * @return Array
     */
    public static function config()
    {
        if (!is_file(iPHP_APP_CONFIG)) {
            $msg = sprintf(
                'Unable to find "%s" config file (%s).Please install %s',
                iPHP_APP_SITE,
                iPHP_APP_CONFIG,
                iPHP_APP
            );
            self::error($msg, -896);
        }

        return require_once iPHP_APP_CONFIG;
    }

    public static function run($app = null, $do = null, $params = null, $prefix = null)
    {
        try {
            self::hooks('run', 'init');
            empty($app) && $app = Request::sparam('app'); //单一入口
            empty($app) && $app = File::name(iPHP_SELF);
            empty($do) && $do = Request::sparam('do');
            empty($version) && $version = Request::sget('version');
            empty($app) && $app = 'index';

            if (is_null($prefix)) {
                if (Request::isPost()) {
                    $prefix = iPHP_POST_PREFIX;
                } else {
                    $prefix = iPHP_GET_PREFIX;
                }
            }
            if (Request::isPost() && $action = Request::sparam('action')) {
                $do = $action;
                $prefix = iPHP_POST_PREFIX;
            }
            iPHP_SHELL && list($app, $do, $params) = self::cmd();

            $do or $do = 'index';
            //$do = do_save;
            if (strpos($do, iPHP_GET_PREFIX) === 0 || strpos($do, iPHP_POST_PREFIX) === 0) {
                list($prefix, $do) = explode('_', $do);
            }
            if (strpos($app, '..') !== false) {
                self::error('what the fuck', -895);
            }

            //TestApp => TestApp.php
            $className = sprintf('%sApp', ucfirst($app));
            $appFile = sprintf('%s.php', $className);
            //test.php?app=test_abc
            //test.php?app=testAbc
            $app = self::get($app, $sub);
            //test_abc => testAbcApp.php
            //testAbcApp => testAbcApp.php
            if ($sub) {
                self::$SNAME = ucfirst($app) . ucfirst($sub);
                $className = sprintf('%sApp', ucfirst($app) . ucfirst($sub));
                $appFile = sprintf('%s.php', $className);
            }

            self::$APP = $app;
            self::$PATH = self::path($app);
            self::$FILE = self::$PATH . $appFile;
            self::$CLASS = $className;
            self::$NAME = $app;
            self::$DO = $do;
            self::$PREFIX = $prefix;
            self::$METHOD = self::$PREFIX . $do;

            self::startup($className, self::$PREFIX);
            //不存在应用目录且**App.php不存在，调用自定义APP
            //并初始化 Application::$instance,Application::$FILE
            if (!is_dir(self::$PATH) && !is_file(self::$FILE)) {
                // testHook::run_file
                self::hooks('run', 'file', [$app]);
            }
            if (!is_file(self::$FILE)) {
                self::error(sprintf('Unable to find app file <b>%s</b>', self::$FILE), -894);
            }
            AppsHook::begin();
            // testHook::run_begin
            self::hooks('run', 'begin');
            if (self::$INSTANCE === null) {
                // try {
                self::$INSTANCE = iPHP::getInstance($className, $rc);
                // } catch (\Exception $ex) {
                //     $msg = $startup ?: sprintf('%s in file <b>%s</b>', $ex->getMessage(), self::$FILE);
                //     self::error($msg, -893);
                // }

                if (isset(self::$INSTANCE->instance) && self::$INSTANCE->instance) { //子系统模式
                    self::$INSTANCE = self::$INSTANCE->instance;
                    $className = get_class(self::$INSTANCE);
                    $rc = new ReflectionClass($className);
                }
            } else {
                $className = get_class(self::$INSTANCE);
                $rc = new ReflectionClass($className);
            }
            // testHook::run_after
            self::hooks('run', 'after');
            /**
             * 无方法，仅实例化
             */
            if (isset(self::$INSTANCE->noMethod) && self::$INSTANCE->noMethod === true) {
                return self::$INSTANCE;
            }
            $commit = Request::isPost();

            // testHook::app_$app_start
            self::hooks($app, 'start');
            $method = self::$METHOD;
            if ($rc->hasMethod($method)) {
                $params === null && $params = self::$PARAMS;

                self::$callback['params'] && iPHP::callback(self::$callback['params'], [&$params], E_USER_ERROR);

                if ($params === 'object') {
                    return self::$INSTANCE;
                }
                if (isset(self::$INSTANCE->noCommit) && self::$INSTANCE->noCommit === true) {
                    $commit = false;
                }
                $commit && DB::beginTransaction();

                $init_method = '__INIT__'; 				//testApp::__INIT__
                $prefix_start = strtolower(self::$PREFIX) . 'Init'; //testApp::doInit OR testApp::actionInit
                $before_method = 'before_' . $method; //testApp::before_aaa
                $after_method = 'after_' . $method; 	//testApp::after_aaa

                $rc->hasMethod($init_method) && call_user_func_array([self::$INSTANCE, $init_method], (array) $params);
                $rc->hasMethod($prefix_start) && call_user_func_array([self::$INSTANCE, $prefix_start], (array) $params);
                $rc->hasMethod($before_method) && call_user_func_array([self::$INSTANCE, $before_method], (array) $params);
                // testHook::app_$app_$do_start
                self::hooks($app, 'start', $do);
                if (isset(self::$INSTANCE->isRun) && self::$INSTANCE->isRun === false) {
                    $response = null;
                } else {
                    $response = call_user_func_array([self::$INSTANCE, $method], (array) $params);
                }
                $rc->hasMethod($after_method) && call_user_func_array([self::$INSTANCE, $after_method], [$params, &$response]);

                // testHook::app_$app_$do_end
                self::hooks($app, 'end', $do);
                // return $response;
                $commit && DB::commit();
                // testHook::app_$app_end
                self::hooks($app, 'end', [$response]);
                return self::respond($response, $className, $method);
            } else {
                DB::rollBack();
                self::error(sprintf('Call to undefined method <b>%s::%s</b> in file <b>%s</b>', $className, $method, self::$FILE), -893);
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * 命令行模式
     * @return array
     */
    public static function cmd()
    {
        $argv = (array)$_SERVER['argv'];
        if (empty($argv[1])) {
            exit('error >> need argc' . PHP_EOL . 'exp:' . $argv[0] . ' app method' . PHP_EOL . PHP_EOL);
        }
        $app = $argv[1];
        $do = $argv[2];
        // id=1
        if ($argv[3] && (strpos($argv[3], '=') !== false || strpos($argv[3], '&') !== false) && !isset($argv[4])) {
            $argc = [];
            parse_str($argv[3], $argc);
            $params = [$argc];
        } else {
            $params = array_slice($argv, 3);
        }
        return [$app, $do, $params];
    }

    public static function hooks($app, $type = '', $do = '', $params = [])
    {
        if ($app === 'run') {
            $hooks = isset(iCMS::$config['hooks']['run']) ? iCMS::$config['hooks']['run'] : [];
            $callback = $hooks[$type];
            $params = $do;
            $callback && iPHP::callback($callback, $params, E_USER_ERROR);
            return;
        } else {
            $hooks = isset(iCMS::$config['hooks']['app']) ? iCMS::$config['hooks']['app'] : [];
        }
        if (empty($hooks)) {
            return;
        }

        $callback = '';
        if ($app && $do && is_string($do) && $type) {
            $callback = $hooks[$app][$do][$type];
        } elseif ($app && $type) {
            $callback = $hooks[$app][$type];
            $params = $do;
        } elseif ($app) {
            // $callback = $hooks[$app];
        }

        $callback && iPHP::callback($callback, $params, E_USER_ERROR);
    }

    /**
     * 请求响应
     *
     * @param response $response
     * @param string $class
     * @param string $method
     * @return void
     */
    public static function respond($response, $class, $method)
    {
        // testHook::run_end
        self::hooks('run', 'end');
        if (self::$RESPONSE === true) {
            self::$RESPONSE = $response;
            return $response;
        }

        if (ob_get_length()) {
            $html = ob_get_contents();
            ob_get_clean();
            self::hooks('run', 'html', [&$html]);
            echo $html;
            return $html;
        }

        $title = $method;
        $doc = Helper::getAppComment($class, $method);
        $doc['title'] && $title = (string) $doc['title'];
        self::response($response, $title);
    }

    /**
     * 请求响应
     *
     * @param response $response
     * @param string $title
     * @return void
     */
    public static function response($response, $title)
    {
        if (is_null($response)) {
            //POST return null;
            if (Request::isPost()) {
                iJson::success($title);
            }
        }
        if ($response === true) {
            // return true;
            iJson::success($title);
        } elseif ($response === false) {
            // return false;
            self::alert($title);
        } elseif (is_array($response)) {
            if (empty($response)) {
                iJson::success();
            }
            //数字索引
            if (Utils::isAssocArr($response)) {
                if ($response[0] === true) {
                    // return [true,'asd'];
                    iJson::success($response[1] ?: $title, $response[2]);
                } elseif ($response[0] === false) {
                    // return [false,'asd'];
                    self::alert($response[1] ?: $title, $response[2]);
                } elseif (is_array($response[0]) && !is_array($response[1])) {
                    // return [[1,2,3],'asd','url'];
                    iJson::success($response[0], $response[1], $response[2]);
                } else {
                    //return ['asd','123'];
                    iJson::success($response, $title);
                }
            } else {
                $data = $response;
                $response = [];
                $response['message'] = $data['message'] ?: ($data['title'] ?: $title);
                $response['code'] = isset($data['code']) ? $data['code'] : 1;
                $response['state'] = isset($data['state']) ? $data['state'] : 'SUCCESS';
                $response['url'] = $data['url'] ?: '';
                $response['data'] = $data['data'] ? (array)$data['data'] : [];
                unset($data['title'], $data['message'], $data['code'],$data['state'], $data['url'], $data['data']);
                $data && $response['data'] = array_merge($response['data'], $data);

                iJson::success(
                    (array)$response['data'],
                    $response['message'],
                    $response['url'],
                    $response['code'],
                    $response['state']
                );
            }
        } elseif (is_string($response)) {
            // return http://...;
            // return string;
            if (Request::isUrl($response, true) || strpos($response, '/') === 0) {
                iJson::success($title, $response);
            } elseif (strpos($response, 'url:') !== false) {
                list($prefix, $url, $time) = explode(':', $response);
                is_null($time) && $time = 1500;
                iJson::success(['time'=>$time], $title, $url);
            } else {
                iJson::success($response);
            }
        } elseif (is_numeric($response)) {
            $response > 0 ?
                iJson::success($response, $title) :
                self::alert($title);
        } else {
            return $response;
        }
    }

    /**
     * 自动加载
     *
     * @param string $name 类名
     * @param boolean $flag 是否返回内容
     * @return void
     */
    public static function load($name = null, $flag = false)
    {
        try {
            if ($name) {
                File::check($name);
                $arr = require_once iPHP_APP_CONFDIR . "/{$name}.php";
                if ($flag) {
                    return $arr;
                }
                $GLOBALS[strtoupper($name)] = $arr;
            } else {
                $files = glob(iPHP_APP_CONFDIR . '/*.php');
                if (is_array($files)) {
                    foreach ($files as $key => $path) {
                        $name = File::name($path);
                        $arr = require_once $path;
                        is_array($arr) && $GLOBALS[strtoupper($name)] = $arr;
                    }
                }
            }
        } catch (\Exception $ex) {
            return false;
        }
    }

    public static function get($app, &$sub)
    {
        //api.php?app=test_node
        if (stripos($app, '_') !== false) {
            $pieces = explode('_', $app);
        } else {
            //api.php?app=testNode
            $pieces = preg_split('/(?<=\w)(?=[A-Z])/', $app);
        }
        if (count($pieces) > 1) {
            $app = array_shift($pieces);
            $sub = implode('', $pieces);
        }
        $app = strtolower($app);
        return $app;
    }

    public static function path($app, $file = null)
    {
        return iPHP::getAppPath($app, $file);
    }

    public static function setDir($key, $value)
    {
        self::$DIRS[$key] = $value;
    }

    /**
     * 程序执行完毕后执行
     * destruct_do_METHOD_NAME
     *
     * @return void
     */
    public static function destruct()
    {
        $method = 'destruct_' . self::$METHOD;
        $key = 'is_' . $method;
        $isrun = self::$callback[$key];
        if (method_exists(self::$INSTANCE, $method) && !$isrun) {
            self::$callback[$key] = true;
            self::$INSTANCE->$method();
        }
        self::hooks(self::$APP, 'destruct');
        self::hooks('run', 'destruct');
    }

    public static function startup(&$className, $prefix = null)
    {
        $app = ucfirst(strtolower(self::$NAME));
        $prefix = strtolower(substr($prefix, 0, -1));
        $prefix = ucfirst(strtolower($prefix));
        $doArray = array_map('ucfirst', explode('_', strtolower(self::$DO)));
        $do = implode('', $doArray);
        // GET article.php?do=manage
        // article/ArticleDoManageApp.php

        // POST article.php
        // action=save
        // article/ArticleDoneSaveApp.php

        // GET api.php?app=article&do=test
        // article/ArticleApiTestApp.php

        // POST api.php?app=article
        // action=save
        // article/ArticleDoneTestApp.php
        $class = $app;
        if (stripos(self::$CLASS, 'App') !== false) {
            $class = substr(self::$CLASS, 0, -3);
        }
        $_className = $class . 'MyApp';
        $path = self::$PATH . $_className . '.php';
        self::$PATHS[$_className] = $path;
        // var_dump($_className, $path);
        if (!is_file($path)) {
            // article/ArticleDo$doApp.php
            // article/ArticleDone$doApp.php
            // article/ArticleApi$doApp.php
            // article/ArticleApiIndexApp.php
            $_className = $class . $prefix . $do . 'App';
            $path = self::$PATH . $_className . '.php';
            self::$PATHS[$_className] = $path;
            // var_dump($className, $path);
        }
        if (!is_file($path)) {
            // article/Article$doApp.php
            // article/Article$doApp.php
            // article/Article$doApp.php
            // article/ArticleIndexApp.php
            $_className = $class . ucfirst($do) . 'App';
            $path = self::$PATH . $_className . '.php';
            self::$PATHS[$_className] = $path;
            // var_dump($className, $path);
        }
        if (!is_file($path)) {
            // article/ArticleDoApp.php
            // article/ArticleDoneApp.php
            // article/ArticleApiApp.php
            // article/ArticleShellApp.php
            $_className = $class . $prefix . 'App';
            $path = self::$PATH . $_className . '.php';
            self::$PATHS[$_className] = $path;
            // var_dump($_className, $path);
        }

        // var_dump(self::$PATHS);
        // var_dump($_className, $path);
        if (is_file($path)) {
            require_once $path;
            if (class_exists($_className, false)) {
                self::$FILE = $path;
                $rc = new ReflectionClass($_className);
                if ($rc->hasMethod(self::$METHOD)) {
                    $className = $_className;
                } else {
                    return sprintf('Call to undefined method <b>%s::%s</b> in file <b>%s</b>', $_className, self::$METHOD, self::$FILE);
                }
            }
        }
    }

    /**
     * 等同 iPHP::error,加载语言包
     * 可用于前后台
     *
     * @param string|array $msg
     * @param integer $code
     * @param string $state
     * @throws sException
     */
    public static function error($msg, $code = -1, $state = 'error')
    {
        if (is_array($msg)) {
            $msg = Lang::get($msg[0], $msg[1]);
        } elseif (is_string($msg)) {
            $msg = Lang::get($msg);
        }
        iPHP::error($msg, $code, $state);
    }

    /**
     * 等同 iPHP::alert,加载语言包
     * 可用于前后台
     *
     * @param string|array $msg
     * @param integer $code
     * @throws sException
     */
    public static function alert($msg, $code = 0)
    {
        self::error($msg, $code, 'alert');
    }

    public static function nil($msg = 'FALSE', $code = '0')
    {
        throw new FalseEx($msg, $code);
    }
}
