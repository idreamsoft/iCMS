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

class iAPP
{
	public static $INSTANCE = null;
	public static $LISTS    = null;
	public static $ID       = null;
	public static $NAME     = null;
	public static $DO       = null;
	public static $METHOD   = null;
	public static $PATH     = null;
	public static $FILE     = null;
	public static $ARGS     = null;
	public static $DIRS     = array();
	public static $CLASS    = null;

	public static $callback   = array();

	public static function site($site = null)
	{
		if ($site === null) {
			$site = iPHP_MULTI_SITE ? $_SERVER['HTTP_HOST'] : iPHP_APP;
			if (iPHP_MULTI_DOMAIN) {
				preg_match("/[^\.\/][\w\-]+\.[^\.\/]+$/", $site, $matches); //只绑定主域
				$site = $matches[0];
			}
			strpos($site, '..') === false or self::exception('What are you doing', '001');
		}
		empty($site) && self::exception('Unable to find site ', '0000');
		return $site;
	}
	public static function config($site = null)
	{
		if (iPHP_CALLBACK_CONFIG) return iPHP::callback(iPHP_CALLBACK_CONFIG);

		defined('iPHP_APP_SITE') or define('iPHP_APP_SITE', self::site($site));
		defined('iPHP_APP_CONFDIR') or define('iPHP_APP_CONFDIR', iPHP_CONFIG_DIR . '/' . iPHP_APP_SITE); //网站配置目录
		define('iPHP_APP_CONFIG', iPHP_APP_CONFDIR . '/config.php'); //网站配置文件
		is_file(iPHP_APP_CONFIG) or self::exception(
			sprintf(
				'Unable to find "%s" config file (%s).Please install %s',
				iPHP_APP_SITE,
				iPHP_APP_CONFIG,
				iPHP_APP
			),
			'0001'
		);
		$config = require_once iPHP_APP_CONFIG;
		self::$LISTS = $config['apps'];
		self::loadConf('database');
		return $config;
	}

