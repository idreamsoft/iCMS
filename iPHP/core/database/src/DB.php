<?php
// namespace DataBase;

//https://learnku.com/docs/laravel/7.x/database/7493


// use DataBase\Manager;
// use ReflectionClass;
// use Closure;
// use Exception;

class DB
{
    private static $handle;
    private static $debug = array(
        'log' => false, 
        'trace' => false,
        'explain' => false,
    );

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
            $call = array($instance, $method);
            try {
                return call_user_func_array($call, $params);
            } catch (\Exception $ex) {
                throw $ex;
            }
        } else {
            throw new Exception("Calling method '$method' " . implode(', ', $params));
        }
    }

    public function __construct()
    {
    }
    /**
     * @return Connection
     */
    public static function connection($name = null)
    {
        if (empty(self::$handle[$name])) {
            self::$handle[$name] = Manager::getInstance()->connect($name);
        }
        self::$handle[$name]->loggingQueryLog     = self::$debug['log'];
        self::$handle[$name]->loggingQueryTrace   = self::$debug['trace'];
        self::$handle[$name]->loggingQueryExplain = self::$debug['explain'];

        return self::$handle[$name];
    }
    public static function config($config = null)
    {
        Manager::getInstance()->setConfig($config);
    }
    public static function handle($name = null)
    {
        return self::$handle[$name];
    }
    public static function debug($flag=1)
    {
        if($flag>0){
            self::$debug['log'] = true;
            $flag>1 && self::$debug['trace'] = true;
            $flag>2 && self::$debug['explain'] = true;
        }
    }
    public static function errorText($code)
    {
        $array = $GLOBALS['DB.ERROR'];
        if(empty($array)){
            $array = include __DIR__.'/ErrorCode.php';
        }
        return $array[$code];
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
