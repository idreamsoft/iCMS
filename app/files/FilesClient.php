<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilesClient
{
	public static $force_ext = false;
	public static $valid_ext = true;
	public static $config = null;
	public static $data = null;

	public static $ERROR = null;
	public static $EXTS = array(
		"png", "jpg", "jpeg", "gif", "bmp", "webp", "psd", "tif", "svg",
		"flv", "swf", "mkv", "avi", "rm", "rmvb", "mpeg", "mpg", "mp4", "m3u8",
		"ogg", "ogv", "mov", "wmv", "webm", "mp3", "aac", "m4a", "wav", "mid", "amr",
		"rar", "zip", "tar", "gz", "7z", "bz2", "cab", "iso",
		"doc", "docx", "xls", "xlsx", "ppt", "pptx", "pdf", "txt", "md", "xml",
		"apk", "ipa",
		"js", "css", "json",
		"html", "htm", "shtml",
	);
	public static function init($config)
	{
		self::$config = $config;
	}
	public static function url($urls = null)
	{
		$urls === null && $urls = self::$config['url'];
		return trim($urls);
	}

	public static function attachment($path, $filename = null)
	{
		$info = pathinfo($path);
		$filename === null && $filename = $info['basename'];
		ob_end_clean();
		header("Content-Type: application/force-download");
		header("Content-Transfer-Encoding: binary");
		header('Content-Type: ' . FilesMime::get($info['extension']));
		header('Content-Disposition: attachment; filename=' . $filename);
		header('Content-Length: ' . filesize($path));
		readfile($path);
		flush();
		ob_flush();
	}
	//获取文件夹列表
	public static function folder($dir = '', $type = NULL)
	{
		$dir = trim($dir, '/');
		File::check($dir);

		if ($_GET['dir']) {
			$gDir = trim($_GET['dir'], '/');
			File::check($gDir);
		}
		$sDir_PATH = File::pathMerge(iPHP_PATH, $dir);
		$iDir_PATH = File::pathMerge($sDir_PATH, $gDir);

		strpos($iDir_PATH, $sDir_PATH) === false && self::throwError('DIR_ERROR');

		if (!is_dir($iDir_PATH)) {
			return false;
		}

		$url = Route::make('dir');

		list($dirArray, $fileArray) = self::getdirs($iDir_PATH, $sDir_PATH, $url);
		$a['DirArray']  = (array) $dirArray;
		$a['FileArray'] = (array) $fileArray;
		$a['pwd']       = str_replace($sDir_PATH, '', $iDir_PATH);
		$a['pwd']       = trim($a['pwd'], '/');
		$pos            = strripos($a['pwd'], '/');
		$a['parent']    = ltrim(substr($a['pwd'], 0, $pos), '/');
		$a['URI']       = $url;
		return $a;
	}
	public static function getDirs($dir, $rdir = null, $url = null)
	{
		$dir = iconv('utf-8', 'gb2312', $dir);
		$rdir = iconv('utf-8', 'gb2312', $rdir);

		$dirArray = array();
		$fileArray = array();
		if ($handle = opendir($dir)) {
			while (false !== ($file = readdir($handle))) {
				$filepath = File::pathMerge($dir, $file);
				$filepath = rtrim($filepath, '/');

				$sFileType = @filetype($filepath);
				$path = str_replace($rdir, '', $filepath);
				$path = ltrim($path, '/');
				if ($sFileType == "dir" && !in_array($file, array('.', '..', 'admincp', 'iPHP', 'core', '.git', '.svn'))) {
					$da = array(
						'path' => $path,
						'name' => $file,
					);
					$url && $da['url'] = $url . urlencode($path);
					$dirArray[] = $da;
				}
				if ($sFileType == "file" && !in_array($file, array('..'))) {
					if (!self::checkExt($file)) {
						continue;
					}
					$filext = File::getExt($file);
					$fa = array(
						'path'     => $path,
						'dir'      => dirname($path),
						'name'     => $file,
						'modified' => get_date(filemtime($filepath), "Y-m-d H:i:s"),
						'md5'      => md5_file($filepath),
						'ext'      => $filext,
						'size'     => File::sizeUnit(filesize($filepath)),
					);
					$url && $fa['url'] = self::getUrl($path);
					if ($type) {
						in_array(strtolower($filext), $type) && $fileArray[] = $fa;
					} else {
						$fileArray[] = $fa;
					}
				}
			}
		}
		return array($dirArray, $fileArray);
	}
	public static function getDir()
	{
		$dir = File::pathMerge(iPHP_PATH, self::$config['dir']);
		return rtrim($dir, '/') . '/';
	}

	public static function createDir($udir = null, $md5 = null, $ext = null)
	{
		$FileDir = self::$config['dir_format'];
		preg_match_all('@\{(.+?)\}@', $FileDir, $matches);
		if ($matches[1]) foreach ($matches[1] as $key => $value) {
			if (substr($value, 0, 4) == 'md5:') {
				$format = substr($value, 4);
				list($start, $len) = explode(',', $format);
				if ($len === null) {
					$len   = $start;
					$start = 0;
				}
				$dir = substr($md5, $start, $len);
			} elseif ($value == 'EXT') {
				$dir = $ext;
			} else {
				if (substr($value, 0, 5) == 'date:') {
					$value = substr($value, 5);
				}
				$dir = get_date(0, $value);
			}
			$FileDir = str_replace($matches[0][$key], $dir, $FileDir);
		}

		if ($udir) {
			File::check($udir);
			$FileDir = $udir;
		}

		$FileDir  = rtrim($FileDir, '/') . '/';
		$FileDir  = ltrim($FileDir, './');
		$RootPath = self::getDir() . $FileDir;
		$RootPath = rtrim($RootPath, '/') . '/';
		File::mkdir($RootPath) or self::throwError('MKDIR', $RootPath);
		return array($RootPath, $FileDir);
	}

	public static function saveUploadFile($file, $path)
	{
		$flag = true;
		if (function_exists('move_uploaded_file') && $flag = move_uploaded_file($file, $path)) {
			@chmod($path, 0644);
		} elseif ($flag = copy($file, $path)) {
			@chmod($path, 0644);
		} elseif (is_readable($file) && is_writable($path)) {
			if ($path = @fopen($file, 'rb')) {
				@flock($path, 2);
				$filedata = @fread($path, @filesize($file));
				@fclose($path);
			}
			if ($path = fopen($path, 'wb')) {
				@flock($path, 2);
				@fwrite($path, $filedata);
				@fclose($path);
				@chmod($path, 0644);
			}
		} else {
			$flag = false;
		}
		@unlink($file);
		return $flag ? true : self::throwError('UPLOAD_ERROR');
	}
	/**
	 * 流数据
	 */
	public static function input($name = '', $udir = '', $ext = 'jpg', $type = '3', $data = null)
	{
		$data === null && $data = Request::input();
		if (empty($data)) {
			return false;
		}
		$name && File::check($name);
		$hash = md5($data);
		$name or $name = $hash;

		$size = strlen($data);
		$source = $name . "." . $ext;
		$ext = self::validExt($source); //判断文件类型
		list($rootPath, $dir) = self::createDir($udir, $hash, $ext); // 文件保存目录方式
		$path = $dir . $source;
		$filePath = $rootPath . $source;
		File::put($filePath, $data);
		$data = compact('name', 'source', 'path', 'intro', 'ext', 'size');
		$data['id'] = files::insert($data, $type);
		self::hook('upload', array($filePath, $ext));
		return $data;
	}
	public static function base64ToFile($base64Data, $udir = '', $FileExt = 'png')
	{
		if (empty($base64Data)) {
			return false;
		}

		$filedata = base64_decode($base64Data);
		return self::input(null, $udir, $FileExt, '2', $filedata);
	}

	public static function upload($field = 'upfile', $udir = '', $FileName = '', $ext = '')
	{
		$file = Request::file($field);
		if ($file['name']) {
			$tmp_file = $file['tmp_name'];
			if (!is_uploaded_file($tmp_file)) {
				self::throwError('NO_UPLOADED_FILE');
			}
			if ($file['error'] > 0) {
				@unlink($tmp_file);
				switch ((int) $file['error']) {
					case UPLOAD_ERR_NO_FILE:
						self::throwError('NO_FILE');
						break;
					case UPLOAD_ERR_FORM_SIZE:
						self::throwError('UPLOAD_MAX');
						break;
				}
				self::throwError($file['error']);
			}
			$source = $file['name'];
			empty($ext) && $ext = self::validExt($source); //判断文件类型

			if (self::$data) {
				extract(self::$data);
			} else {
				$name = md5_file($tmp_file);
				$size = @filesize($tmp_file);
				if ($frs  = files::getData('name', $name)) {
					return $frs;
				};
			}
			list($rootPath, $dir) = self::createDir($udir, $name, $ext); // 文件保存目录方式

			$FileName && File::check($FileName);
			$FileName or $FileName = $name;
			$path = $dir . $FileName . "." . $ext;

			self::validExt($path); //判断文件类型
			$filePath = self::getRoot($path);

			self::saveUploadFile($tmp_file, $filePath);

			$data = compact('name', 'source', 'path', 'intro', 'ext', 'size');
			if ($id) {
				files::update(compact('source', 'size'), $id);
				$data['id'] = $id;
			} else {
				$data['id'] = files::insert($data, 0);
			}
			self::hook('upload', array($filePath, $ext));
			return $data;
		} else {
			return false;
		}
	}
	public static function checkImageBin($path, $bin = false)
	{
		if (empty($path)) {
			return false;
		}
		if ($bin) {
			$head = substr($path, 0, 16);
		} else {
			if (!is_file($path)) return false;

			$fh = fopen($path, "rb");
			//必须使用rb来读取文件，这样能保证跨平台二进制数据的读取安全
			//仅读取前面的16个字节
			$head = fread($fh, 16);
			fclose($fh);
		}

		$arr = unpack("C*", $head);
		$string = null;
		foreach ($arr as $k => $C) {
			if ($C >= 48 && $C <= 127) {
				$string .= chr($C);
			}
		}
		if (empty($string)) {
			return false;
		}

		$string = strtoupper($string);
		$format = array(
			'JFIF', 'TIFF', 'RIFF',
			'PNG', 'GIF89A', 'GIF87A', 'JPEG',
			'BMP', 'WEBP',
			'JPEG 2000', 'EXIF', 'BPG', 'SVG'
		);
		foreach ($format as $key => $f) {
			if (strpos($string, $f) !== false) {
				return $f;
			}
		}
		return false;
	}
	public static function allowExt($exts, $allow = null)
	{
		$exts_array = explode(',', $exts);
		is_null($allow) && $allow = self::$EXTS;
		foreach ($exts_array as $key => $ext) {
			if (!in_array($ext, $allow)) {
				return false;
			}
		}
		return true;
	}

	public static function checkExt($ext, $allow = null)
	{
		$ext = File::getExt($ext);
		$ext = strtolower($ext);
		return self::allowExt($ext, $allow);
	}
	public static function validExt($fn)
	{
		$_ext = strtolower(File::getExt($fn));
		$ext = self::allowExt($_ext) ? $_ext : 'file';

		if (self::$force_ext !== false) {
			if (empty($_ext) || strlen($_ext) > 4 || $ext == 'file') {
				$ext = self::$force_ext;
			}
			return $ext;
		}
		if (!self::$valid_ext) {
			return $ext;
		}

		$ext_array = explode(',', strtolower(self::$config['allow_ext']));
		if (in_array($_ext, $ext_array)) {
			return $ext;
		} else {
			self::throwError('TYPE', $fn);
		}
	}
	public static function getRoot($path)
	{
		return self::getPath($path, '+root');
	}
	public static function getUrl($path)
	{
		return self::getPath($path, '+http');
	}
	public static function getPath($f, $m = '+http', $_config = null)
	{
		$config = $_config ? $_config : self::$config;
		$url = self::$config['url'];
		switch ($m) {
			case '+http':
				$fp = rtrim($url, '/') . '/' . ltrim($f, '/');
				Request::isUrl($f) && $fp = $f;
				break;
			case '-http':
				$fp = str_replace($url, '', $f);
				break;
			case 'http2root':
				$f = str_replace($url, '', $f);
				$fp = File::pathMerge(iPHP_PATH, $config['dir'], '/') . ltrim($f, '/');
				break;
			case 'root2http':
				$f = str_replace(File::pathMerge(iPHP_PATH, $config['dir']), '', $f);
				$fp = $url . $f;
				break;
			case '+root':
				$fp = File::pathMerge(iPHP_PATH, $config['dir'], '/') . ltrim($f, '/');
				break;
			case '-root':
				$fp = str_replace(File::pathMerge(iPHP_PATH, $config['dir']), '', $f);
				break;
		}
		return $fp;
	}
	public static function name($path)
	{
		$path = trim($path);
		if (Request::isUrl($path)) {
			$url = self::url();
			$host = parse_url($url, PHP_URL_HOST);
			if (stripos($path, $host) !== false) {
				$path = self::getPath($path, '-http');
			} else {
				return false;
			}
		}
		$name = basename($path);
		$name = substr($name, 0, 32);
		return $name;
	}
	//--------upload---end-------------------------------
	public static function remote($http, &$ret = null, $times = 0)
	{
		$frs = files::getData('source', $http);

		if ($frs) {
			if (is_array($ret)) {
				$ret = $frs;
				return true;
			}
			return $frs['path'];
		}
		$ext = self::validExt($http); //判断过滤文件类型

		$fdata = Http::remote($http);
		if ($fdata) {
			$name = md5($fdata);
			list($rootPath, $dir) = self::createDir(null, $name, $ext); // 文件保存目录方式
			$frs = files::getData('name', $name);
			if ($frs) {
				$path = $frs['path'];
				$filePath = self::getRoot($path);
				if (!is_file($filePath)) {
					File::mkdir(dirname($filePath));
					File::put($filePath, $fdata);
					self::hook('upload', array($filePath, $ext));
				}
				if (is_array($ret)) {
					$ret = $frs;
					return true;
				}
			} else {
				$FileName = $name . "." . $ext;
				$path = $dir . $FileName;
				$filePath = $rootPath . $FileName;
				File::put($filePath, $fdata);
				$size = @filesize($filePath) ?: 0;
				$source = $http;
				$data = compact('name', 'source', 'path', 'intro', 'ext', 'size');
				$data['id'] = files::insert($data, 1);
				self::hook('upload', array($filePath, $ext));
				if (is_array($ret)) {
					$ret = $data;
					return true;
				}
			}
			return $path;
		} else {
			self::throwError('REMOTE_EMPTY');
		}
	}

	public static function hook($key, $param)
	{
		return Hooks::call("FilesClient.{$key}", $param);
	}
	public static function checkConf($config)
	{
		if (!self::allowExt($config['allow_ext'])) {
			return "附件 > 允许上传类型设置不合法";
		}
		$forbidArray = ['template', 'app', 'cache', 'config', 'core', 'iPHP'];
		$text = implode("，", $forbidArray);
		foreach ($forbidArray as $key => $forbid) {
			if (strpos($config['dir'], $forbid) !== false) {
				return "附件 > 文件保存目录,禁止出现" . $text . "等字符";
			}
			if (strpos($config['dir_format'], $forbid) !== false) {
				return "附件 > 目录结构,禁止出现" . $text . "等字符";
			}
		}
		if (substr_count($config['dir'], '..') > 2) {
			return "附件 > 文件保存目录,禁止..字符超过两次";
		}
		if (strpos($config['dir_format'], '..') !== false) {
			return "附件 > 目录结构,禁止出现[..]等字符";
		}
	}
	public static function throwError($type, $msg = null)
	{
		$stateMap = array(
			"UPLOAD_MAX" => "文件大小超出 upload_max_filesize 限制",
			"MAX_FILE_SIZE" => "文件大小超出 MAX_FILE_SIZE 限制",
			"文件未被完整上传",
			"没有文件被上传",
			"NO_UPLOADED_FILE" => "上传文件为空",
			"POST" => "文件大小超出 post_max_size 限制",
			"SIZE" => "文件大小超出网站限制",
			"TYPE" => "不允许的文件类型",
			"IO" => "IO错误",
			"UNKNOWN" => "未知错误",
			"ERROR" => "上传文件出错",
			"MOVE" => "文件保存时出错",
			"MKDIR_ERROR" => "目录创建失败",
			"UPLOAD_ERROR" => "上传文件出错",
			"DIR_ERROR" => "访问目录出错",
			"REMOTE_EMPTY" => "远程下载为空",
			"CHECK" => "检测出错"
		);
		$msg = sprintf('【%s】%s', $stateMap[$type], $msg);
		iPHP::throwError($msg, 'FileClient');
	}
}
