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
//array(
//	'enable'	=> true,false,
//	'engine'	=> memcached,redis,file,
//	'host'		=> 127.0.0.1,/tmp/redis.sock,
//	'port'		=> 11211,
//	'db'		=> 1,
//	'compress'	=> 1-9,
//	'time'		=> 0,
//)
class Cache
{
	public static $DATA = null;
	public static $instance = null;
	public static $handle = null;
	protected static $config = null;

	public static function init($config, $reset = null)
	{
		self::$config = $config;

		$reset === null && $reset = $config['reset'];
		$reset && self::destroy();

		if (self::$handle) {
			return self::$handle;
		}
		self::$config['engine'] or self::$config['engine'] = 'file';
		return self::connect();
	}
	public static function connect()
	{
		if (self::$handle === null) {
			switch (self::$config['engine']) {
				case 'memcached':
					$_servers = explode("\n", str_replace(array("\r", " "), "", self::$config['host']));
					self::$handle = Vendor::run('MemcachedClient', array(
						'servers' => $_servers,
						'compress_threshold' => 10240,
						'persistant' => false,
						'debug' => false,
						'compress' => self::$config['compress'],
					), true);
					unset($_servers);
					break;
				case 'redis':
					list($hosts, $db, $passwd) = explode('@', trim(self::$config['host']));
					list($host, $port) = explode(':', $hosts);
					if (strstr($hosts, 'unix:') !== false) {
						$host = $hosts;
						$port = 0;
					}
					$db = (int) str_replace('db:', '', $db);
					$db == '' && $db = 1;

					self::$handle = Vendor::run('RedisClient', array(
						'host' => $host,
						'port' => $port,
						'db' => $db,
						'passwd' => $passwd,
						'compress' => self::$config['compress'],
					), true);
					break;
				case 'file':
					list($dirs, $level) = explode(':', self::$config['host']);
					$level or $level = 0;
					self::$handle = new FileCache(array(
						'dirs' => $dirs,
						'level' => $level,
						'compress' => self::$config['compress'],
					));
					break;
			}
		}
		return self::$handle;
	}
	public static function prefix($keys = null, $prefix = null)
	{
		$prefix === null && $prefix = self::$config['prefix'];
		if ($prefix) {
			$keys = is_array($keys) ?
				array_map(__METHOD__, $keys) :
				sprintf('%s/%s', $prefix, $keys);
		}
		return $keys;
	}
	public static function get($keys, $ckey = NULL, $unserialize = true)
	{
		try {
			self::connect();
			$keys = self::prefix($keys);
			$_keys = implode('', (array) $keys);
			if (!isset($GLOBALS['iPHP_CACHE'][$_keys])) {
				$GLOBALS['iPHP_CACHE'][$_keys] = is_array($keys) ?
					self::$handle->get_multi($keys, $unserialize) :
					self::$handle->get($keys, $unserialize);
			}
			return $ckey === NULL ?
				$GLOBALS['iPHP_CACHE'][$_keys] :
				$GLOBALS['iPHP_CACHE'][$_keys][$ckey];
		} catch (\Exception $ex) {
			self::throwError($ex);
		}
	}
	public static function set($keys, $data, $cachetime = "-1")
	{
		try {
			self::connect();
			$keys = self::prefix($keys);
			if (self::$config['engine'] == 'memcached') {
				self::$handle->delete($keys);
			}
			self::$handle->add($keys, $data, ($cachetime != "-1" ? $cachetime : self::$config['time']));
		} catch (\Exception $ex) {
			self::throwError($ex);
		}
	}
	public static function clean($keys = null)
	{
        if (self::$config['engine'] == 'memcached') {
			return;
        }
		$keys = self::prefix($keys);
		$keys = self::$handle->keys($keys);
		// var_dump($keys);
		if ($keys) foreach ($keys as $value) {
			self::$handle->delete($value);
		}
	}

	public static function delete($key = '', $time = 0)
	{
		try {
			$key = self::prefix($key);
			self::connect();
			self::$handle->delete($key, $time);
		} catch (\Exception $ex) {
			self::throwError($ex);
		}
	}

	public static function newFileCache()
	{
		return new FileCache(array(
			'dirs' => '',
			'level' => 0,
			'compress' => 1,
		));
	}
	public static function redis($host = '127.0.0.1:6379@db:1', $time = '86400')
	{
		if (self::$config['engine'] != 'redis') {
			self::init(array(
				'enable' => true,
				'reset' => true,
				'engine' => 'redis',
				'host' => $host,
				'time' => $time,
			));
		}
	}
	public static function destroy()
	{
		self::$handle = null;
	}
	public static function throwError($ex)
	{
		$msg = $ex->getMessage();
		$code = $ex->getCode();
		$file = $ex->getFile();
		$line = $ex->getLine();
		$error = sprintf('%s in %s on line %s', $msg, $file, $line);
		throw new CacheException($error, $code);
	}
}
class CacheException extends sException
{
}
