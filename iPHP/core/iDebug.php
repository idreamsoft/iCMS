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

class iDebug
{
	public static $ID  = 'error-message';
	public static $CALLBACK  = array();
	public static $ex = null;
	public static $DATA = array();
	public static function dump($data = null, $title = null)
	{
		if (empty($data)) return;
		$data === true && $data = iDebug::$DATA;

		echo '<div class="block block-themed">';
		echo '<div class="block-header">' . ($title ?: '"dump 调试信息"') . '</div>';
		echo '<div class="block-content">';
		echo '<pre class="alert alert-primary">';
		$data = Security::filterPath($data);
		print_r($data);
		echo '</pre>';
		echo '</div>';
		echo '</div>';
	}
	public static function info($tpl = null)
	{
		$tpl = Security::filterPath($tpl);
		if (iPHP_DEBUG && iPHP_DEBUG_TRACE) {
			echo '<div class="block block-themed">';
			echo '<div class="block-header bg-primary-dark">info 调试信息</div>';
			echo '<div class="block-content">';
			echo '<div class="alert alert-success">模板:' . $tpl . ' <hr /> 
			使用内存:' . File::sizeUnit(memory_get_usage()) . ', 
			执行时间:' . Helper::timerStop() . 's <hr/>
			数据库累计执行:' . DB::getQueryNum() . '次
			数据库累计用时:' . DB::timerStop() . 's
			</div>';
			iDebug::dump(true, 'iDebug::$DATA 调试信息');
			iPHP_DB_TRACE && iDebug::dump(DB::getQueryTrace(), '数据库执行详情');
			echo '</div>';
			echo '</div>';
		}
	}

