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
class File
{
	public static function exist($file)
	{
		return @stat($file) === false ? false : true;
	}
	public static function is_file($file)
	{
		return @is_file($file);
	}

	public static function is_dir($file)
	{
		return @is_dir($file);
	}

	public static function is_readable($file)
	{
		return @is_readable($file);
	}

	public static function is_writable($file)
	{
		return @is_writable($file);
	}

	public static function atime($file)
	{
		return @fileatime($file);
	}

	public static function mtime($file)
	{
		return @filemtime($file);
	}

	public static function check($fn)
	{
		$tmpname = strtolower($fn);
		$tmparray = array('://', "\0", "%00", '..');
		if (str_replace($tmparray, '', $tmpname) != $tmpname) {
			throw new sException('File check failed');
		}
		return true;
	}

	public static function rm($fn, $check = 1)
	{
		$check && self::check($fn);
		@chmod($fn, 0777);
		$del = @unlink($fn);
		Hooks::call('File.delete', [$fn]);
		return $del;
	}

	public static function get($fn, $check = 1, $method = "rb")
	{
		$check && self::check($fn);
		if (function_exists('file_get_contents') && $method != "rb") {
			$filedata = file_get_contents($fn);
		} else {
			if ($handle = fopen($fn, $method)) {
				flock($handle, LOCK_SH);
				$filedata = @fread($handle, (int) filesize($fn));
				fclose($handle);
			}
		}
		return $filedata;
	}

