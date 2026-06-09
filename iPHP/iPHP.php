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
// namespace iPHP;

// use iPHP\core\Request;

class iPHP
{
    public static $RESERVED = ['API', 'DONE', 'DO', 'MY'];

    public static $handler = [
        'autoload'  => ['iPHP',   'autoloadHandler'],
        'shutdown'  => ['iDebug', 'shutdownHandler'],
        'exception' => ['iDebug', 'exceptionHandler'],
        'error'     => ['iDebug', 'errorHandler'],
    ];

    public static function bootstrap()
    {
        Helper::timerStart();
        self::composer();
        self::start();
        self::setMemoryLimit();
        self::setHandler();
        Request::bootstrap();
        Security::bootstrap();
        iDefine::bootstrap();
        Request::route();
    }

    public static function start()
    {
        @ob_start();
        @ini_set('magic_quotes_sybase', 0);
        @ini_set('magic_quotes_runtime', 0);
        @ini_set('magic_quotes_gpc', 0);
        @header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
    }

    /**
     * 设置PHP内存限制
     * 如果当前内存限制小于指定的值，则尝试增加内存限制。
     */
    public static function setMemoryLimit()
    {
        if (function_exists('ini_get')) {
            $memorylimit = @ini_get('memory_limit');
            if ($memorylimit && get_bytes($memorylimit) < 134217728 && function_exists('ini_set')) {
                ini_set('memory_limit', iPHP_MEMORY_LIMIT);
            }
        }
    }

    public static function setHandler()
    {
        date_default_timezone_set(iPHP_TIME_ZONE);
        // 定义PHP程序执行完成后执行的函数
        register_shutdown_function(self::$handler['shutdown']);
        // 设置一个用户定义的错误处理函数
        set_error_handler(self::$handler['error'], E_ALL & ~E_DEPRECATED & ~E_NOTICE);
        // 自定义异常处理。
        set_exception_handler(self::$handler['exception']);
        // 设置一个用户定义的自动加载函数
        spl_autoload_register(self::$handler['autoload'], true);
    }

    public static function autoloadHandler($name)
    {
        try {
            self::autoload($name);
            if (class_exists($name)) {
                return true;
            }
        } catch (\Exception $ex) {
            $autoload_finish = true;
            //自动载入失败 检测其它 autoload 并注册
            $functions = spl_autoload_functions();
            if ($functions) {
                foreach ($functions as $key => $autoload) {
                    if ($autoload != iPHP::$handler['autoload']) {
                        $autoload_finish = false;
                        spl_autoload_register($autoload);
                    }
                }
            }
            defined('iPHP_DEBUG') or define('iPHP_DEBUG', true);
            if (iPHP_DEBUG) {
                if (!$autoload_finish) {
                    //存在 其它 autoload 不报错
                    return false;
                }
                $path = $ex->getMessage();
                $msg = sprintf('Unable to load Class/Function "%s",in paths "%s"', $name, $path);
                self::error($msg, -999);
            } else {
                return false;
            }
        }
    }

