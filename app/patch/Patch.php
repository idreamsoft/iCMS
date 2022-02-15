<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
/**
 * 自动更新类
 *
 * @author icmsdev
 */
define('PATCH_DIR', iPHP_PATH . 'cache/iCMS/patch/'); //临时文件夹
Http::$CURLOPT_REFERER = $_SERVER['HTTP_URL'];

class Patch
{
	const PATCH_URL = "https://patch.icmsdev.com/v8";	//自动更新服务器
	const APP = 'patch';
	const APPID = iCMS_APP_PATCH;

	public static $version = '';
	public static $release = '';
	public static $zipName = '';
	public static $gitFiles = [];
	public static $upgrade = false;
	public static $test = false;

	public static function init($force = false)
	{
		$info = self::info($force);
		$gitDate = date("Ymd", iCMS_GIT_TIME);
		if ($info &&
			$info->app == iPHP_APP &&
			version_compare($info->version, iCMS_VERSION, '>=') &&
			$info->release > iCMS_RELEASE &&
			$info->release > $gitDate
		) {
			self::$version = $info->version;
			self::$release = $info->release;
			self::$zipName = sprintf('iCMS.%s.patch.%s.zip', self::$version, self::$release);
			return array(self::$version, self::$release, $info->update, $info->changelog);
		}
	}
	public static function setTime()
	{
		$release = strtotime(iCMS_RELEASE);
		$gitTime = iCMS_GIT_TIME;
		$_GET['iCMS_RELEASE'] && $release = strtotime($_GET['iCMS_RELEASE']);
		$_GET['iCMS_GIT_TIME'] && $gitTime = $_GET['iCMS_GIT_TIME'];
		Cache::set('patch.time', array($release, $gitTime), 3600);
	}
	public static function getTime()
	{
		return (array) Cache::get('patch.time');
	}

