<?php

// namespace iPHP\core;

/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
class View
{
    public const TPL_FUNC_NAME = 'FuncClass';
    public const TPL_FUNC_METHOD = 'FuncMethod';
    public const TPL_FLAG_0 = 'iCMS://';
    public const TPL_FLAG_1 = '{iTPL}';
    public const TPL_FLAG_2 = '{DEVICE}';
    public const TPL_FLAG_LEFT = '<!--{';
    public const TPL_FLAG_RIGHT = '}-->';
    public const EXT = '.htm';

    /**
     * @var TemplateLite
     */
    public static $handle = null;
    public static $app = null;
    public static $gateway = null;
    public static $config = [];
    public static $callback = [];
    public static $template = [];

    public static $CACHE = null;
    public static $PATHS = [];
    public static $template_dir = null;
    public static $template_device = null;

    public static function init($config = [])
    {
        self::$config = $config;
        self::$template_dir = $config['template']['tpl'];
        self::$template_device = $config['template']['name'];
        self::$callback = $config['callback'];
        self::template();
    }

    /**
     *
     * @return  TemplateLite
     */
    public static function template()
    {
        $tpl = new TemplateLite();
        $tpl->debugging = iPHP_TPL_DEBUGGING;
        $tpl->template_dir = iPHP_TEMPLATE_DIR;
        $tpl->compile_dir = iPHP_TEMPLATE_CACHE;
        $tpl->reserved_template_varname = iPHP_TEMPLATE_VAR;
        $tpl->reserved_func_name = self::TPL_FUNC_NAME;
        $tpl->reserved_func_method = self::TPL_FUNC_METHOD;
        $tpl->error_reporting_header = "<?php defined('iPHP') OR exit('What are you doing?');error_reporting(iPHP_TEMPLATE_DEBUG?E_ALL & ~E_NOTICE:0);?>\n";
        $tpl->left_delimiter = self::TPL_FLAG_LEFT;
        $tpl->right_delimiter = self::TPL_FLAG_RIGHT;
        $tpl->register_callback('compile', [__CLASS__, 'callback_compile']);
        $tpl->register_callback('resource', [__CLASS__, 'callback_resource']);
        $tpl->register_callback('func', [__CLASS__, 'callback_func']);
        $tpl->register_callback('plugin', [__CLASS__, 'callback_plugin']);
        $tpl->register_callback('block', [__CLASS__, 'callback_block']);
        $tpl->register_callback('register', [__CLASS__, 'callback_register']);
        $tpl->register_callback('output', [__CLASS__, 'callback_output']);
        $tpl->assign('_GET', Request::get());
        $tpl->assign('_POST', Request::post());
        $tpl->assign('_REQUEST', Request::param());
        iPHP_TEMPLATE_DEBUG && $tpl->clear_compiled_tpl();
        self::$handle = $tpl;
        self::receiveTemplate();
        return $tpl;
    }

    public static function set_vars($key, $value)
    {
        self::$handle->$key = $value;
    }

    public static function set_template_dir($dir)
    {
        self::$handle->template_dir = $dir;
    }

    /**
     * callback_register 模板方法注册
     * @param  string $func [方法]
     * @param  [type] $type [类型]
     * @return void
     */
    public static function callback_register($func, $type, $a)
    {
        list($app, $method, $sname) = explode(':', $func);
        $uc_method = $method ? ucfirst($method) : '';
        $typeMap = ['compiler', 'block', 'function', 'output'];
        $suffix = in_array($type, $typeMap) ? 'Tmpl' : 'Func';
        //app/test/testTmplAbc.php >> test:method => testTmplAbc::block_method
        $callback = [
            ucfirst($app) . $suffix . $uc_method,
            $type . ($method ? '_' . $method : '')
        ];

        if (self::hasFile($app, $suffix . $uc_method)) {
            if (class_exists($callback[0]) && method_exists($callback[0], $callback[1])) {
                return implode('::', $callback);
            }
        } else {
            //四种方法选一
            //app/test/testTmpl.php >> test:method => testTmpl::block_method
            //app/test/testTmpl.php >> test:method => testTmpl::function_method
            //app/test/testTmpl.php >> test:method => testTmpl::output_method
            //app/test/testTmpl.php >> test:method => testTmpl::compiler_method

            //app/payment/paymentTmpl.php >> payment:cut => paymentTmpl::block_cut
            $callback[0] = ucfirst($app) . $suffix;
            // var_dump($callback);
            if (self::hasFile($app, $suffix)) {
                if (class_exists($callback[0]) && method_exists($callback[0], $callback[1])) {
                    return implode('::', $callback);
                }
            }
        }
    }

