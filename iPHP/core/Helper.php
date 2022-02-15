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

class Helper
{
	public static $time_start = 0;
	public static function timerTask()
	{
		$timestamp = Cache::get('timerTask');
		//list($_today,$_week,$_month) = $timestamp ;
		$time     = $_SERVER['REQUEST_TIME'];
		$today    = get_date($time, "Ymd");
		$yday     = get_date($time - 86400 + 1, "Ymd");
		$week     = get_date($time, "YW");
		$month    = get_date($time, "Ym");
		$timestamp[0] == $today or Cache::set('timerTask', array($today, $week, $month), 0);
		return array(
			'yday'  => ($today - $timestamp[0]),
			'today' => ($timestamp[0] == $today),
			'week'  => ($timestamp[1] == $week),
			'month' => ($timestamp[2] == $month),
		);
	}
	/**
	 * Starts the timer, for debugging purposes
	 */
	public static function timerStart()
	{
		$mtime = microtime();
		$mtime = explode(' ', $mtime);
		self::$time_start = $mtime[1] + $mtime[0];
	}

	/**
	 * Stops the debugging timer
	 * @return int total time spent on the query, in milliseconds
	 */
	public static function timerStop($restart = false)
	{
		$mtime = microtime();
		$mtime = explode(' ', $mtime);
		$time_end = $mtime[1] + $mtime[0];
		$time_total = $time_end - self::$time_start;
		$restart && self::$time_start = $time_end;
		return round($time_total, 4);
	}
	public static function redirect($url = '', $flag = null)
	{
		if ($flag) {
			//防止从重复跳转
			$redirect_num = (int)Cookie::get('redirect_num');
			if ($redirect_num) {
				$url = iPHP_URL;
				Cookie::set('redirect_num', '', -31536000);
			} else {
				Cookie::set('redirect_num', ++$redirect_num);
			}
		}
		$url or $url = iPHP_REFERER;
		if (@headers_sent()) {
			echo '<meta http-equiv=\'refresh\' content=\'0;url=' . $url . '\'><script type="text/javascript">window.location.replace(\'' . $url . '\');</script>';
		} else {
			header("Location: $url");
		}
		exit;
	}
	public static function buffer()
	{
		@set_time_limit(0);
		@header('Cache-Control: no-cache');
		@header('X-Accel-Buffering: no');
		ob_start();
		ob_end_clean();
		ob_end_flush();
		ob_implicit_flush(true);
	}
	public static function flushEnd()
	{
		ob_end_clean();
		ob_end_flush();
		ob_implicit_flush(true);
	}
	public static function flush()
	{
		flush();
		ob_flush();
	}
}