	public static function put($path, $data, $check = 1, $method = "wb+", $iflock = 1, $chmod = 0)
	{
		$check && self::check($path);
		self::mkdir(dirname($path));
		@touch($path);
		$handle = fopen($path, $method);
		$iflock && flock($handle, LOCK_EX);
		fwrite($handle, $data);
		$method == "rb+" && ftruncate($handle, strlen($data));
		fclose($handle);
		$chmod && @chmod($path, 0644);
		Hooks::call('File.put', [$path, $data]);
	}
	// 将内容添加在文件原内容前面
	public static function prepend($path, $data)
	{
		$data = self::get($path) . $data;
		self::put($path, $data);
	}
	// 将内容添加在文件原内容后
	public static function append($path, $data)
	{
		self::put($path, $data, 1, 'ab+');
	}
	public static function copy($path, $target)
	{
		if (self::exist($path)) {
			self::mkdir(dirname($target));
			$data = self::get($path);
			return self::put($target, $data);
		}
		return false;
	}
	/**
	 * 目录转换
	 * @param  $dir
	 * @return string
	 */
	public static function escapeDir($dir)
	{
		$dir = str_replace(array("'", '#', '=', '`', '$', '%', '&', ';', "\0"), '', $dir);
		return rtrim(preg_replace('/(\/){2,}|(\\\){1,}/', '/', $dir), '/');
	}
	//创建目录
	public static function mkdir($d)
	{
		$d = self::escapeDir($d);
		$d = str_replace('//', '/', $d);
		if (file_exists($d)) {
			return @is_dir($d);
		}

		// Attempting to create the directory may clutter up our display.
		if (@mkdir($d)) {
			$stat = @stat(dirname($d));
			$dir_perms = $stat['mode'] & 0007777;  // Get the permission bits.
			@chmod($d, $dir_perms);
			return true;
		} elseif (is_dir(dirname($d))) {
			return false;
		}

		// If the above failed, attempt to create the parent node, then try again.
		if (($d != '/') && (self::mkdir(dirname($d)))) {
			return self::mkdir($d);
		}

		return false;
	}
	public static function checkDir($dirpath)
	{
		if (empty($dirpath)) {
			return false;
		}
		$dirpath = rtrim($dirpath, '/') . '/';
		$test = $dirpath . 'test.txt';
		if ($fp = @fopen($test, "wb")) {
			@fclose($fp);
			@unlink($test);
			return true;
		} else {
			return false;
		}
	}
	//删除目录
	public static function rmdir($dir, $df = true, $ex = NULL)
	{
		if (iPHP_PATH == rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) {
			return false;
		}
		$exclude = array('.', '..');
		$ex && $exclude = array_merge($exclude, (array) $ex);
		if ($dh = @opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if (!in_array($file, $exclude)) {
					$path = $dir . '/' . $file;
					is_dir($path) ? self::rmdir($path, $df) : ($df ? @unlink($path) : null);
				}
			}
			closedir($dh);
		}
		return @rmdir($dir);
	}
	public static function getDirSize($dir)
	{
		$pattern = sprintf("%s/*", $dir);
		$variable = glob($pattern);
		$size = 0;
		if ($variable) foreach ($variable as $key => $value) {
			if (is_dir($value)) {
				$size += self::getDirSize($value);
			} else {
				$size += filesize($value);
			}
		}
		return $size;
	}
	//获取文件夹下所有文件/文件夹列表
	public static function fileList($dir, $pattern = '*')
	{
		$lists = array();
		$dir   = rtrim($dir, '/');
		self::check($dir);
		foreach (glob($dir . '/' . $pattern) as $value) {
			$lists[] = $value;
			if (is_dir($value)) {
				$_lists = self::fileList($value, $pattern);
				$lists  = array_merge($lists, $_lists);
			}
		}
		return (array)$lists;
	}

	public static function info($path)
	{
		return (object) pathinfo($path);
	}

	public static function path($p = '')
	{
		$p = str_replace("\0", '', $p);
		$end = substr($p, -1);
		$a = explode('/', $p);
		$o = array();
		$c = count($a);
		for ($i = 0; $i < $c; $i++) {
			if ($a[$i] == '.' || $a[$i] == '') {
				continue;
			}

			if ($a[$i] == '..' && $i > 0 && end($o) != '..') {
				array_pop($o);
			} else {
				$o[] = $a[$i];
			}
		}

		$o[0] == 'https:' && $o[0] = 'https:/';
		$o[0] == 'http:' && $o[0] = 'http:/';

		return ($p[0] == '/' ? '/' : '') . implode('/', $o) . ($end == '/' ? '/' : '');
	}

	public static function path_is_absolute($path)
	{
		// this is definitive if true but fails if $path does not exist or contains a symbolic link
		if (@realpath($path) == $path) {
			return true;
		}

		if (strlen($path) == 0 || $path[0] == '.') {
			return false;
		}

		// windows allows absolute paths like this
		if (preg_match('#^[a-zA-Z]:\\\\#', $path)) {
			return true;
		}

		// a path starting with / or \ is absolute; anything else is relative
		return (bool) preg_match('#^[/\\\\]#', $path);
	}

	public static function pathMerge($base, $path, $rtrim = false)
	{
		//if (!self::path_is_absolute($path))

		$path = rtrim($base, '/') . '/' . ltrim($path, '/');
		$path = self::path($path);
		$rtrim && $path = rtrim($path, '/') . '/';
		return $path;
	}

	//文件名
	public static function name($fn)
	{
		$_fn = substr(strrchr($fn, "/"), 1);
		return substr($_fn, 0, strrpos($_fn, "."));
	}
	public static function getPath($fn)
	{
		// $_fn = substr(strrchr($fn, "/"), 1);
		return substr($fn, 0, strrpos($fn, "."));
	}
	// 获得文件扩展名
	public static function getExt($fn)
	{
		if (Request::isUrl($fn)) {
			$fn = parse_url($fn, PHP_URL_PATH);
		}
		return pathinfo($fn, PATHINFO_EXTENSION);
	}

	// 获取文件大小
	public static function sizeUnit($filesize)
	{
		$SU = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
		$n = 0;
		while ($filesize >= 1024) {
			$filesize /= 1024;
			$n++;
		}
		return round($filesize, 2) . ' ' . $SU[$n];
	}
}
