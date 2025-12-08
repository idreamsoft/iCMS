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

class Cache
{
    public static $DATA = null;
    public static $instance = null;
    /**
     * 缓存句柄
     * @var FileCache|RedisClient|MemcachedClient
     */
    public static $handle = null;
    protected static $config = [
        'enable'	  => true,
        'engine'	  => 'file', //memcached,redis,file
        'host'		   => '', //127.0.0.1:6379,/tmp/redis.sock,127.0.0.1:11211,
        'db'		     => '1', //1
        'compress'	=> '9',
        'time'		   => '86400',
    ];

    public static function init($config, $reset = null)
    {
        self::$config = $config;

        $reset === null && isset($config['reset']) && $reset = $config['reset'];
        $reset && self::destroy();

        if (self::$handle) {
            return self::$handle;
        }
        self::$config['engine'] or self::$config['engine'] = 'file';
        return self::connect();
    }

    public static function connect($config = null, $flag = true)
    {
        is_null($config) && $config = self::$config;
        $handle = $flag ? self::$handle : null;

        if ($handle === null) {
            switch ($config['engine']) {
                case 'memcached':
                    $_servers = explode("\n", str_replace(["\r", ' '], '', $config['host']));
                    $handle = Vendor::run('MemcachedClient', [
                        'servers'            => $_servers,
                        'compress_threshold' => 10240,
                        'persistant'         => false,
                        'debug'              => false,
                        'compress'           => $config['compress'],
                    ], true);
                    unset($_servers);
                    break;
                case 'redis':
                    list($hosts, $db, $passwd) = explode('@', trim($config['host']));
                    list($host, $port) = explode(':', $hosts);
                    if (strstr($hosts, 'unix:') !== false) {
                        $host = $hosts;
                        $port = 0;
                    }
                    $db = (int) str_replace('db:', '', $db);
                    $db == '' && $db = 1;

                    $handle = Vendor::run('RedisClient', [
                        'host'     => $host,
                        'port'     => $port,
                        'db'       => $db,
                        'passwd'   => $passwd,
                        'compress' => $config['compress'],
                    ], true);
                    break;
                case 'file':
                    list($dirs, $level) = explode(':', $config['host']?:'');
                    $level or $level = 0;
                    $handle = new FileCache([
                        'dirs'     => $dirs,
                        'level'    => $level,
                        'compress' => $config['compress'],
                    ]);
                    break;
            }
        }
        $flag && self::$handle = $handle;
        return $handle;
    }

    public static function prefix($keys = null, $prefix = null)
    {
        $prefix === null && $prefix = self::$config['prefix'];
        if ($prefix) {
            $keys = is_array($keys) ?
                array_map(__METHOD__, $keys) :
                sprintf('%s/%s', $prefix, $keys);
        }
        if (self::$config['engine'] != 'file') {
            $keys = str_replace('/', ':', $keys);
        }
        return $keys;
    }

    public static function temp($key, $callable, $param = [])
    {
        is_array($key) && $key = md5(json_encode($key));
        $result = isset(self::$DATA[$key]) ? self::$DATA[$key] : null;
        if (empty($result)) {
            $result = call_user_func_array($callable, $param);
            $result && self::$DATA[$key] = $result;
        }
        return $result;
    }

    /**
     * 键值缓存回调函数结果
     *
     * @param string $key 键名
     * @param callable $callable 回调函数
     * @param string $expire 过期时间 0永久，-1系统缓存时间，true不缓存
     * @return mixed
     */
    public static function KV($key, $callable, $expire = '-1')
    {
        $result = self::get($key);
        if (empty($result) || $expire === true) {
            $result = call_user_func($callable);
            $result && self::set($key, $result, $expire);
        }
        return $result;
    }

    public static function get($keys, $ckey = null)
    {
        try {
            self::connect();
            $keys = self::prefix($keys);
            $key = implode('', (array) $keys);
            if (!isset(self::$CACHE[$key])) {
                self::$DATA[$key] = is_array($keys) ?
                    self::$handle->get_multi($keys) :
                    self::$handle->get($keys);
            }
            if ($ckey === null) {
                $data = self::$DATA[$key];
            } else {
                $data = self::$DATA[$key][$ckey];
            }
            return ($data === null || $data === false) ? null : $data;
        } catch (\Exception $ex) {
            self::throwError($ex);
        }
    }

    public static function set($keys, $data, $expire = '-1')
    {
        try {
            self::connect();
            $keys = self::prefix($keys);
            if (self::$config['engine'] == 'memcached') {
                self::$handle->delete($keys);
            }
            self::$handle->add($keys, $data, ($expire < 0 ? self::$config['time'] : $expire));
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
        if ($keys) {
            foreach ($keys as $value) {
                self::$handle->delete($value);
            }
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

    public static function file()
    {
        return new FileCache([
            'dirs'     => '',
            'level'    => 0,
            'compress' => 1,
        ]);
    }

    public static function redis($host = '127.0.0.1:6379@db:1', $time = '86400')
    {
        return self::connect([
            'enable' => true,
            'reset'  => true,
            'engine' => 'redis',
            'host'   => $host,
            'time'   => $time,
        ], false);
    }

    public static function destroy()
    {
        self::$handle = null;
        self::$DATA = [];
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