    public static function autoload($name)
    {
        $PREFIX = self::$RESERVED;
        $paths = [];
        if (strpos($name, '\\') !== false) {
            //namespace aaa\bbb
            $space = str_replace('\\', '/', $name);
            //app\xxx\xxx
            if (strpos($name, 'app\\') === 0) {
                $path = iPHP_APP_PATH . $space . '.php';
            } else {
                // vendor/aaa/bbb.php
                $path = iPHP_COMPOSER_DIR . '/' . $space . '.php';
                if (defined('iPHP_VENDOR_DIR')) {
                    $path = iPHP_VENDOR_DIR . '/' . $space . '.php';
                }
            }
            $paths[] = $path;
        } else {
            $file = ucfirst($name);
            $path = '';
            if (self::isCoreClass($name)) {
                // iPHP/core/ooxx.php
                $path = iPHP_CORE . '/' . $name . '.php';
                $paths[] = $path;
            } else {
                $file = ucfirst($name);
                //test_aaa => test/test_aaa.class.php >> class test_aaa{}
                if (strpos($name, '_') !== false) {
                    list($a, $b) = explode('_', $name);
                    if (!in_array($a, $PREFIX)) {
                        $file = ucfirst($name);
                        $name = $a;
                    }
                }
                //test => test/test.class.php >> class test{}
                $path = self::getAppPath($name, $file);
                $paths[] = $path;
                if (empty($path) or !is_file($path)) {
                    //aaaTest 		=> aaa/AaaTest.php 		>> class aaaTest{}
                    //AaaTest 		=> aaa/AaaTest.php 		>> class AaaTest{}
                    //AaaTestBbbb 	=> aaa/AaaTestBbbb.php  >> class AaaTestBbbb{}
                    $pieces = preg_split('/(?<=\w)(?=[A-Z])/', $name);
                    // var_dump($pieces);
                    $c = count($pieces);
                    if ($c > 1) {
                        $name = $pieces[0];
                        $file = implode('', array_map('ucfirst', $pieces)); //大驼峰
                        // $file = strtolower(implode('.', $pieces));//随意模式
                        // app/aaa/AaaTest.php
                        $path = self::getAppPath($name, $file);
                        // var_dump($path);
                        $paths[] = $path;
                        if (!is_file($path)) {
                            //core/AaaTest.php
                            $path = iPHP_APP_CORE . '/' . $file . '.php';
                            $paths[] = $path;
                            if (!is_file($path)) {
                                self::error(implode(' Or ', $paths), -998);
                            }
                        }
                        // is_file($path) or $path = $core;
                    }
                }
            }
        }
        if (is_file($path)) {
            require_once $path;
        } else {
            self::error(implode(' Or ', $paths), -997);
        }
    }

    // 判断是否是核心类
    private static function isCoreClass($name)
    {
        $coreClasses = explode(',', iPHP_CORE_CLASS);
        return in_array($name, $coreClasses);
    }

    public static $instance = [];

    /**
     * 获得类的对象实例
     * @param string $className
     * @param mixed $rc
     * @return mixed
     */
    public static function getInstance($className, &$rc = null)
    {
        // self::$instance && $instance = self::$instance[$className];
        // if (!$instance) {
        try {
            // 获取类的反射对象
            $rc = new ReflectionClass($className);
            // 检查类是否有构造函数
            if ($rc->hasMethod('__construct')) {
                // 获取构造函数的参数
                $paramArr = self::getMethodParams($className);
                // 创建类的实例
                $instance = $rc->newInstanceArgs($paramArr);
            } else {
                // 如果没有构造函数，则创建没有构造函数的实例
                $instance = $rc->newInstanceWithoutConstructor();
            }

            // 将实例缓存到静态属性中
            self::$instance[$className] = $instance;

            // 返回实例
            return $instance;
        } catch (ReflectionException $e) {
            // 处理类不存在的异常
            throw new Exception("Class {$className} does not exist or cannot be instantiated.");
        } catch (\Exception $ex) {
            // 处理其他可能的异常
            throw $ex;
        }
        // 	self::$instance[$className] = $instance;
        // }
    }

    /**
     * 执行类的方法
     *
     * @param string|array $call      类名和方法名的组合，格式为 "ClassName::methodName" 或 [ClassName, methodName]
     * @param array        $params    传递给方法的额外参数
     * @return mixed       方法的返回值
     * @throws Exception   如果方法不存在或无法调用
     */
    public static function invoke($call, $params = [])
    {
        // 如果 $call 是字符串，并且包含 "::"，则将其转换为数组格式
        if (is_string($call) && strpos($call, '::') !== false) {
            $call = explode('::', $call, 2);
        }

        // 确保 $call 是数组格式
        if (is_array($call) && count($call) === 2) {
            list($className, $methodName) = $call;
            $instance = self::getInstance($className);
            $paramArr = self::getMethodParams($className, $methodName, $params);
            $callable = [$instance, $methodName];
        } elseif (is_callable($call)) {
            // 如果直接传入了一个可调用的函数或闭包，则直接使用
            $callable = $call;
            $paramArr = $params;
        } else {
            throw new Exception("Invalid call format. Expected 'ClassName::methodName' or callable.");
        }

        return call_user_func_array($callable, (array)$paramArr);
    }