    public static function checkFile($path, $type)
    {
        $flag = isset(self::$CACHE[$path]) ? self::$CACHE[$path] : null;
        if (is_null($flag)) {
            $flag = is_file($path);
            iPHP_DEBUG && iDebug::$DATA['View'][$type][] = $path;
            self::$CACHE[$path] = $flag;
        }
        return $flag;
    }

    public static function hasFile($app, $type = 'func')
    {
        $file = ucfirst($app) . ucfirst($type);
        $path = iPHP::getAppPath($app, $file);
        return self::checkFile($path, $type);
    }

    public static function callback_output(&$content, $file, $tpl)
    {
        if (self::$callback['output']) {
            iPHP::callback(self::$callback['output'], [&$content, $file, $tpl], E_USER_ERROR);
        }
    }

    /**
     * iPHP:test:method
     * iPHP:func
     * iPHP:testApp:method
     * iPHP:testClass:method
     */
    public static function callback_func($params, $tpl)
    {
        try {
            self::run_callback_func($params, $tpl);
        } catch (\Exception $ex) {
            $driver = DB::getDriverName();
            $code = $ex->getCode();
            $msg = $ex->getMessage();
            $msg = Security::filterPath($msg);
            // if($driver=='sqlite'){

            // }
            echo $msg;
        }
    }

