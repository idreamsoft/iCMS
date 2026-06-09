<?php

// namespace DataBase;

//https://learnku.com/docs/laravel/7.x/database/7493

// use DataBase\Manager;
// use ReflectionClass;
// use Closure;
// use Exception;

class DB
{
    private static $handle = [];
    private static $config = [];
    private static $debug = [
        'log'     => false,
        'trace'   => false,
        'explain' => false,
    ];
    private static $connectionTimeout = 3600; // 连接超时时间（秒）
    private static $maxConnections = 10; // 最大连接数
    private static $connectionTimestamps = []; // 连接创建时间戳

    // public static $lastId;
    // public static $rowCount; //返回DELETE、 INSERT、或 UPDATE 语句受影响的行数。

    public function __call($method, $params)
    {
        return self::__called($method, $params);
    }

    public static function __callStatic($method, $params)
    {
        return self::__called($method, $params);
    }

    private static function __called($method, $params)
    {
        $instance = static::connection();
        $rc = new ReflectionClass($instance);
        if ($rc->hasMethod($method)) {
            $call = [$instance, $method];
            return call_user_func_array($call, $params);
        } else {
            throw new Exception(sprintf( '%s::%s(%s) method does not exist.', get_class($instance),$method,implode(', ', $params)));
        }
    }

    public function __construct()
    {
    }

    /**
     * 数据库连接
     *
     * @param string $name 数据库连接名,默认使用default
     * @return Connection
     */
    public static function connection($name = null)
    {
        // 如果没有指定连接名，使用默认连接
        if ($name === null) {
            $name = Manager::getInstance()->getDefaultConnection();
        }
        
        // 为每个连接名创建唯一的缓存键
        $cacheKey = $name ?: 'default';
        
        // 检查连接是否过期
        if (isset(self::$handle[$cacheKey])) {
            $timestamp = isset(self::$connectionTimestamps[$cacheKey]) ? self::$connectionTimestamps[$cacheKey]: 0;
            if (time() - $timestamp > self::$connectionTimeout) {
                self::purgeConnection($cacheKey);
            }
        }
        
        // 检查最大连接数
        if (empty(self::$handle[$cacheKey]) && count(self::$handle) >= self::$maxConnections) {
            // 清理最旧的连接
            asort(self::$connectionTimestamps);
            $oldestKey = key(self::$connectionTimestamps);
            self::purgeConnection($oldestKey);
        }
        
        if (empty(self::$handle[$cacheKey])) {
            self::$handle[$cacheKey] = Manager::getInstance()->connect($name);
            self::$connectionTimestamps[$cacheKey] = time();
        }
        
        self::$handle[$cacheKey]->loggingQueryLog = self::$debug['log'];
        self::$handle[$cacheKey]->loggingQueryTrace = self::$debug['trace'];
        self::$handle[$cacheKey]->loggingQueryExplain = self::$debug['explain'];
        return self::$handle[$cacheKey];
    }

    public static function config($config = null)
    {
        self::$config = $config;
        Manager::getInstance()->setConfig($config);
    }
    public static function setDefaultConnection($name)
    {
        self::$config['default'] = $name;
        Manager::getInstance()->setDefaultConnection($name);
    }

    /**
     * 获取指定连接的句柄
     *
     * @param string $name 连接名
     * @return Connection|null
     */
    public static function handle($name = null)
    {
        $cacheKey = $name ?: 'default';
        return isset(self::$handle[$cacheKey]) ? self::$handle[$cacheKey] : null;
    }
    
    /**
     * 获取指定连接的数据库连接实例
     *
     * @param string $name 连接名
     * @return Connection
     */
    public static function getConnection($name)
    {
        return self::connection($name);
    }
    
    /**
     * 清除指定连接的缓存
     *
     * @param string $name 连接名，为null时清除所有缓存
     * @return void
     */
    public static function purgeConnection($name = null)
    {
        if ($name === null) {
            self::$handle = [];
            self::$connectionTimestamps = [];
        } else {
            $cacheKey = $name ?: 'default';
            unset(self::$handle[$cacheKey]);
            unset(self::$connectionTimestamps[$cacheKey]);
        }
    }
    
