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
// use iPHP\core\Security;
// use iPHP\core\iDefine;
// use iPHP\core\Waf;
// use iPHP\core\Adapter;

class iPHP
{
	public static $RESERVED   = array('API', 'ACTION', 'DO', 'MY');

	public static $handler    = array(
		'autoload'  => array('iPHP',   'autoloadHandler'),
		'shutdown'  => array('iDebug', 'shutdownHandler'),
		'exception' => array('iDebug', 'exceptionHandler'),
		'error'     => array('iDebug', 'errorHandler'),
	);

	public static function bootstrap()
	{
		Helper::timerStart();
		self::start();
		self::setMemoryLimit();
		self::setHandler();
		Request::boot();
		Security::boot();
		iDefine::boot();
	}
	public static function start()
	{
		@ob_start();
		@ini_set('magic_quotes_sybase', 0);
		@ini_set("magic_quotes_runtime", 0);
		@ini_set("magic_quotes_gpc", 0);
		@header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
	}
	public static function setMemoryLimit()
	{
		if (function_exists('ini_get')) {
			$memorylimit = @ini_get('memory_limit');
			if ($memorylimit && get_bytes($memorylimit) < 33554432 && function_exists('ini_set')) {
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
		set_error_handler(self::$handler['error'], E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_STRICT);
		// 自定义异常处理。
		set_exception_handler(self::$handler['exception']);
		// 设置一个用户定义的自动加载函数
		spl_autoload_register(self::$handler['autoload'], true);
	}
	public static function autoloadHandler($name)
	{
		try {
			self::composer();
			require_once self::autoload($name);
			if (class_exists($name)) {
				return true;
			}
		} catch (\Exception $ex) {
			$autoload_finish = true;
			//自动载入失败 检测其它 autoload 并注册
			$functions = spl_autoload_functions();
			if ($functions) foreach ($functions as $key => $autoload) {
				if ($autoload != iPHP::$handler['autoload']) {
					$autoload_finish = false;
					spl_autoload_register($autoload);
				}
			}
			if (iPHP_DEBUG) {
				if (!$autoload_finish) {
					//存在 其它 autoload 不报错
					return false;
				}
				$path = $ex->getMessage();
				// self::exception(
				// 	sprintf('Unable to load class "%s",file paths "%s"', $name, $path),
				// 	'0x021'
				// );
				$msg = "Class '$name' not found";
				$msg = sprintf('Unable to load Class/Function "%s",in paths "%s"', $name, $path);
				self::exception($msg, '0x020');
			} else {
				return false;
			}
		}
	}
	public static function autoload($name)
	{

		$PREFIX = self::$RESERVED;
		if (strpos($name, '\\') !== false) {
			//namespace aaa\bbb
			$space = str_replace('\\', DIRECTORY_SEPARATOR, $name);
			// vendor/aaa/bbb.php
			$path = iPHP_COMPOSER_DIR . '/' . $space . '.php';
			$paths[] = $path;
			// var_dump($path);
		} else {
			if (!isset($GLOBALS['iPHP_CORE_CLASS'])) {
				$GLOBALS['iPHP_CORE_CLASS'] = explode(',', iPHP_CORE_CLASS);
			}
			if (in_array($name, $GLOBALS['iPHP_CORE_CLASS'])) {
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
				$path = iAPP::path($name, $file);
				if (empty($path) or !is_file($path)) {
					$paths[] = $path;
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
						$path = iAPP::path($name, $file);
						// var_dump($path);
						if (!is_file($path)) {
							$paths[] = $path;
							//core/AaaTest.php
							$path = iPHP_APP_CORE . '/' . $file . '.php';
							if (!is_file($path)) {
								$paths[] = $path;
								throw new sException(implode(' Or ', $paths), -1);
							}
						}
						// is_file($path) or $path = $core;
					}
				}
			}
		}

		if (is_file($path)) return $path;

		throw new sException(implode(' Or ', $paths), -1);
	}
	public static $instance = array();
	// 获得类的对象实例
	public static function getInstance($className)
	{
		// self::$instance && $instance = self::$instance[$className];
		// if (!$instance) {
		$paramArr = self::getMethodParams($className);
		$rc = new ReflectionClass($className);
		$instance = $rc->newInstanceArgs($paramArr);
		// 	self::$instance[$className] = $instance;
		// }
		return $instance;
	}

	/**
	 * 执行类的方法
	 * @param  [type] [$className,$methodName]  [类名,方法名称]
	 * @param  [type] $params     [额外的参数]
	 * @return [type]             [description]
	 */
	public static function invoke($call, $params = [])
	{
		$func = $call;
		if (is_array($call)) {
			list($className, $methodName) = $call;
			$instance = self::getInstance($className);
			$paramArr = self::getMethodParams($className, $methodName, $params);
			$func = [$instance, $methodName];
		}
		return call_user_func_array($func, (array)$paramArr);
	}

	/**
	 * 获得类的方法参数，只获得有类型的参数
	 * @param  [type] $className   [description]
	 * @param  [type] $methodsName [description]
	 * @return Array              [description]
	 */
	protected static function getMethodParams($className, $methodsName = '__construct', $paramData = [])
	{
		// 通过反射获得该类
		$class = new ReflectionClass($className);
		$paramArr = []; // 记录参数，和参数类型
		// 判断该类是否有构造函数
		if ($class->hasMethod($methodsName)) {
			// 获得构造函数
			$construct = $class->getMethod($methodsName);
			// 判断构造函数是否有参数
			$params = $construct->getParameters();
			if (count($params) > 0) {
				// 判断参数类型
				foreach ($params as $key => $param) {
					if ($param->isOptional()) {
						$default = $param->getDefaultValue();
					}
					if (version_compare(PHP_VERSION, '7.0', ">=")) {
						$reflectionType = $param->getType();
						if ($reflectionType instanceof ReflectionNamedType) {
							$paramClass = $param->getType()->getName();
						}
					} else {
						$reflectionClass = $param->getClass();
						if ($reflectionClass instanceof ReflectionClass) {
							$paramClass = $reflectionClass->getName();
						}
					}
					if ($paramClass) {
						// 获得参数类型
						$args = self::getMethodParams($paramClass);
						$rc = new ReflectionClass($paramClass);
						$paramArr[] = $rc->newInstanceArgs($args);
					} else {
						$paramArr[] = isset($paramData[$key]) ? $paramData[$key] : $default;
					}
				}
			}
		}
		return $paramArr;
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

	/**
	 * [callback 回调执行]
	 * @param  [type] $callback [执行函数]
	 * @param  [type] $params   [参数]
	 * @param  [type] $default  [默认值]
	 * @return [type]           [description]
	 */
	public static function callback($callback, $params = null, $default = null)
	{
		if (empty($callback)) return;

		if (is_array($callback) && !is_callable($callback)) {
			$res = array();
			foreach ($callback as $key => $call) {
				if (is_callable($call)) {
					$res[$key] = self::callback($call, $params, $default);
				}
			}
			return $res;
		}
		$method = $callback;
		is_array($callback) && $method = $callback[1];
		if (is_string($method)) {
			//带有_FALSE 的方法名 如果出错 返回默认值 false
			if (stripos($method, '_FALSE') !== false) {
				$default = false;
			}
			//带有_TRUE 的方法名 如果出错 返回默认值 true
			if (stripos($method, '_TRUE') !== false) {
				$default = true;
			}
		}
		try {
			if (is_array($callback)) {
				$isObj = is_object($callback[0]);
				if (!$isObj) {
					$class = new ReflectionClass($callback[0]);
					$method = $class->getMethod($callback[1]);
					if (!$method->isStatic()) { //非静态方法调用
						$callback[0] = new $callback[0];
					}
				}
			}
			return call_user_func_array($callback, (array)$params);
		} catch (\Exception $ex) {
			$code = $ex->getCode();
			$msg = $ex->getMessage();
			// var_dump($default,E_USER_ERROR);
			if ($default == E_USER_ERROR) {
				throw $ex;
			} elseif ($default === null) {
				return $params;
			} else {
				return $default;
			}
		}
	}

	public static function isCallback($var)
	{
		if (is_array($var) && count($var) == 2) {
			$var = array_values($var);
			if ((!is_string($var[0]) && !is_object($var[0])) || (is_string($var[0]) && !class_exists($var[0]))) {
				return false;
			}
			$isObj = is_object($var[0]);
			$class = new ReflectionClass($isObj ? get_class($var[0]) : $var[0]);
			if ($class->isAbstract()) {
				return false;
			}
			try {
				$method = $class->getMethod($var[1]);
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
		} elseif (is_string($var) && function_exists($var)) {
			return true;
		}
		return false;
	}
	/**
	 * 获取注释
	 * @param  [type] $class  [description]
	 * @param  [type] $method [description]
	 * @return [type]         [description]
	 */
	public static function getDocComment($class, $method)
	{
		$reflection = new ReflectionMethod($class, $method);
		$docblockr  = $reflection->getDocComment();
		preg_match_all('#^\s*\s(.+)\n#m', $docblockr, $lines);
		$doc = array();
		foreach ($lines[1] as $key => $line) {
			self::parseLine($line, $doc);
		}
		is_array($doc['descArray']) && $doc['desc'] = implode(PHP_EOL, $doc['descArray']);
		return $doc;
	}
	/**
	 * 解析注释
	 * @param  [type] $line [description]
	 * @return [type]       [description]
	 */
	private static function parseLine($line, &$doc)
	{
		// trim the whitespace from the line
		$line = trim($line);

		if (empty($line)) {
			return null;
		} // Empty line
		if (strpos($line, '@') !== false) {
			$line   = preg_replace("/\s+/", " ", $line);
			//* @param string $a [$a description]
			//* @param type $b description
			//* @param type $b
			preg_match('/\*\s*@(\w+)\s+([\[\]\w]+)\s+(\$\w+)\s+(.*?)$/is', $line, $match);
			// var_dump($match);
			if ($match) {
				$type = trim($match[2]);
				$var  = trim($match[3]);
				$desc = trim($match[4]);
			} else {
				//* @param true [description]
				//* @param asd description
				preg_match('/\*\s*@(\w+)\s+([\[\]\w]+)\s+(.*?)$/is', $line, $match);
				// var_dump($match);	
				if ($match) {
					$value = trim($match[2]);
					$desc = trim($match[3]);
				} else {
					//* @param  [description]
					//* @param  description
					preg_match('/\*\s*@(\w+)\s+(.+)/is', $line, $match);
					// var_dump($match);
					$desc = trim($match[2]);
				}
			}
			$group = trim($match[1]);

			if ($group == 'access') {
				$aflag = $var == "true" ? true : false;
				if ($type) {
					$doc[$group][$type] = $aflag;
				} else {
					$doc[$group] = $aflag;
				}
			} elseif ($group == 'return') {
				$doc[$group] = compact('value', 'desc');
			} else {
				if ($var) {
					$doc[$group][$var] = compact('var', 'type', 'desc');
				} elseif ($value) {
					$doc[$group][] = compact('value', 'type', 'desc');
				} else {
					$doc[$group] = is_array($doc[$group]) ? compact('desc') : $desc;
				}
			}
		} else {
			$line = str_replace(array('[', ']'), '', $line);
			preg_match('#^\*\s(.+)#is', $line, $match);
			$match[1] && $doc['descArray'][] = trim($match[1]);
		}
		if ($doc) {
			return $doc;
		}
	}
	/**
	 * 统一所有异常，由 sException 处理
	 *
	 * @param   [type]$ex    [$ex description]
	 * @param   [type]$code  [$code description]
	 * @param   null         [ description]
	 *
	 * @return  [type]       [return description]
	 */
	public static function throwError($ex, $code = null)
	{
		if ($ex instanceof sException) {
			throw $ex;
		} else {
			self::exception($ex, $code);
		}
	}
	public static function alert($msg)
	{
		return self::exception($msg, 'alert');
	}
	public static function exception($ex, $code = null)
	{
		$msg = $ex;
		if ($ex instanceof Exception) {
			iDebug::$ex = $ex;
			$msg   = $ex->getMessage();
			$code  = $ex->getCode();
			if ($ex instanceof sException) {
				throw $ex;
				// $code = $ex->getState();
			}
		}
		throw new sException($msg, $code);
	}
}
