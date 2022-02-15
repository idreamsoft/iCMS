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
define('ADMINCP_URL', iPHP_SELF . '?app');

// use iPHP\core\Security;
// use iPHP\core\Script;

class Admincp
{
	public static $APP          = null;
	public static $APPID        = null;
	public static $APP_INSTANCE = null;
	public static $APP_NAME     = null;
	public static $APP_DO       = null;
	public static $APP_METHOD   = null;
	public static $APP_PATH     = null;
	public static $APP_TPL      = null;
	public static $APP_FILE     = null;
	public static $APP_DIR      = null;
	public static $APP_DATA     = null;
	public static $APP_PARAMS   = null;
	public static $DATA         = array();


	public static $METHOD_BEFOR = true;
	public static $METHOD_RUN   = true;
	public static $METHOD_AFTER = true;

	public static function init($app = null, $do = null, $prefix = null)
	{
		empty($app) && $app = Request::param('app');
		empty($do)  && $do  = Request::get('do');
		empty($app) && $app = 'admincp';
		if (stripos($_SERVER['SCRIPT_NAME'], '.php/') !== false) {
			list($_, $app, $do) = explode('/', ltrim($_SERVER['SCRIPT_NAME'], '/'));
		}
		if ($action = Request::param('action')) {
			DB::beginTransaction();
			$do = $action;
			$prefix = iPHP_POST_PREFIX;
		}
		$do  or $do  = iPHP_APP;
		//$do = do_save;

		if (strpos($do, iPHP_GET_PREFIX) === 0 || strpos($do, iPHP_POST_PREFIX) === 0) {
			list($prefix, $do) = explode('_', $do);
		}

		strpos($app, '..') === false or self::exception('what the fuck', '1024');

		self::$APP_NAME   = $app;
		self::$APP_DO     = $do;
		self::$APP_METHOD = $prefix . $do;

		//TestAdmincp => TestAdmincp.php
		$className = ucfirst($app) . 'Admincp';
		$appFile = $className . '.php';
		//admincp.php?app=test_node
		//admincp.php?app=testNode
		$app = iApp::get($app, $sub);
		//test_node => testNodeAdmincp.php
		//testNodeAdmincp => testNodeAdmincp.php
		if ($sub) {
			$className = ucfirst($app) . ucfirst($sub) . 'Admincp';
			$appFile = $className . '.php';
		}
		self::$APP = $app;
		self::$APP_PATH = iAPP::path($app);
		// self::$APP_TPL  = self::$APP_PATH . '/'.self::$VIEW_DIR;
		self::$APP_FILE = self::$APP_PATH . $appFile;

		self::$APP_DATA = Apps::get($app);
		self::$APPID = self::$APP_DATA['id'];
		//程序文件不存为自定义APP，指向content应用
		if (!is_file(self::$APP_FILE) && self::$APP_DATA['apptype'] == Apps::CONTENT_TYPE) {
			if (self::$APP_DATA) {
				$sub && $sub = ucfirst($sub);
				$className = 'Content' . $sub . 'Admincp';
				$appFile = $className . '.php';
				self::$APP_PATH = iAPP::path('content');
				// self::$APP_TPL  = self::$APP_PATH . '/'.self::$VIEW_DIR;
				self::$APP_FILE = self::$APP_PATH . $appFile;
			} else {
				self::exception(
					sprintf('Unable to load app data <b>%s</b>(%s)', self::$APP_FILE, $app),
					1001
				);
			}
		}

		is_file(self::$APP_FILE) or self::exception(
			sprintf('Unable to find admincp file %s (%s)', $appFile, self::$APP_FILE),
			1002
		);
		self::define($prefix, self::$APP_NAME, self::$APP_DO);

		return $className;
	}
	public static function run($app = null, $do = null, $params = null, $prefix = iPHP_GET_PREFIX)
	{
		try {
			$className = self::init($app, $do, $prefix);

			AdmincpHooks::onInit(); //相关应用初始化配置
			AdmincpHooks::onAuth(); //验证权限

			if ($startup = self::startup(APP_URL_DO)) {
				$className = $startup;
			}

			AdmincpHooks::onAppBegin($className);
			self::$APP_INSTANCE = iPHP::getInstance($className);
			AdmincpHooks::onAppInit($className);
			/**
			 * 无方法，仅实例化
			 */
			if (isset(self::$APP_INSTANCE->noMethod) && self::$APP_INSTANCE->noMethod === true) {
				return self::$APP_INSTANCE;
			}

			$params === null && $params = self::$APP_PARAMS;
			if ($params === 'object') return self::$APP_INSTANCE;

			$rc = new ReflectionClass($className);
			$rc->hasMethod(self::$APP_METHOD) or self::exception(
				sprintf('Call to undefined method <b>%s::%s</b>', $className, self::$APP_METHOD),
				1003
			);
			AdmincpHooks::onMethodBegin($className, $params); //应用方法执行前
			// var_dump(self::$APP_METHOD,self::$APP_DO);

			$METHOD_BEFOR = sprintf('__%s', self::$APP_METHOD); //__do_add
			if ($rc->hasMethod($METHOD_BEFOR) && self::$METHOD_BEFOR) {
				$_response = call_user_func_array(array(self::$APP_INSTANCE, $METHOD_BEFOR), (array) $params);
				is_null($_response) or $response = $_response;
			}

			// $response = iPHP::invoke([$className, self::$APP_METHOD], $params);
			self::$METHOD_RUN && $response = call_user_func_array(array(self::$APP_INSTANCE, self::$APP_METHOD), (array) $params);

			$METHOD_AFTER = sprintf('%s__', self::$APP_METHOD); //do_add__
			// self::runMethod($METHOD_AFTER,$params,self::$METHOD_RUN[2]);
			if ($rc->hasMethod($METHOD_AFTER) && self::$METHOD_AFTER) {
				$_response = call_user_func_array(array(self::$APP_INSTANCE, $METHOD_AFTER), [$response, $params]);
				is_null($_response) or $response = $_response;
			}
			AdmincpHooks::onMethodEnd($className, $response); //应用方法执行后
			DB::commit();
			return self::response($response, $className, self::$APP_METHOD);
		} catch (\Exception $ex) {
			DB::rollBack();
			self::throwError($ex);
		}
	}