	public static function git($do, $commit_id = null, $type = 'array')
	{
		$commit_id === null && $commit_id = iCMS_GIT_COMMIT;
		$_GET['commit_id'] && $commit_id = Request::sget('commit_id');
		$last_commit_id = Request::sget('last_commit_id');
		$path = $_GET['path'];
		$url = sprintf(
			'%s/git?do=%s&VERSION=%s&RELEASE=%s&commit_id=%s&last_commit_id=%s',
			Patch::PATCH_URL,
			$do,
			iCMS_VERSION,
			iCMS_RELEASE,
			$commit_id,
			$last_commit_id
		);
		$path && $url .= '&path=' . urlencode($path);
		$url .= '&t=' . time();
		$data = Http::remote($url);
		$array = json_decode($data, true);
		if (is_null($array)) {
			throw new sException('iCMS版本服务出错请联系开发人员，谢谢！');
		}
		return $array;
	}
	public static function version($force = false)
	{
		$url = sprintf(
			"%s/cms.version?VERSION=%s&RELEASE=%s&iCMS_GIT_COMMIT=%s&callback=?",
			self::PATCH_URL,
			iCMS_VERSION,
			iCMS_RELEASE,
			iCMS_GIT_COMMIT
		);
		$json = Http::remote($url);
		if ($json) {
			echo $json;
		}
	}
	public static function info($force = false)
	{
		File::mkdir(PATCH_DIR);
		$tFilePath = PATCH_DIR . 'version.json'; //临时文件夹
		if (File::exist($tFilePath) && time() - File::mtime($tFilePath) < 3600 && !$force) {
			$FileData = File::get($tFilePath);
		} else {
			$url = sprintf(
				'%s/version.%s.%s.patch.%s?t=%d',
				self::PATCH_URL,
				iPHP_APP,
				iCMS_VERSION,
				iCMS_RELEASE,
				time()
			);
			$FileData = Http::remote($url);
			File::put($tFilePath, $FileData);
		}
		return json_decode($FileData); //版本列表
	}
	public static function download()
	{
		$zipFile = PATCH_DIR . self::$zipName; //临时文件
		$zipHttp = self::PATCH_URL . '/' . self::$zipName;
		$msg = '正在下载 [' . self::$release . '] 更新包 ' . $zipHttp . '<iCMS>';
		if (!File::exist($zipFile)) {
			try {
				$FileData = Http::remote($zipHttp);
				if ($FileData) {
					File::mkdir(PATCH_DIR);
					File::put($zipFile, $FileData); //下载更新包
					$msg .= '下载完成....<iCMS>';
				}			//code...
			} catch (\Exception $ex) {
				$msg .= '下载失败....<iCMS>';
				$msg .= $ex->getMessage();
			}
		}
		return $msg;
	}
	public static function update()
	{
		@set_time_limit(0);
		// Unzip uses a lot of memory
		@ini_set('memory_limit', '256M');
		Vendor::run('PclZip'); //加载zip操作类
		$zipFile = PATCH_DIR . self::$zipName; //临时文件
		$msg = '正在对 [' . self::$zipName . '] 更新包进行解压缩<iCMS>';
		$zip = new PclZip($zipFile);

		if (false == ($archive_files = $zip->extract(PCLZIP_OPT_EXTRACT_AS_STRING))) {
			exit("ZIP包错误");
		}

		if (0 == count($archive_files)) {
			exit("空的ZIP文件");
		}

		$msg .= '解压完成<iCMS>';
		$msg .= '开始测试目录权限<iCMS>';
		$update = true;
		if (!File::checkDir(iPHP_PATH)) {
			$update = false;
			$msg .= iPHP_PATH . ' 目录无写权限<iCMS>';
		}

		//测试目录文件是否写
		foreach ($archive_files as $file) {
			$folder = $file['folder'] ? $file['filename'] : dirname($file['filename']);
			$dp = iPHP_PATH . $folder;
			if (!File::checkDir($dp) && File::exist($dp)) {
				$update = false;
				$msg .= $dp . ' 目录无写权限<iCMS>';
			}
			if (empty($file['folder'])) {
				$fp = iPHP_PATH . $file['filename'];
				if (file_exists($fp) && !@is_writable($fp)) {
					$update = false;
					$msg .= $fp . ' 文件无写权限<iCMS>';
				}
			}
		}
		if (self::$gitFiles) foreach (self::$gitFiles as $git) {
			if ($git[0] == 'D') { //git删除资源
				$fp = iPHP_PATH . ltrim($git[1], '/');
				if (file_exists($fp) && !@is_writable($fp)) {
					$update = false;
					$msg .= $fp . ' 文件无删除权限<iCMS>';
				}
			}
		}
		if (!$update) {
			$msg .= '权限测试无法完成<iCMS>';
			$msg .= '请设置好上面提示的文件写权限<iCMS>';
			$msg .= '然后重新更新<iCMS>';
			self::$upgrade = false;
			$msg = Security::filterPath($msg);
			return $msg;
		}
		$msg .= '权限测试通过<iCMS>';
		//测试通过！
		$msg .= '备份目录创建完成<iCMS>';
		$bakdir = self::getBackupDir(self::$release);
		File::mkdir($bakdir);

		$msg .= '开始更新程序<iCMS>';

		foreach ($archive_files as $file) {
			preg_match('@^app/(\w+)/@', $file['filename'], $match);
			if ($match[1]) {
				if (!Apps::check($match[1]) && $match[1] != 'func') {
					$msg .= '应用 [' . $match[1] . '] 不存在,跳过[' . $file['filename'] . ']更新<iCMS>';
					continue;
				}
			}
			$folder = $file['folder'] ? $file['filename'] : dirname($file['filename']);
			$dp = iPHP_PATH . $folder;
			if (!File::exist($dp)) {
				$msg .= '创建 [' . $dp . '] 文件夹<iCMS>';
				File::mkdir($dp);
			}
			if (empty($file['folder'])) {
				$fp = iPHP_PATH . $file['filename'];
				$bfp = $bakdir . '/' . $file['filename'];
				File::mkdir(dirname($bfp));
				if (File::exist($fp)) {
					$msg .= '备份 [' . $fp . '] 文件 到 [' . $bfp . ']<iCMS>';
					File::copy($fp, $bfp); //备份旧文件
				}
				$msg .= '更新 [' . $fp . '] 文件<iCMS>';
				if (self::$test) {
					$msg .= '[' . $fp . '] 测试更新完成!<iCMS>';
				} else {
					File::put($fp, $file['content']);
					$msg .= '[' . $fp . '] 更新完成!<iCMS>';
				}
			}
		}
		if (self::$gitFiles) foreach (self::$gitFiles as $git) {
			if ($git[0] == 'D' && $git[1]) { //git删除资源
				$fp = iPHP_PATH . ltrim($git[1], '/');
				if (file_exists($fp) && !@is_writable($fp)) {
					$bfp = $bakdir . '/' . ltrim($git[1], '/');
					File::mkdir(dirname($bfp));
					if (File::exist($fp)) {
						$msg .= '备份 [' . $fp . '] 文件 到 [' . $bfp . ']<iCMS>';
						File::copy($fp, $bfp); //备份旧文件
						$msg .= '删除 [' . $fp . '] 文件<iCMS>';
						if (self::$test) {
							$msg .= '[' . $fp . '] 测试删除完成!<iCMS>';
						} else {
							File::rm($fp);
							$msg .= '[' . $fp . '] 删除完成!<iCMS>';
						}
					}
				}
			}
		}
		$msg .= '清除临时文件!<iCMS>';
		$msg .= '注:原文件备份在 [' . $bakdir . '] 目录<iCMS>';
		$msg .= '请暂时保留本次更新的备份，以备回滚使用!<iCMS>';

		$msg = Security::filterPath($msg);
		self::get_upgrade_files() && self::$upgrade = true;
		return $msg;
	}
	public static function get_upgrade_files($flag = false)
	{
		$files = array();
		$patch_dir = iAPP::path('patch/files');
		list($release, $gitTime) = self::getTime();
		foreach (glob($patch_dir . "*.{php,sql}", GLOB_BRACE) as $file) {
			$d = str_replace(array($patch_dir, 'db.', 'fs.', '.php', '.sql'), '', $file);
			if ($flag) {
				$files[$d] = $file;
			} else {
				$time = strtotime($d . '5959');
				if ($time > $release) {
					if ($gitTime) {
						if ($time > $gitTime) {
							$files[$d] = $file;
						} else {
							File::rm($file);
						}
					} else {
						$files[$d] = $file;
					}
				} else {
					File::rm($file);
				}
			}
		}
		return $files;
	}
	public static function run()
	{
		$flag  = isset($_GET['force']);
		$files = self::get_upgrade_files($flag);
		if ($files) {
			self::$upgrade = true;
			ksort($files);
			foreach ($files as $key => $file) {
				if (stripos($file, '.php') !== false) {
					$name = str_replace(['.php', '.'], ['', '_'], basename($file));
					$patch_name = 'patch_' . $name;
					$patch_func = require_once $file;
					$title = sprintf('%s[%s]', ($title ?: ''), $patch_name);
					if (is_callable($patch_func)) {
						$msg .= sprintf('执行%s升级程序<iCMS>', $title);
						try {
							if (self::$test) {
								$msg .= '测试升级顺利完成!<iCMS>';
							} else {
								$msg .= call_user_func($patch_func);
								$msg .= '升级顺利完成!<iCMS>';
								self::$test or File::rm($file);
							}
							$msg .= '删除升级程序!<iCMS>';
						} catch (\Exception $ex) {
							$msg = sprintf('%s升级出错<iCMS>', $title);
							$msg .= $ex->getMessage() . '<iCMS>';
						}
					} else {
						$msg = sprintf('%s升级出错<iCMS>', $title);
						$msg .= '找不到升级程序<iCMS>';
					}
				} elseif (stripos($file, '.sql') !== false) {
					$sql = file_get_contents($file);
					$name = basename($file);
					if ($sql) {
						try {
							if (self::$test) {
								$msg .= '测试SQL执行顺利完成!<iCMS>';
							} else {
								$prefix = DB::getTablePrefix();
								runQuery($sql, $prefix);
								$msg .= 'SQL执行顺利完成!<iCMS>';
								self::$test or File::rm($file);
							}
							$msg .= '删除文件!<iCMS>';
						} catch (\Exception $ex) {
							$msg = '[' . $name . ']SQL执行出错<iCMS>';
							$msg = $ex->getMessage() . '<iCMS>';
						}
					}
				}
			}
		} else {
			$msg = '升级顺利完成!';
		}
		self::$upgrade = false;
		return $msg;
	}

	public static $check_login = true;
	public static function getBackupDir($name)
	{
		return iPHP_PATH . '.backup/patch.' . $name;
	}
	public static function upgrade($func)
	{
		if (is_null(self::$upgrade)) {
			return;
		}
		if (self::$upgrade) {
			return $func;
		}
		if (self::$check_login) {
			try {
				Member::login();
			} catch (\Exception $ex) {
				exit("请先登录");
			}
		}

		$output = $func();
		is_array($output) && $output = implode('<br />', $output);
		$output = str_replace('<iCMS>', '<br />', $output);
		$output = Security::filterPath($output);
		echo $output;
		$path = strtr(iPHP_SELF, '\\', '/');
		$path = ltrim($path, '/');
		if (preg_match('@app/patch/files/\d+.php@', $path)) {
			File::rm(iPHP_PATH . $path);
		}
	}
}