    /**
     * 获得类的方法参数，只获得有类型的参数
     *
     * @param  string $className   类名
     * @param  string $methodName  方法名，默认为 '__construct'
     * @param  array  $paramData   用于设置参数默认值的数组
     * @return array  返回包含参数名称和对应值的数组
     */
    public static function &getMethodParams($className, $methodName = '__construct', $paramData = [])
    {
        $paramArr = [];

        // 通过反射获得该类
        $class = new ReflectionClass($className);

        // 判断该类是否有指定的方法
        if ($class->hasMethod($methodName)) {
            // 获得方法
            $method = $class->getMethod($methodName);

            // 判断方法是否有参数
            $params = $method->getParameters();

            if (count($params) > 0) {
                foreach ($params as $key => $param) {
                    $paramClass = null;
                    $default = null;

                    // 判断参数是否有默认值
                    if ($param->isOptional()) {
                        $default = $param->getDefaultValue();
                    }

                    // 获取参数类型
                    $paramClass = self::getParamType($param);

                    // 获取参数值
                    $value = isset($paramData[$key]) ? $paramData[$key] : $default;

                    // 处理参数类型
                    $value = self::handleParamType($paramClass, $value);

                    // 处理引用传递
                    $paramKey = $param->isPassedByReference() ? 'ref:' . $key : $key;
                    $paramArr[$paramKey] = $value;
                }
            }
        }

        return $paramArr;
    }

    /**
     * 获取参数类型
     *
     * @param  ReflectionParameter $param 反射参数对象
     * @return string|null 参数类型，如果不存在则返回 null
     */
    private static function getParamType(ReflectionParameter $param)
    {
        if (version_compare(PHP_VERSION, '7.0', '>=')) {
            $reflectionType = $param->getType();
            return $reflectionType instanceof ReflectionNamedType ? $reflectionType->getName() : null;
        } else {
            $reflectionClass = $param->getClass();
            return $reflectionClass instanceof ReflectionClass ? $reflectionClass->getName() : null;
        }
    }

    /**
     * 处理参数类型
     *
     * @param  string|null $paramClass 参数类型
     * @param  mixed       $value      参数值
     * @return mixed       处理后的参数值
     */
    private static function handleParamType($paramClass, $value)
    {
        if ($paramClass) {
            $args = self::getMethodParams($paramClass);
            if (isset($value)) {
                $args[0] = $value;
            }

            $rc = new ReflectionClass($paramClass);

            if ($rc->hasMethod('__construct')) {
                $value = $rc->newInstanceArgs($args);
            } else {
                $value = $rc->newInstanceWithoutConstructor();
            }
        }

        return $value;
    }

    public static function composer()
    {
        if (isset($GLOBALS['composer'])) {
            return;
        }
        $dir = iPHP_COMPOSER_DIR . '/composer';
        if (file_exists($dir)) {
            $path = iPHP_COMPOSER_DIR . '/autoload.php';
            if (is_file($path)) {
                $GLOBALS['composer'] = true;
                require_once $path;
            }
        }
    }