    public static function run_callback_func($params, $tpl)
    {
        // var_dump($params);
        isset($params['debug_vars']) && var_dump($params);
        $class = $params[self::TPL_FUNC_NAME];
        $method = $params[self::TPL_FUNC_METHOD];
        (is_array($class) && $class['app']) && $class = $class['app'];
        $assign = $class . ($method ? '_' . $method : '');
        isset($params['as']) && $assign = $params['as'];
        $isMultiArgs = false;
        //模板标签 对应>> 类::静态方法
        if ($method) {
            if (substr($class, -3, 3) === 'App') {
                //app/test/testApp.php
                //iPHP:testApp:method >> testApp::method
                //$testApp_method
                $callFunc = [$class, $method];
                $isMultiArgs = true;
            } elseif (substr($class, -5, 5) === 'Class') {
                //app/test/test.php
                //iPHP:testClass:method >> test::method
                ////$testClass_method
                $callFunc = [substr($class, 0, -5), $method];
                $isMultiArgs = true;
            } else {
                $method == 'list' && $method = 'lists';
                //iPHP:test:method app="aaa" method="bbb" >> aaaFunc::bbb
                // $params['app']     && $TFN = $params['app'];
                // $params['method']  && $TFM = $params['method'];
                //
                //app/test/testFunc.php
                //iPHP:test:method >> testFunc::method
                $callFunc = [$class . 'Func', $method];

                //自定义APP模板调用
                //app/content/contentFunc.php
                //iPHP:content:list app="test" >> contentFunc::list
                //iPHP:test:list >> contentFunc::list
                $apps = self::$config['apps'];
                if ($apps) {
                    // 判断自定义APP app/test/testFunc.php 程序是否存在
                    if (!self::hasFile($class) && $apps[$class]) {
                        $content_func = self::$callback['content'];
                        // 程序不存在,调用 contentFunc::list
                        $params['app'] = $class; //参数必需设置
                        $_callFunc = [$content_func, $method];
                        if (method_exists($_callFunc[0], $_callFunc[1])) {
                            $callFunc = $_callFunc;
                        }
                    }
                }
                //app/test/testFuncMethod.php
                //用户重写 iPHP:test:method 调用 testFuncMethod::method
                self::getReFunc($callFunc, $method);

                //app/test/testFuncMy.php
                //用户重写 iPHP:test:method 调用 testFuncMy::method
                self::getReFunc($callFunc, 'My');
            }
            if (!method_exists($callFunc[0], $callFunc[1]) && strpos($callFunc[1], '__') === false) {
                //app/func/iPHP/test.php
                //iPHP:test:method >> function iPHP_testFunc(){}
                $sysFunc = self::getSystemFunc($class, $callFunc[0], false);
                $sysFunc && $callFunc[0] = $sysFunc;
            }
            if (!method_exists($callFunc[0], $callFunc[1]) && strpos($callFunc[1], '__') === false) {
                if ($params['throw'] === false) {
                    $tpl->assign($assign, []);
                    return;
                }
                $msgMethod = implode('::', $callFunc);
                $msg = sprintf(
                    '<pre>%s</pre>',
                    var_export(iDebug::$DATA['View']['getReFunc'][$callFunc[0]], true)
                );
                self::throwError("{$msg}Unable to find method '{$msgMethod}'", -799);
            }
        } else {
            //app/func/iPHP/test.php
            //iPHP:test >> function iPHP_test(){}
            $callFunc = self::call_func_system($class, $params['run']);
        }
        self::parseVars($params); //解析[]字符
        //合并 参数
        if (isset($params['vars'])) {
            $vars = $params['vars'];
            unset($params['vars'], $vars['loop'], $vars['page']);
            $params = array_merge($params, $vars);
        }
        self::parseExpress($params); //解析表达式字符
        //是否实例化
        $isnew = $params['new'] ? true : false;
        //是否独立参数
        $isMultiArgs = (isset($params['params']) && is_array($params['params']));
        isset($params['debug_func']) && var_dump($callFunc);
        isset($params['args']) && $params = $params['args']; //设置参数
        //获取参数
        isset($params['varsAs']) && $tpl->assign($params['varsAs'], $params);

        self::unvars($params);
        if (is_array($callFunc)) {
            // iPHP:app:_method >> testFunc::method
            // strpos($callFunc[1], '__') !== false && $callFunc[1] = substr($callFunc[1], strpos($callFunc[1], '__') + 2);
            $isnew && $callFunc[0] = iPHP::getInstance($callFunc[0]); //动态方法 iPHP:app:method >> new test() ->method($params);
            $init = [$callFunc[0], 'init'];
            if (method_exists($init[0], $init[1])) {
                iPHP::invoke($init);
            }
        }
        // var_dump($callFunc);
        // var_dump($params);
        if ($callFunc) {
            $args = $isMultiArgs ? $params['params'] : [$params];
            $args[] = &$tpl;
            $response = iPHP::invoke($callFunc, $args);
            if (isset($params['output'])) {
                // var_dump(is_callable($params['output']));
                if ($params['output'] == 'json') {
                    print(json_encode($response));
                } elseif (is_callable($params['output'])) {
                    call_user_func_array($params['output'], [$response]);
                }
            } else {
                if (is_array($response) && $response[0] === true && is_string($response[1])) {
                    echo $response[1];
                } else {
                    $tpl->assign($assign, $response);
                }
            }
        } else {
            self::throwError('Template callback not found', -798);
        }
    }

    public static function getSystemFunc($func, $method = null, $throw = true)
    {
        $func = strtolower($func);
        //app/func/iPHP/test.php
        $path = sprintf('%s/%s/%s.php', iPHP_TEMPLATE_FUN, iPHP_APP, $func);
        $method === null && $method = $func;
        //iPHP_test || iPHP_testFunc
        $callFunc = iPHP_APP . '_' . $method;
        if (!is_file($path)) {
            if ($throw) {
                $msg = sprintf('function [%s] in %s file not found', $callFunc, $path);
                self::throwError($msg, -796);
            }
            return false;
        }
        is_callable($callFunc) or require_once $path;
        return $callFunc;
    }

    public static function call_func_system($func, $run = false, $vars = [])
    {
        //iPHP:test >> function iPHP_test(){}
        //app/func/iPHP/test.php
        $callFunc = self::getSystemFunc($func);
        self::parseVars($vars);
        View::unfuncVars($vars);
        return $run ?
            call_user_func_array($callFunc, [$vars]) :
            $callFunc;
    }