	// public static function runMethod($method,$params,$flag=true){
	// 	if (method_exists(self::$APP_INSTANCE,$method) && $flag) {
	// 		$_response = call_user_func_array(array(self::$APP_INSTANCE, $method), (array) $params);
	// 		is_null($_response) OR $response = $_response;
	// 	}
	// }
	public static function define($prefix, $APP_NAME, $APP_DO)
	{
		define('APP_URL_DO', strtolower(substr($prefix, 0, -1)));
		define('APP_URL_QS', self::makeQS([$APP_NAME, APP_URL_DO => $APP_DO]));
		define('APP_URL',    ADMINCP_URL . '=' . $APP_NAME);
		define('APP_DOURL',  ADMINCP_URL . '=' . APP_URL_QS);
		define('APP_MAINID', 'main-' . $APP_NAME);
		define('APP_FORMID', 'form-' . md5(APP_URL));
		define('APP_ASSETS', './app/' . $APP_NAME . '/assets');
	}

	public static function response($response, $class, $method)
	{

		if (ob_get_contents()) return;

		$do = self::$APP_DO;
		$doc = iPHP::getDocComment($class, $method);
		$title = AppsAccess::$docMap[$do];
		$doc['desc'] && $title = (string) $doc['desc'];
		$title = $title ?: $method;

		$vars = get_class_vars($class);
		if ($vars['ACCESC_TITLE']) {
			$title = str_replace('{title}', $vars['ACCESC_TITLE'], $title);
		}
		$title = str_replace('{title}', self::$APP_DATA['title'], $title);
		$title = str_replace('{app.name}', self::$APP_DATA['name'], $title);
		// var_dump($response,$title,Request::isPost());
		if (is_null($response)) {
			//POST return null;
			if (Request::isPost() || isset($_GET['action'])) {
				AdmincpBase::success($title);
			}
		} else {
			iJson::$forward = iPHP_REFERER;
			if ($response === true) {
				// return true;
				AdmincpBase::success();
			} elseif ($response === false) {
				// return false;
				AdmincpBase::alert($title);
			} elseif (is_array($response)) {
				if (
					array_key_exists('data', $response) &&
					array_key_exists('message', $response) &&
					array_key_exists('url', $response) &&
					array_key_exists('code', $response)
				) {
					AdmincpBase::success(
						$response['data'],
						$response['message'],
						$response['url'],
						$response['code']
					);
				} elseif ($response['data']) {
					AdmincpBase::success(
						(array)$response['data'],
						$response['title'] ?: $title,
						$response['url']
					);
				} else {
					// return [true,'asd'];
					// return [false,'asd'];
					if ($response[0] === true) {
						AdmincpBase::success($response[1] ?: $title);
					} elseif ($response[0] === false) {
						AdmincpBase::alert($response[1] ?: $title);
					} else {
						//return ['asd','123'];
						AdmincpBase::success($response, $title);
					}
				}
			} elseif (is_string($response)) {
				// return http://...;
				// return string;

				if (Request::isUrl($response, true) || strpos($response, ADMINCP_URL) !== false) {
					AdmincpBase::success($title, $response);
				} else {
					AdmincpBase::success($response);
				}
			} else {
				return $response;
			}
		}
	}
	public static function startup($prefix = null)
	{
		$app = ucfirst(strtolower(self::$APP_NAME));
		$prefix = ucfirst(strtolower($prefix));
		$doArray = array_map('ucfirst', explode('_', strtolower(self::$APP_DO)));
		$do = implode('', $doArray);
		// article/articleDoManageAdmincp.php
		// article/articleActionSaveAdmincp.php
		$className = $app . $prefix . $do . 'Admincp';
		$path   = self::$APP_PATH . $className . '.php';
		// var_dump($className, $path);
		if (!is_file($path)) {
			// article/articleDoAdmincp.php
			// article/articleActionAdmincp.php
			$className = $app . $prefix . 'Admincp';
			$path   = self::$APP_PATH . $className . '.php';
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
	 * 统一所有异常，由 AdmincpException->display 处理
	 *
	 * @param   [type]$ex    [$ex description]
	 * @param   [type]$code  [$code description]
	 * @param   null         [ description]
	 *
	 * @return  [type]       [return description]
	 */
	public static function throwError($ex, $code = null)
	{
		if ($ex instanceof AdmincpException) {
			return $ex->display();
		} else {
			try {
				self::exception($ex, $code);
			} catch (AdmincpException $ae) {
				return $ae->display();
			}
		}
	}
	public static function exception($ex, $code = null)
	{
		$msg = $ex;
		if ($ex instanceof Exception) {
			AdmincpException::$ex = $ex;
			$msg   = $ex->getMessage();
			$code  = $ex->getCode();
			if ($ex instanceof sException) {
				$code = $ex->getState();
			}
		}
		throw new AdmincpException($msg, $code);
	}
	public static function makeQS($array)
	{
		$key = array_search(iPHP_APP, $array);
		if ($key !== FALSE) unset($array[$key]);
		$array = array_filter($array);
		return substr(http_build_query($array), 2);
	}
	public static function url($app)
	{
		return sprintf("%s=%s", ADMINCP_URL, $app);
	}
	/**
	 * app=test&do=aaa....
	 *
	 * @return String
	 */
	public static function uri()
	{
		if (defined('APP_DOURL')) {
			$url = APP_DOURL;
		} else {
			$url = iPHP_REQUEST_URL;
		}
		return str_replace(ADMINCP_URL . '=', '', $url);
	}
	/**
	 * 代理
	 *
	 * @param String $method
	 * @param Array $params
	 * @return void
	 */
	public static function proxy($method, $params)
	{
		$rc = new ReflectionClass('Admincp');
		if ($rc->hasMethod($method)) {
			$call = array('Admincp', $method);
			try {
				return call_user_func_array($call, $params);
			} catch (\Exception $ex) {
				throw $ex;
			}
		} else {
			throw new Exception("Calling method '$method' " . implode(', ', $params));
		}
	}
}