    /**
     * 清理过期连接（在请求结束时调用）
     *
     * @return void
     */
    public static function cleanup()
    {
        $now = time();
        foreach (self::$connectionTimestamps as $key => $timestamp) {
            if ($now - $timestamp > self::$connectionTimeout) {
                self::purgeConnection($key);
            }
        }
    }

    /**
     * 创建独立的数据库连接（不影响全局配置）
     *
     * @param  array  $config 数据库配置
     * @param  string  $name 连接名称（可选）
     * @return Connection
     * 
     * @example
     * $conn = DB::createConnection([
     *     'driver' => 'mysql',
     *     'host' => 'localhost',
     *     'database' => 'test_db',
     *     'username' => 'root',
     *     'password' => 'password',
     *     'charset' => 'utf8mb4',
     *     'prefix' => 'test_',
     * ]);
     * $users = $conn->table('users')->select();
     */
    public static function createConnection(array $config, $name = null)
    {
        return Manager::getInstance()->createConnection($config, $name);
    }

    public static function debug($flag = 1)
    {
        if ($flag > 0) {
            self::$debug['log'] = true;
            $flag > 1 && self::$debug['trace'] = true;
            $flag > 2 && self::$debug['explain'] = true;
        }
    }

    public static function errorText($code)
    {
        $array = include_once __DIR__ . '/ErrorCode.php';
        return $array[$code];
    }

    public static function getError($ex, $code = 0)
    {
        $message = $ex->getMessage();
        $code = $ex->getCode();
        $driver = strtoupper(DB::getDriverName());
        $message = $driver . '.' . $message;
        // $message .= '<hr />' . self::errorText($code);
        $queryLog = DB::getQueryLog();
        $queryLog && $message .= '<hr />' . DB::getQueryLog();
        $codeMap = [
            1045    => 'Database Access denied',
            1049    => 'Database Nofound',
            2002    => 'Database Host error',
        ];
        if ($msg = $codeMap[$code]) {
            $message = $msg;
            // trigger_error($msg,E_USER_ERROR);
        }
        return [$code, $message, $driver];
        // throw $ex;
        // $queryLog = $this->getQueryLog();

        // if ($this->loggingQueryLog) {
        //     $queryLog && $message = $message . '<hr />' . $queryLog;
        // }
        // $codeMap = [
        //     1045    => 'Database Access denied',
        //     1049    => 'Database Nofound',
        //     2002    => 'Database Host error',
        //     '42S22' => $message . '<hr />' . $queryLog
        // ];
        // $codeMap[$code] && $message = $codeMap[$code];
        // throw new sException($message, -100,$ex);
    }
    // private function table($table)
    // {
    //     try {
    //         return $this->handle->table($table);
    //     } catch (\Exception $e) {
    //         return $this->throwError($e);
    //     }
    // }
    // private function select($query, $params = array())
    // {
    //     try {
    //         return $this->handle->select($query, $params);
    //     } catch (\Exception $e) {
    //         return $this->throwError($e);
    //     }
    // }
    // private function insert($query, $params = array())
    // {
    //     try {
    //         return self::$lastId = $this->handle->insert($query, $params);
    //     } catch (\Exception $e) {
    //         return $this->throwError($e);
    //     }
    // }
    // private function update($query, $params = array())
    // {
    //     try {
    //         return self::$rowCount = $this->handle->update($query, $params);
    //     } catch (\Exception $e) {
    //         return $this->throwError($e);
    //     }
    // }
    // private function transaction(Closure $callback, $attempts = 1)
    // {
    //     try {
    //         return $this->handle->transaction($callback, $attempts);
    //     } catch (\Exception $e) {
    //         return $this->throwError($e);
    //     }
    // }
    // private function beginTransaction()
    // {
    //     return $this->handle->beginTransaction();
    // }
    // private function rollBack()
    // {
    //     return $this->handle->rollBack();
    // }
    // private function commit()
    // {
    //     return $this->handle->commit();
    // }
    // private function connection($name = null)
    // {
    //     $this->handle = $this->connect($name);
    //     return $this->handle;
    // }

    // public function throwError($e)
    // {
    //     $text = $e->getMessage();
    //     throw new Exception($text);
    // }
}