    public static function getReFunc(&$callback = null, $suffix = 'My', $type = 'func')
    {
        //用户重写 iPHP:test:method 调用 testFuncMy::method
        //app/test/testFuncMy.php
        if ($callback) {
            $custom = $callback;
            $custom[0] .= ucfirst($suffix); //testFuncMy
            $pos = strlen($type);
            $app = substr($callback[0], 0, 0 - $pos); //test
            $file = ucfirst($custom[0]); //TestFuncMy
            $path = iPHP::getAppPath($app, $file);

            if (!self::checkFile($path, 'getReFunc')) {
                return false;
            }

            if (method_exists($custom[0], $custom[1])) {
                $callback = $custom;
            }
        }
    }

    public static function callback_plugin($name, $tpl)
    {
        $path = iPHP_TEMPLATE_FUN . '/template/tpl.' . $name;
        if (self::checkFile($path, 'plugin')) {
            return $path;
        }
        return false;
    }

    //模板防下载
    public static function callback_compile($content, $file, $obj)
    {
        return str_replace(iPHP_FILE_HEAD, '', $content);
    }

    /**
     * 模板路径
     * @param  string $tpl [模板路径]
     * @return void
     */
    public static function callback_resource($tpl, $obj)
    {
        // file::dir||asd.htm
        if (strpos($tpl, 'file::') !== false) {
            list($_dir, $tpl) = explode('||', str_replace('file::', '', $tpl));
            if (!Security::safePath($_dir)) {
                self::throwError('file:: template_dir path unsafe', -791);
            }
            if (!Security::safePath($tpl)) {
                self::throwError('file:: tpl path unsafe', -790);
            }
            $obj->template_dir = $_dir;
            return $tpl;
        }
        //./asd.htm
        if (substr($tpl, 0, 2) === './') {
            $dir = dirname($obj->_file);
            $tpl = self::concatPaths($dir, $tpl);
        }

        self::$PATHS = [];
        $rtpl = self::tplExist($tpl);
        if ($rtpl === false) {
            $errorMsg = sprintf('<pre>%s</pre>Unable to find the template file <b>"%s"</b>', implode('<br />', Security::filterPath(self::$PATHS)), $tpl);
            self::throwError($errorMsg, -795);
        }
        try {
            File::check($rtpl);
        } catch (\sException $ex) {
            self::throwError('template file [' . $rtpl . '] check failed', -794);
        }
        return $rtpl;
    }

    public static function concatPaths($basePath, $newPath)
    {
        $path = rtrim($basePath, '/') . '/' . ltrim($newPath, './');
        return $path;
    }

    public static function withApp($newApp, callable $callback)
    {
        $oldApp = self::$app; // 保存当前的 app 值
        try {
            self::$app = $newApp; // 设置新的 app 值
            return $callback();   // 执行回调逻辑
        } finally {
            self::$app = $oldApp; // 确保无论是否发生异常，都恢复原始值
        }
    }