	public static function errorLog($error)
	{
		defined('iPHP_DEBUG_ERRORLOG') or define('iPHP_DEBUG_ERRORLOG', false); //兼容PHP7
		if (!iPHP_DEBUG_ERRORLOG) return;

		@file_put_contents(
			iPHP_APP_CACHE . '/error_log_' . md5(sha1(iPHP_KEY)) . '_' . date("Y-m") . '.log',
			"<?php exit('What the fuck!');?>"
				. PHP_EOL . '[' . date("Y-m-d H:i:s") . '] ' . Request::ip()
				. PHP_EOL . iPHP_REQUEST_URL
				. ($_GET ? PHP_EOL . '$_GET=>' . var_export($_GET, true) : '')
				. ($_POST ? PHP_EOL . '$_POST=>' . var_export($_POST, true) : '')
				. PHP_EOL . html2text($error)
				. PHP_EOL . PHP_EOL,
			FILE_APPEND
		);
	}
	/**
	 * [exceptionHandler description]
	 *
	 * @param   Exception  $ex  [$ex description]
	 * @return  [type]       [return description]
	 */
	public static function exceptionHandler($ex)
	{
		self::$ex && $ex = self::$ex;
		if (is_object($ex)) {
			$trace = $ex->getTrace();
			$error = array(
				'errno' => 99998,
				'errstr' => $ex->getMessage(),
				'errfile' => $ex->getFile(),
				'errline' => $ex->getLine(),
			);
		} elseif (is_array($ex)) {
			$trace = $ex['trace'];
			$error = $ex;
		}
		return self::errorPrint($error, $error['errno'], $trace);
	}
	public static function shutdownHandler()
	{
		$last = error_get_last();
		$last && $error = array(
			'errno' => $last['type'],
			'errstr' => $last['message'],
			'errfile' => $last['file'],
			'errline' => $last['line']
		);
		// @ 强制屏蔽错误
		if (self::$ex === false) {
			return false;
		}
		$last && self::errorPrint($error, 99999);
	}
	public static function errorHandler($errno, $errstr, $errfile, $errline)
	{
		$error = compact('errno', 'errstr', 'errfile', 'errline');
		return self::errorPrint($error, $error['errno']);
	}
	public static function errorPrint($error, $code = 0, $backtrace = null)
	{
		self::errorLog($error);
		// 屏蔽php8 Undefined array key
		if (version_compare(PHP_VERSION, '8.0', ">=")) {
			if ($error['errno'] == E_WARNING) {
				if (
					stripos($error['errstr'], 'Undefined array key') === 0 ||
					stripos($error['errstr'], 'Undefined variable $') !== false ||
					stripos($error['errstr'], 'Trying to access array offset on value of type null') === 0
				) {
					return null;
				}
			}
		}
		// @ 强制屏蔽错误
		if (!(error_reporting() & $error['errno'])) {
			// This error code is not included in error_reporting, so let it fall
			// through to the standard PHP error handler
			// return false;
			if (stripos($error['errstr'], 'Use of undefined constant') === false) {
				return null;
			}
		}

		$body = ob_get_contents();
		$body = Security::filterPath($body);
		ob_get_clean();

		defined('E_STRICT') or define('E_STRICT', 2048);
		defined('E_EXCEPTION_ERROR') or define('E_EXCEPTION_ERROR', 99998);
		defined('E_RECOVERABLE_ERROR') or define('E_RECOVERABLE_ERROR', 4096);

		switch ($error['errno']) {
			case E_ERROR:
				$type = "Error";
				break;
			case E_WARNING:
				$type = "Warning";
				break;
			case E_PARSE:
				$type = "Parse Error";
				break;
			case E_NOTICE:
				$type = "Notice";
				break;
			case E_CORE_ERROR:
				$type = "Core Error";
				break;
			case E_CORE_WARNING:
				$type = "Core Warning";
				break;
			case E_COMPILE_ERROR:
				$type = "Compile Error";
				break;
			case E_COMPILE_WARNING:
				$type = "Compile Warning";
				break;
			case E_USER_ERROR:
				$type = iPHP_APP . " Error";
				break;
			case E_USER_WARNING:
				$type = iPHP_APP . " Warning";
				break;
			case E_USER_NOTICE:
				$type = iPHP_APP . " Notice";
				break;
			case E_STRICT:
				$type = "Strict Notice";
				break;
			case E_RECOVERABLE_ERROR:
				$type = "Recoverable Error";
				break;
			case E_EXCEPTION_ERROR:
				$type = "Exception";
				break;
			default:
				$type = sprintf("Unknown error (%s)", $error['errno']);
				break;
		}

		$backtrace = $backtrace ?: debug_backtrace();

		$message = sprintf(
			'<div id="%s"><b>%s</b>:%s in <b>%s</b> on line <b>%s</b><br />%s</div>',
			self::$ID,
			$type,
			$error['errstr'],
			$error['errfile'],
			$error['errline'],
			self::backtraceHtml($backtrace)
		);

		$message = Security::filterPath($message);

		$body && $message = $body . '<hr />' . $message;

		if (isset(self::$CALLBACK['print']) && self::$CALLBACK['print']) {
			iPHP::callback(self::$CALLBACK['print'], array($message, $error));
		}

		if (Request::isAjax()) {
			// $message = str_replace("<br />","\n",$message);
			// $message = html2text($message);
			// $message = html2js($message);
			iJson::error($message, null, -1);
		}
		// if (Request::param('frame') || Request::post() || Request::file()) {
		// 	Script::$dialog['width'] = 600;
		// 	Script::$dialog['height'] = 'auto';
		// 	Script::alert($message, null, 300000);
		// }
		if (iPHP_SHELL) {
			$message = str_replace("<br />", PHP_EOL, $message);
			$message = html2text($message) . PHP_EOL;
		}
		exit($message);
	}
	public static function args($args)
	{
		// $args = var_export($args,true);
		// $args = preg_replace('/\s*\d+\s*=>\s*/','',$args);
		$res = [];
		foreach ($args as $key => $value) {
			if (is_array($value)) {
				// var_dump($value);
				// $res[] = sprintf('[%s]',self::args($value));
				$res[] = 'Array(...)';
			} else {
				if (is_null($value)) {
					$res[] = 'NULL';
				} elseif ($value === true) {
					$res[] = 'TRUE';
				} elseif ($value === false) {
					$res[] = 'FALSE';
				} else {
					is_object($value) && $value = get_class($value);
					$res[] = sprintf('"%s"', $value);
				}
			}
		}
		return implode(',', $res);
	}
	public static function backtraceHtml($backtrace = null)
	{
		empty($backtrace) && $backtrace = debug_backtrace();
		krsort($backtrace);

		$html = [];
		$c = count($backtrace);
		// var_dump($backtrace);
		foreach ($backtrace as $i => $bt) {
			$args = $bt['args'] ?: '';
			if (is_array($args)) {
				// $args = 'Array()';
				// $fill = array_fill(0, count($args), '?');
				// $args = var_export($fill,true);
				$args = self::args($args);
				// $args = var_export($args,true);
				$args = preg_replace('/\s*\d+\s*=>\s*/', '', $args);
				$args = preg_replace("/\n/", "", $args);
				$args = str_replace("/\n/", "", $args);
				// $args = str_replace(["',)"],["',NULL)"],$args);

				// $args = preg_replace(['/,\s*/','/\(\s*\)/'],[',','()'],$args);
			}
			isset($bt['class']) or $bt['class'] = '';
			isset($bt['type']) or $bt['type'] = '';

			$div = ($c - $i) . ". <b>{$bt['class']}{$bt['type']}{$bt['function']}({$args})</b>";
			isset($bt['file']) && $div .= " in <b>{$bt['file']}</b>";
			isset($bt['line']) && $div .= " on line <b>{$bt['line']}</b>";
			$html[] = $div;
		}
		return '<pre>' . implode('<br />', $html) . '</pre>';
	}
}