    public static function getAppPath($app, $name = null)
    {
        $app = strtolower($app);
        $basePath = iPHP_APP_DIR . '/' .$app . '/';

        if ($name === null || $name === '') {
            return $basePath;
        }

        $version = Request::$VERSIONS[$app];
        if ($version) {
            //app/aa/v1.0/
            $basePathVer = $basePath .$version . '/';
            if(is_dir($basePathVer)){
                //app/aa/v1.0/aaaXxx.php
                return self::getPath($basePathVer, $name);
            }
        }
        //app/aa/aaaXxx.php
        return self::getPath($basePath, $name);
    }
    public static function getPath($base, $name)
    {
        //app/aa/aaaXxx.php
        $path = $base . $name . '.php';

        if (is_file($path)) {
            return $path;
        }

        $isApp = substr($name, -3) === 'App';
        $isModel = substr($name, -5) === 'Model';

        if ($isModel) {
            //app/aa/model/aaaModel.php
            $path = $base .  'model/' . $name . '.php';
            if (is_file($path)) {
                return $path;
            }
        } elseif ($isApp) {
            //app/aa/app/aaaApp.php
            $path = $base .  'app/' . $name . '.php';
            if (is_file($path)) {
                return $path;
            }
        }
        return $path;
    }
    /**
     * callback 回调执行
     * @param  [type] $callback [执行函数]
     * @param  [type] $params   [参数]
     * @param  function|string|boolean $exCall   抛出错误后回调，默认返回$exCall设置值
     * @return mixed
     */
    public static function callback($callback, $params = null, $exCall = null)
    {
        if (empty($callback)) {
            return;
        }

        if (is_array($callback) && !is_callable($callback)) {
            $res = [];
            foreach ($callback as $key => $call) {
                if (is_callable($call)) {
                    $res[$key] = self::callback($call, $params, $exCall);
                }
            }
            return $res;
        }

        try {
            if (is_array($callback)) {
                $isObj = is_object($callback[0]);
                if (!$isObj) {
                    $class = new ReflectionClass($callback[0]);
                    $method = $class->getMethod($callback[1]);
                    if (!$method->isStatic()) { //非静态方法调用
                        $callback[0] = new $callback[0]();
                    }
                }
            }
            if (!is_callable($callback)) {
                throw new sException('Not callable');
            }

            return call_user_func_array($callback, (array)$params);
        } catch (\Exception $ex) {
            if (is_callable($exCall)) {
                return call_user_func_array($exCall, [$ex]);
            } elseif ($exCall === E_USER_ERROR) {
                throw $ex;
            } else {
                return $exCall;
            }
        }
    }

    public static function isCallback($params)
    {
        if (is_array($params) && count($params) == 2) {
            $params = array_values($params);
            if ((!is_string($params[0]) && !is_object($params[0])) || (is_string($params[0]) && !class_exists($params[0]))) {
                return false;
            }
            $isObj = is_object($params[0]);
            $class = new ReflectionClass($isObj ? get_class($params[0]) : $params[0]);
            if ($class->isAbstract()) {
                return false;
            }
            try {
                $method = $class->getMethod($params[1]);
                if (!$method->isPublic() || $method->isAbstract()) {
                    return false;
                }
                if (!$isObj && !$method->isStatic()) {
                    return false;
                }
            } catch (ReflectionException $e) {
                return false;
            }
            return true;
        } elseif (is_string($params) && function_exists($params)) {
            return true;
        }
        return false;
    }

    /**
     * 错误提示对话框 iCMS.ui.error
     *
     * @param string $msg
     * @param integer $code
     * @param string $state
     * @return void
     */
    public static function error($msg, $code = -99, $state = 'error')
    {
        $ex = new sException((string)$msg, $code);
        $ex->setState($state);
        throw $ex;
    }

    /**
     * 警告提示对话框  iCMS.ui.alert
     *
     * @param string $msg
     * @param integer $code
     * @param string $state
     * @return void
     */
    public static function alert($msg, $code = -1, $state = 'alert')
    {
        self::error($msg, $code, $state);
    }

    /**
     * 获取异常
     * @param sException $ex
     * @return Array
     */
    public static function getException($ex)
    {
        if ($ex instanceof PDOException) {
            iDebug::$SHOW_ARGS = false;
        }

        $code = $state = $ex->getCode();
        $message = Security::filterPath($ex->getMessage());
        $backtrace = $ex->getTrace();
        $trace = iDebug::backtraceHtml($backtrace);
        $trace = Security::filterPath($trace);
        $line = $ex->getLine();
        $file = Security::filterPath($ex->getFile());
        // $trace = Security::filterPath($ex->getTraceAsString());

        if ($ex instanceof sException) {
            $state = $ex->getState();
        }
        is_null($code) && $code = -1;
        // $message = Lang::get($message);
        return compact('code', 'message', 'trace', 'line', 'file', 'state');
    }
    public static function log($msg, $name = null)
    {
        Utils::log($msg, $name);
    }
}