    public static function tplExist($path)
    {
        $sss = iPHP_APP . ':/';
        $tpl = $path;
        // self::$PATHS[] = $path;
        //iPHP://user/test.htm
        strpos($path, $sss) !== false && $flag = $sss;
        //{iTPL}/test.htm
        strpos($path, self::TPL_FLAG_1) !== false && $flag = self::TPL_FLAG_1;
        if ($flag) {
            // 模板名/$path
            if ($tpl = self::checkTplPath($path, self::$template_dir, $flag)) {
                return $tpl;
            }
            if (self::$app) {
                // testApp/$path
                $appDir = self::$app . 'App';
                $_path = str_replace(self::$app . '.', '', $path);
                $_path = str_replace(self::$app . '/', '', $path);
                if ($tpl = self::checkTplPath($_path, $appDir, $flag)) {
                    return $tpl;
                }
                // test/$path
                $appDir = self::$app;
                if ($tpl = self::checkTplPath($_path, $appDir, $flag)) {
                    return $tpl;
                }
            }
            //$path
            if ($tpl = self::checkTplPath($path, '', $flag)) {
                return $tpl;
            }
            // iPHP/设备名/$path
            if ($tpl = self::checkTplPath($path, iPHP_APP . '/' . self::$template_device, $flag)) {
                return $tpl;
            }
            if (self::$app) {
                // iPHP/testApp/$path
                $appDir = self::$app . 'App';

                $_path = str_replace(self::$app . '.', '', $path);
                if ($tpl = self::checkTplPath($_path, iPHP_APP . '/' . $appDir, $flag)) {
                    return $tpl;
                }
                // iPHP/test/$path
                $appDir = self::$app;
                if ($tpl = self::checkTplPath($_path, iPHP_APP . '/' . $appDir, $flag)) {
                    return $tpl;
                }
            }
            // iPHP/$path
            if ($tpl = self::checkTplPath($path, iPHP_APP, $flag)) {
                return $tpl;
            }
            // // 其它移动设备$path
            // if(iPHP_MOBILE){
            //     // iPHP/mobile/$path
            //     if ($tpl = self::checkTplPath($path, iPHP_APP.'/mobile')) {
            //         return $tpl;
            //     }
            // }
            // var_dump($flag, self::$template_dir, $path);
            $tpl = str_replace($flag, self::$template_dir, $path);
            // return self::checkTplPath($path, self::$template_dir);
        }
        if (strpos($tpl, self::TPL_FLAG_2) !== false) {
            $tpl = str_replace(self::TPL_FLAG_2, self::$template_device, $tpl);
        }
        $path = rtrim(self::$handle->template_dir, '/') . '/' . ltrim($tpl, '/');
        if (!Security::safePath($path)) {
            self::throwError('Template path unsafe', -792);
        }
        self::$PATHS[] = $path;
        if (is_file($path)) {
            return $tpl;
        } else {
            return false;
        }
    }

    public static function checkTplPath($tpl, $dir = '', $flag = null)
    {
        // self::$PATHS[] = $tpl;
        $flag === null && $flag = iPHP_APP . ':/';
        $ntpl = str_replace($flag, '', $tpl);
        $ntpl = rtrim($dir, '/') . '/' . ltrim($ntpl, '/');
        $ntpl = ltrim($ntpl, '/');
        $tdir = rtrim(self::$handle->template_dir, '/');
        $path = $tdir . '/' . $ntpl;
        self::$PATHS[] = $path;
        if (is_file($path)) {
            return $ntpl;
        }
        return false;
    }

    public static function checkDir($name)
    {
        $dir = rtrim(self::$handle->template_dir, '/') . '/' . $name;
        if (is_dir($dir)) {
            return $dir;
        }
        return false;
    }

    public static function unvars(&$args)
    {
        unset($args[self::TPL_FUNC_NAME], $args[self::TPL_FUNC_METHOD]);
    }

    public static function unfuncVars(&$vars)
    {
        unset($vars[View::TPL_FUNC_NAME]);
    }

    public static function get($key = null)
    {
        return self::$handle->get_template_vars($key);
    }

    public static function getGeneric()
    {
        return self::$handle->generic;
    }

    public static function getParams($key = null)
    {
        $view = self::get($key);
        $app = $view['APP']['app'];
        return $view[$app]['params'] ?: [];
    }

    public static function generic($value = null, $key = null, $append = false)
    {
        if (is_array($value) && $key === null) {
            self::$handle->generic = array_merge((array)self::$handle->generic, $value);
        } else {
            $vars = &self::$handle->generic[$key];
            if ($append) {
                if (is_array($value)) {
                    $vars = array_merge($vars, $value);
                } else {
                    $vars .= $value;
                }
            } else {
                $vars = $value;
            }
        }
    }

    //解析模板中参数 []字符 成数组
    public static function parseVars(&$params)
    {
        $array = [];
        foreach ((array) $params as $ak => $av) {
            $ak = trim($ak);
            if (strpos($ak, '[') !== false && substr($ak, -1) == ']') {
                unset($params[$ak]);
                $pa = make_multi_array($ak, $av, '[');
                $array = array_replace_recursive((array) $array, (array) $pa);
            }
        }
        // $params = array_merge((array) $params, (array) $array);
        $params = array_plus_recursive((array) $params, (array) $array);
        // var_dump($params);
        return $params;
    }