	public static function run($app = NULL, $do = NULL, $args = NULL, $prefix = iPHP_GET_PREFIX)
	{
		try {
			empty($app) && $app = Request::sparam('app'); //单一入口
			empty($app) && $app = File::name(iPHP_SELF);
			empty($do)  && $do  = Request::sget('do');
			empty($app) && $app = 'index';


			if ($action = Request::post('action')) {
				// DB::beginTransaction();
				$do = $action;
				$prefix = iPHP_POST_PREFIX;
			}
			$do or $do = iPHP_APP;

			//TestApp => TestApp.php
			$className = ucfirst($app) . 'App';
			$appFile = $className . '.php';
			$app = self::get($app, $sub);
			//test_node => testNodeApp.php
			//testNodeApp => testNodeApp.php
			if ($sub) {
				$className = ucfirst($app) . ucfirst($sub) . 'App';
				$appFile = $className . '.php';
			}
			
			self::$LISTS && self::$ID = self::$LISTS[$app];

			// self::$LISTS or self::exception(
			// 	'Please update the application cache',
			// 	0x01
			// );
			// self::$ID = self::$LISTS[$app] or self::exception(
			// 	sprintf('Unable to find application <b>%s</b>', $app),
			// 	0x02
			// );

			self::$PATH = self::path($app);
			self::$FILE = self::$PATH . $appFile;
			self::$CLASS  = $className;
			self::$NAME   = $app;
			self::$DO     = $do;
			self::$METHOD = $prefix . $do;
			$fix = strtolower(substr($prefix, 0, -1));
			if ($startup = self::startup($fix)) {
				$className = $startup;
			}

			//自定义APP调用
			//并初始化 iAPP::$instance,iAPP::$FILE
			is_file(self::$FILE) or iPHP::callback(self::$callback['app'], array($app));
			is_file(self::$FILE) or self::exception(
				sprintf('Unable to find application <b>%s</b>', $appFile),
				0x03
			);
			if (self::$INSTANCE === null) {
				iPHP::callback(self::$callback['begin']);
				// self::$INSTANCE = new $className;
				self::$INSTANCE = iPHP::getInstance($className);
				if (isset(self::$INSTANCE->instance) && self::$INSTANCE->instance) { //子系统模式
					self::$INSTANCE = self::$INSTANCE->instance;
					$className = get_class(self::$INSTANCE);
				}
				self::$callback['after'] && iPHP::callback(self::$callback['after']);
			} else {
				$className = get_class(self::$INSTANCE);
			}
			/**
			 * 无方法，仅实例化
			 */
			if (isset(self::$INSTANCE->noMethod) && self::$INSTANCE->noMethod === true) {
				return self::$INSTANCE;
			}
			$rc = new ReflectionClass($className);

			if ($rc->hasMethod(self::$METHOD)) {
				$args === null && $args = self::$ARGS;

				self::$callback['call'] && iPHP::callback(self::$callback['call'], [$args]);

				if ($args === 'object') return self::$INSTANCE;

				$init_method   = '__INIT__'; 				//testApp::__INIT__
				$prefix_start  = strtolower($prefix) . 'Init'; //testApp::doInit OR testApp::actionInit
				$before_method = 'before_' . self::$METHOD; //testApp::before_aaa
				$after_method  = 'after_' . self::$METHOD; 	//testApp::after_aaa

				$rc->hasMethod($init_method)   && call_user_func_array(array(self::$INSTANCE, $init_method), (array) $args);
				$rc->hasMethod($prefix_start)  && call_user_func_array(array(self::$INSTANCE, $prefix_start), (array) $args);
				$rc->hasMethod($before_method) && call_user_func_array(array(self::$INSTANCE, $before_method), (array) $args);

				if (isset(self::$INSTANCE->isRun) && self::$INSTANCE->isRun === false) {
					$response = null;
				} else {
					$response = call_user_func_array(array(self::$INSTANCE, self::$METHOD), (array) $args);
				}
				$rc->hasMethod($after_method) && call_user_func_array(array(self::$INSTANCE, $after_method), array($args, &$response));
				return $response;
				// DB::commit();
				// return self::response($response, $className, self::$METHOD);
			} else {
				DB::rollBack();
				self::exception(
					sprintf('Call to undefined method <b>%s::%s</b>', $className, self::$METHOD),
					0x04
				);
			}
		} catch (\Exception $ex) {
			DB::rollBack();
			iPHP::throwError($ex);
		}
	}
	public static function response($response, $class, $method)
	{
		if (ob_get_contents()) return;
		if (is_null($response)) {
			iJson::success();
		} else {
			$title = sprintf('%s::%s', $class, $method);
			if ($response === true) {
				// return true;
				iJson::success();
			} elseif ($response === false) {
				// return false;
				iJson::error();
			} elseif (is_array($response)) {
				// return [true,'asd'];
				// return [false,'asd'];
				if ($response[0] === true) {
					iJson::success($response[1] ?: $title);
				} elseif ($response[0] === false) {
					iJson::error($response[1] ?: $title);
				} else {
					//return ['asd','123'];
					iJson::success($response, $title);
				}
			} elseif (is_string($response)) {
				// return http://...;
				// return string;
				if (Request::isUrl($response)) {
					iJson::success($title, $response);
				} else {
					iJson::success($response);
				}
			} else {
				return $response;
			}
		}
	}
	public static function loadConf($name = null, $flag = false)
	{
		try {
			if ($name) {
				File::check($name);
				$arr = require iPHP_APP_CONFDIR . "/{$name}.php";
				if ($flag) return $arr;
				$GLOBALS[strtoupper($name)] = $arr;
			} else {
				$files = glob(iPHP_APP_CONFDIR . "/*.php");
				if (is_array($files)) foreach ($files as $key => $path) {
					$name = File::name($path);
					$arr = require_once $path;
					is_array($arr) && $GLOBALS[strtoupper($name)] = $arr;
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
			list($app, $sub) = explode('_', $app);
		} else {
			//api.php?app=testNode
			$pieces = preg_split('/(?<=\w)(?=[A-Z])/', $app);
			$pieces[1] && list($app, $sub) = $pieces;
		}
		$app = strtolower($app);
		return $app;
	}
	public static function path($app, $file = null)
	{
		$path = iPHP_APP_DIR . '/' . strtolower($app) . '/';
		$file && $path .= $file . '.php';
		return $path;
	}
	public static function setDir($key, $value)
	{
		self::$DIRS[$key] = $value;
	}
	public static function destruct()
	{
		$method = 'after_' . iAPP::$METHOD;
		$key    = 'is_' . $method;
		$isrun  = iAPP::$callback[$key];
		if (method_exists(self::$INSTANCE, $method) && !$isrun) {
			iAPP::$callback[$key] = true;
			self::$INSTANCE->$method();
		}
	}
	public static function startup($prefix = null)
	{
		// $app = ucfirst(self::$NAME);
		$prefix = ucfirst(strtolower($prefix));
		$doArray = array_map('ucfirst', explode('_', strtolower(self::$DO)));
		$do = implode('', $doArray);
		// article/ArticleDoManageApp.php
		// article/ArticleActionSaveApp.php
		$className = self::$CLASS . $prefix . $do . 'App';
		$path   = self::$PATH . $className . '.php';
		// var_dump($className, $path);
		if (!is_file($path)) {
			// article/ArticleDoApp.php
			// article/ArticleActionApp.php
			$className = self::$CLASS . $prefix . 'App';
			$path   = self::$PATH . $className . '.php';
			// var_dump($className, $path);
		}
		if (is_file($path)) {
			require_once $path;
			self::$FILE = $path;
			return $className;
		} else {
			return false;
		}
	}
	public static function exception($msg, $code = null)
	{
		throw new sException($msg, $code);
	}
}
