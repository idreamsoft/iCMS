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
    public static $handle   = NULL;
    public static $app      = null;
    public static $gateway  = null;
    public static $config   = array();
    public static $template = array();
    const TPL_FUNC_NAME   = 'FuncClass';
    const TPL_FUNC_METHOD = 'FuncMethod';
    const TPL_FLAG_1 = '{iTPL}';
    const TPL_FLAG_2 = '{DEVICE}';
    const TPL_FLAG_LEFT = '<!--{';
    const TPL_FLAG_RIGHT = '}-->';

    public static $CACHE = null;
    public static $PATHS = [];
    public static $EXT = '.htm';

    public static function init($config = array())
    {
        self::$config = $config;
        self::$handle = self::Template();
        self::$handle->assign('_GET', Request::get());
        self::$handle->assign('_POST', Request::post());
        self::$handle->assign('_REQUEST', Request::param());
        self::receiveTemplate();
        iPHP_TPL_DEBUG && self::$handle->clear_compiled_tpl();
    }
    /**
     * [Template description]
     *
     * @return  TemplateLite
     */
    public static function Template()
    {
        $tpl = new TemplateLite();
        $tpl->debugging    = iPHP_TPL_DEBUGGING;
        $tpl->template_dir = iPHP_TPL_DIR;
        $tpl->compile_dir  = iPHP_TPL_CACHE;
        $tpl->reserved_template_varname = iPHP_TPL_VAR;
        $tpl->reserved_func_name        = self::TPL_FUNC_NAME;
        $tpl->reserved_func_method      = self::TPL_FUNC_METHOD;
        $tpl->error_reporting_header    = "<?php defined('iPHP') OR exit('What are you doing?');error_reporting(iPHP_TPL_DEBUG?E_ALL & ~E_NOTICE:0);?>\n";
        $tpl->left_delimiter  = self::TPL_FLAG_LEFT;
        $tpl->right_delimiter = self::TPL_FLAG_RIGHT;
        $tpl->register_modifier("date", "get_date");
        $tpl->register_modifier("htmlcut", "htmlcut");
        $tpl->register_modifier("cut", ['iString', 'cut']);
        $tpl->register_modifier("cnlen", ['iString', 'strlen']);
        $tpl->register_modifier("html2txt", "html2text");
        $tpl->register_modifier("key2num", "key2num");
        $tpl->register_modifier("unicode", "get_unicode");
        $tpl->register_modifier("random", "random");
        $tpl->register_modifier("fields", "select_fields");
        $tpl->register_modifier("htmldecode", 'htmlspecialchars_decode');
        $tpl->register_modifier("pinyin", ["Pinyin", "get"]);
        $tpl->register_modifier("thumb", ["FilesPic", "thumb"]);
        $tpl->register_callback("compile", [__CLASS__, "callback_compile"]);
        $tpl->register_callback("resource", [__CLASS__, "callback_resource"]);
        $tpl->register_callback("func", [__CLASS__, "callback_func"]);
        $tpl->register_callback("plugin", [__CLASS__, "callback_plugin"]);
        $tpl->register_callback("block", [__CLASS__, "callback_block"]);
        $tpl->register_callback("register", [__CLASS__, "callback_register"]);
        $tpl->register_callback("output", [__CLASS__, "callback_output"]);
        $tpl->register_block("cache", [__CLASS__, "block_cache"]);
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
     * [callback_register 模板方法注册]
     * @param  string $func [方法]
     * @param  [type] $type [类型]
     * @return [type]       [description]
     */
    public static function callback_register($func, $type, $a)
    {
        list($app, $method) = explode(':', $func);
        $typeMap  = array('compiler', 'block', 'function', 'output');
        $suffix    = in_array($type, $typeMap) ? 'Tmpl' : 'Func';
        //app/test/testTmplAbc.php >> test:method => testTmplAbc::block_method
        $callback = array(
            ucfirst($app) . $suffix . ucfirst($method),
            $type . ($method ? '_' . $method : '')
        );
        if (self::hasFile($app, $suffix . ucfirst($method))) {
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
    public static function hasFile($app, $type = 'func')
    {
        $file = ucfirst($app) . ucfirst($type);
        $path = iAPP::path($app, $file);
        iDebug::$DATA['View'][$type][] = $path;
        return is_file($path);
    }
    public static function callback_output(&$content)
    {
        if (self::$config['callback']['output']) {
            iPHP::callback(self::$config['callback']['output'], array(&$content));
        }
    }
    public static function unvars(&$args)
    {
        unset($args[self::TPL_FUNC_NAME], $args[self::TPL_FUNC_METHOD]);
    }
    /**
     * iPHP:test:method
     * iPHP:func
     * iPHP:testApp:method
     * iPHP:testClass:method
     */
    public static function callback_func($params, $tpl)
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
                $callFunc = array($class, $method);
                $isMultiArgs = true;
            } else if (substr($class, -5, 5) === 'Class') {
                //app/test/test.php
                //iPHP:testClass:method >> test::method
                ////$testClass_method
                $callFunc = array(substr($class, 0, -5), $method);
                $isMultiArgs = true;
            } else {
                $method == 'list' && $method = 'lists';
                //iPHP:test:method app="aaa" method="bbb" >> aaaFunc::bbb
                // $params['app']     && $TFN = $params['app'];
                // $params['method']  && $TFM = $params['method'];
                //
                //app/test/testFunc.php
                //iPHP:test:method >> testFunc::method
                $callFunc = array($class . 'Func', $method);

                //自定义APP模板调用
                //app/content/contentFunc.php
                //iPHP:content:list app="test" >> contentFunc::list
                //iPHP:test:list >> contentFunc::list
                if (self::$config['define']) {
                    $apps = self::$config['define']['apps'];
                    $func = self::$config['define']['func'];
                    // 判断自定义APP app/test/testFunc.php 程序是否存在
                    if (!self::hasFile($class) && $apps[$class]) {
                        // 程序不存在调用 contentFunc::list
                        $params['app'] = $class; //参数必需设置
                        $_callFunc = array($func . 'Func', $method);
                        if (method_exists($callFunc[0], $callFunc[1])) {
                            $callFunc = $_callFunc;
                        }
                    }
                }
                //app/test/testFuncMethod.php
                //用户重写 iPHP:test:method 调用 testFuncMethod::method
                self::getUserFunc($callFunc, $method);

                //app/test/testFuncMy.php
                //用户重写 iPHP:test:method 调用 testFuncMy::method
                self::getUserFunc($callFunc, 'My');
            }
            if (!method_exists($callFunc[0], $callFunc[1]) && strpos($callFunc[1], '__') === false) {
                $msgMethod = implode("::", $callFunc);
                $msg = sprintf(
                    '<pre>%s</pre>',
                    var_export(iDebug::$DATA['View']['getUserFunc'][$callFunc[0]], true)
                );
                self::throwError("{$msg}Unable to find method '{$msgMethod}'");
            }
        } else {
            //app/func/iPHP/iPHP.test.php
            //iPHP:test >> function iPHP_test(){}
            $callFunc = self::call_func_system($class, $params['run']);
        }
        //合并 参数
        if (isset($params['vars'])) {
            $vars = $params['vars'];
            unset($params['vars'], $vars['loop'], $vars['page']);
            $params = array_merge($params, $vars);
        }
        self::parseVars($params); //解析[]字符
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
        }
        // var_dump($callFunc);
        // var_dump($params);
        if ($callFunc) {
            $response = call_user_func_array(
                $callFunc,
                $isMultiArgs ? $params['params'] : array($params)
            );
            $tpl->assign($assign, $response);
        } else {
            self::throwError('Template callback not found');
        }
    }
    public static function call_func_system($func, $run = false, $vars = array())
    {
        //iPHP:test >> function iPHP_test(){}
        //app/func/iPHP/test.php
        $path = sprintf('%s/%s/%s.php', iPHP_TPL_FUN, iPHP_APP, $func);
        $callback  = iPHP_APP . '_' . $func;
        if (!is_file($path)) {
            $msg = sprintf('function [%s] in %s file not found', $callback, $path);
            self::throwError($msg);
            return false;
        }

        function_exists($callback) or require_once($path);
        return $run ?
            call_user_func_array($callback, array($vars)) :
            $callback;
    }
    public static function getUserFunc(&$callback = null, $suffix = 'My', $type = 'func')
    {
        //用户重写 iPHP:test:method 调用 testFuncMy::method
        //app/test/testFuncMy.php
        if ($callback) {
            $custom = $callback;
            $custom[0] .= ucfirst($suffix); //testFuncMy
            $pos   = strlen($type);
            $app   = substr($callback[0], 0, 0 - $pos); //test
            $file  = ucfirst($custom[0]); //TestFuncMy
            $path  = iAPP::path($app, $file);

            iDebug::$DATA['View']['getUserFunc'][$callback[0]][] = [$custom, $path];

            if (!is_file($path)) return false;

            if (method_exists($custom[0], $custom[1])) {
                $callback = $custom;
            }
        }
    }
    public static function callback_plugin($name, $tpl)
    {
        $path = iPHP_TPL_FUN . "/template/tpl." . $name;
        iDebug::$DATA['View']['plugin'][] = $path;
        if (is_file($path)) {
            return $path;
        }
        return false;
    }
    public static function block_cache($vars, &$content, $tpl)
    {
        $vars['id'] or Script::warning('cache 标签出错! 缺少"id"属性或"id"值为空.');
        $cache_time = isset($vars['time']) ? (int) $vars['time'] : -1;
        $cache_name = self::$config['template']['device'] . '/block_cache/' . $vars['id'];
        $_content   = Cache::get($cache_name);

        if ($_content === false) {
            Cache::set($cache_name, $content, $cache_time);
        } else {
            $content = $_content;
        }
        if ($vars['assign']) {
            $tpl->assign($vars['assign'], $content);
            return false;
        }
        return true;
    }
    //模板防下载
    public static function callback_compile($content, $file, $obj)
    {
        return str_replace(iPHP_FILE_HEAD, '', $content);
    }
    /**
     * 模板路径
     * @param  string $tpl [模板路径]
     * @return [type]      [description]
     */
    public static function callback_resource($tpl, $obj)
    {
        $tpl = ltrim($tpl, '/');
        // file::dir||asd.htm
        if (strpos($tpl, 'file::') !== false) {
            list($_dir, $tpl)   = explode('||', str_replace('file::', '', $tpl));
            $obj->template_dir = $_dir;
            return $tpl;
        }
        //./asd.htm
        if (substr($tpl, 0, 2) === './') {
            $dir = dirname($obj->_file) . '/';
            if (strpos($dir, iPHP_APP) !== false) $dir .= '/';
            $tpl = str_replace('./', $dir, $tpl);
        }
        self::$PATHS = [];
        $rtpl = self::tplExist($tpl, $_tpl);
        if ($rtpl === false) {
            $errorMsg = sprintf('<pre>%s</pre>Unable to find the template file <b>"%s"</b>', implode('<br />', self::$PATHS), $tpl);
            self::throwError($errorMsg, '002');
        }
        try {
            File::check($rtpl);
        } catch (\sException $ex) {
            self::throwError('template file [' . $rtpl . '] check failed', '003');
        }
        return $rtpl;
    }
    public static function tplExist($path, &$tpl = null)
    {
        $sss = iPHP_APP . ':/';
        $tpl = $path;
        //iPHP://user/test.htm
        strpos($path, $sss) !== false && $flag = $sss;
        //{iTPL}/test.htm
        strpos($path, self::TPL_FLAG_1) !== false   && $flag = self::TPL_FLAG_1;
        if ($flag) {
            // 模板名/$path
            if ($tpl = self::checkTplPath($path, self::$config['template']['dir'], $flag)) {
                return $tpl;
            }
            // testApp/$path
            if (self::$app) {
                $appDir = self::$app . 'App';
                if ($tpl = self::checkTplPath($path, $appDir, $flag)) {
                    return $tpl;
                }
            }
            //$path
            if ($tpl = self::checkTplPath($path, null, $flag)) {
                return $tpl;
            }
            // iPHP/设备名/$path
            if ($tpl = self::checkTplPath($path, iPHP_APP . '/' . self::$config['template']['device'], $flag)) {
                return $tpl;
            }
            if (self::$app) {
                // iPHP/testApp/$path
                if ($tpl = self::checkTplPath($path, iPHP_APP . '/' . $appDir, $flag)) {
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
            $tpl = str_replace($flag, self::$config['template']['dir'], $path);
            // return self::checkTplPath($path, self::$config['template']['dir']);
        }
        if (strpos($tpl, self::TPL_FLAG_2) !== false) {
            $tpl = str_replace(self::TPL_FLAG_2, self::$config['template']['device'], $tpl);
        }
        // var_dump($tpl);
        if (is_file(self::$handle->template_dir . "/" . $tpl)) {
            return $tpl;
        } else {
            return false;
        }
    }
    public static function checkTplPath($tpl, $dir = null, $flag = null)
    {
        // self::$PATHS[] = $tpl;
        $flag === null && $flag = iPHP_APP . ':/';
        $ntpl = str_replace($flag, $dir, $tpl);
        $ntpl  = ltrim($ntpl, '/');
        $tdir = rtrim(self::$handle->template_dir, '/');
        $path = $tdir . "/" . $ntpl;
        self::$PATHS[] = $path;
        if (is_file($path)) {
            return $ntpl;
        }
        return false;
    }
    public static function checkDir($name)
    {
        $dir = self::$handle->template_dir . "/" . $name;
        if (is_dir($dir)) {
            return $dir;
        }
        return false;
    }
    public static function unfuncVars(&$vars)
    {
        unset($vars[View::TPL_FUNC_NAME]);
    }
    public static function appVars($app_name = true, $out = false)
    {
        $app_name === true && $app_name = iAPP::$NAME;
        $rs = self::getVars($app_name);
        return $rs['param'];
    }
    public static function getVars($key = null)
    {
        return self::$handle->get_template_vars($key);
    }
    public static function setGlobal($value = null, $key = null, $append = false)
    {
        if (is_array($value) && $key === null) {
            self::$handle->_global = array_merge(self::$handle->_global, $value);
        } else {
            $vars = &self::$handle->_global[$key];
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
        $array = array();
        foreach ((array) $params as $ak => $av) {
            $ak = trim($ak);
            if (strpos($ak, '[') !== false && substr($ak, -1) == ']') {
                unset($params[$ak]);
                $pa = make_multi_array($ak, $av, '[');
                $array = array_merge_recursive((array) $array, (array) $pa);
            }
        }
        $params = array_merge((array) $params, (array) $array);
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
        self::$handle or self::init();
        self::$handle->fetch($name, true);
    }
    public static function fetch($name)
    {
        self::$handle or self::init();
        return self::$handle->fetch($name);
    }
    public static function render($file, $app = 'index')
    {
        $file or self::throwError('Please set the template file or update node cache', '001', 'TPL');
        $app && self::$app = $app;
        self::receiveTheme($file);
        if (self::$gateway == 'html') {
            return self::$handle->fetch($file);
        } else {
            self::$handle->fetch($file, true);
            iDebug::info($file);
        }
    }
    public static function clearCookie($key = null)
    {
        if ($key) {
            Cookie::set('@' . $key, '', -31536000);
        } else {
            Cookie::set('@template', '', -31536000);
            Cookie::set('@theme', '', -31536000);
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
            // empty($template) && $template = Cookie::get('@template');
            if ($template) {
                $template = File::escapeDir(ltrim($template, '/'));
                $tdir = rtrim(self::$handle->template_dir, '/');
                $path = $tdir . "/" . $template;
                File::check($path);
                is_dir($path) && self::$config['template']['dir'] = $template;
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
                $theme .= self::$EXT;
                $theme = File::escapeDir(ltrim($theme, '/'));
                $dir = self::$config['template']['dir'];
                File::check($theme);
                // var_dump($theme);
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