    //解析模板参数表达式字符
    //!: 不等于 <: 小于等于 >: 大于等于
    public static function parseExpress(&$params)
    {
        $expArray = explode(',', '<,>,!:,<:,>:');
        foreach ($params as $key => $value) {
            $nkey = null;
            $exp = substr($key, -1);
            if (in_array($exp, $expArray)) {
                $nkey = substr($key, 0, -1);
            } else {
                $exp = substr($key, -2);
                if (in_array($exp, $expArray)) {
                    $exp = str_replace(':', '=', $exp);
                    $nkey = substr($key, 0, -2);
                }
            }
            if ($nkey) {
                $params[$nkey] = [$exp, $value];
                unset($params[$key]);
            }
        }
    }

    public static function clearTpl($file = null)
    {
        self::$handle or self::init();
        self::$handle->clear_compiled_tpl($file);
    }

    public static function value($key, $value = null)
    {
        self::$handle->assign($key, $value);
    }

    public static function assign($key, $value = null)
    {
        self::$handle->assign($key, $value);
    }

    public static function append($key, $value = null, $merge = false)
    {
        self::$handle->append($key, $value, $merge);
    }

    public static function clear($key)
    {
        self::$handle->clear_assign($key);
    }

    public static function display($name)
    {
        self::$handle or self::template();
        self::fetched($name, true);
    }

    public static function fetch($name)
    {
        self::$handle or self::template();
        return self::fetched($name);
    }

    public static function fetched($name, $flag = false)
    {
        return self::$handle->fetch($name, $flag);
    }

    /**
     * 渲染模板
     *
     * @param string 模板路径，/template/目录下的模板
     * @param string $app
     * @param array $route
     * @return void
     */
    public static function render($file, $app = 'index', $route = [])
    {
        $file or self::throwError('Please set the template file or update node cache', -793);
        $app && self::$app = $app;
        self::receiveTheme($file);
        $route && View::generic($route, 'route');

        if (self::$gateway == 'html') {
            return [self::fetched($file), $route];
        } else {
            self::fetched($file, true);
            iDebug::info($file);
        }
    }

    public static function clearCookie($key = null)
    {
        if ($key) {
            Cookie::del('@' . $key);
        } else {
            Cookie::del('@template');
            Cookie::del('@theme');
        }
    }

    /**
     * 接收URL设置的模板目录 @template=ooxx
     * 接收Cookie设置的模板目录 @template=ooxx
     *
     * @return void
     */
    public static function receiveTemplate()
    {
        try {
            // Cookie::set('@template','blogX');
            $template = Request::param('@template');
            // var_dump($template);
            // empty($template) && $template = Cookie::get('@template');
            if ($template) {
                $template = File::escapeDir(ltrim($template, '/'));
                $tdir = rtrim(self::$handle->template_dir, '/');
                $path = $tdir . '/' . $template;
                File::check($path);
                is_dir($path) && self::$template_dir = $template;
            }
        } catch (\sException $ex) {
            //throw $th;
        }
    }

    /**
     * 接收URL设置的模板文件 @theme=ooxx/aaa
     * 接收Cookie设置的模板文件 @theme=ooxx/aaa
     *
     * @param [type] $name
     * @param [type] $theme
     * @return void
     */
    public static function receiveTheme(&$name, $theme = null)
    {
        try {
            $theme === null && $theme = Request::param('@theme');
            // empty($theme) && $theme = Cookie::get('@theme');
            if ($theme) {
                $theme .= self::EXT;
                $theme = File::escapeDir(ltrim($theme, '/'));
                $dir = self::$template_dir;
                File::check($theme);
                if (self::checkTplPath($theme, $dir)) {
                    $name = sprintf('%s/%s', View::TPL_FLAG_1, $theme);
                }
            }
        } catch (\sException $ex) {
            //throw $th;
        }
    }

    public static function throwError($msg, $code = null)
    {
        throw new ViewEx($msg, $code);
    }
}
class ViewEx extends sException
{
}
