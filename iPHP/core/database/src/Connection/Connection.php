<?php
// namespace DataBase\Connection;

// use DataBase\Arr;

// use PDO;
// use PDOException;
// use PDOStatement;
// use LogicException;
// use Closure;
// use Exception;

class Connection
{
    /**
     * The active PDO connection.
     *
     * @var \PDO|\Closure
     */
    protected $pdo;

    /**
     * The active PDO connection used for reads.
     *
     * @var \PDO|\Closure
     */
    protected $readPdo;

    /**
     * The name of the connected database.
     *
     * @var string
     */
    protected $database;

    /**
     * The table prefix for the connection.
     *
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * The database connection configuration options.
     *
     * @var array
     */
    protected $config = array();

    /**
     * The reconnector instance for the connection.
     *
     * @var callable
     */
    protected $reconnector;

    /**
    /**
     * The default fetch mode of the connection.
     *
     * @var int
     */
    // protected $fetchMode = PDO::FETCH_OBJ;//对象
    // protected $fetchMode = PDO::FETCH_NUM;//数字索引
    protected $fetchMode = PDO::FETCH_ASSOC; //数组

    /**
     * The number of active transactions.
     *
     * @var int
     */
    protected $transactions = 0;

    /**
     * Indicates if changes have been made to the database.
     *
     * @var int
     */
    protected $recordsModified = false;

    /**
     * All of the queries run against the connection.
     *
     * @var array
     */
    protected $queryLog = array();
    protected $queryTrace = array();
    public $loggingQueryLog = false;
    public $loggingQueryTrace = false;
    public $loggingQueryExplain = false;

    /**
     * The connection resolvers.
     *
     * @var array
     */
    protected static $resolvers = array();
    protected static $timeStart = 0;
    protected static $queryNum = 0;

    protected $statement;

    /**
     * Create a new database connection instance.
     *
     * @param  \PDO|\Closure  $pdo
     * @param  string  $database
     * @param  string  $tablePrefix
     * @param  array  $config
     * @return void
     */
    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = array())
    {
        static::timerStart();

        $this->pdo = $pdo;

        // First we will setup the default properties. We keep track of the DB
        // name we are connected to since it is needed when some reflective
        // type commands are run such as checking whether a table exists.
        $this->database = $database;

        $this->tablePrefix = $tablePrefix;

        $this->config = $config;
        // We need to initialize a query grammar and the query post processors
        // which are both very important parts of the database abstractions
        // so we initialize these to their default values while starting.
        // $this->useDefaultQueryGrammar();

        // $this->useDefaultPostProcessor();
    }

    /**
     * Configure the PDO prepared statement.
     *
     * @param  \PDOStatement  $statement
     * @return \PDOStatement
     */
    protected function prepared(PDOStatement &$statement)
    {
        $statement->setFetchMode($this->fetchMode);
        return $statement;
    }
    /**
     * Bind values to their parameters in the given statement.
     *
     * @param  \PDOStatement  $statement
     * @param  array  $bindings
     * @return void
     */
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }

    /**
     * Reconnect to the database.
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function reconnect()
    {
        if (is_callable($this->reconnector)) {
            $this->doctrineConnection = null;

            return call_user_func($this->reconnector, $this);
        }

        throw new LogicException('Lost connection and no reconnector available.');
    }

    /**
     * Disconnect from the underlying PDO connection.
     *
     * @return void
     */
    public function disconnect()
    {
        $this->setPdo(null)->setReadPdo(null);
    }
    /**
     * @return Builder
     */
    public function table($table)
    {
        try {
            return new Builder($this, $table);
        } catch (\Exception $ex) {
            $this->throwError($ex);
        }
    }
    public function cursor($query, $params = array(), $callback = null, $fetchMode = null)
    {
        try {
            $this->makeQueryLog($query, $params);
            $option = array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL);
            $stmt = $this->execute($query, $params, null, $option);

            while ($row = $stmt->fetch($fetchMode, PDO::FETCH_ORI_NEXT)) {
                $flag = call_user_func_array($callback, [$row, $stmt, $this]);
                if ($flag === 'break') {
                    return 'break';
                }
            }
        } catch (PDOException $ex) {
            $this->throwError($ex);
        }
    }
    public function row($query, $params = array(), $fetchMode = null)
    {
        try {
            $this->makeQueryLog($query, $params);
            $stmt = $this->execute($query, $params);
            $result = $stmt->fetch($fetchMode);
            return $result?:[];
        } catch (PDOException $ex) {
            $this->throwError($ex);
        }
    }
    public function select($query, $params = array())
    {
        try {
            $this->makeQueryLog($query, $params);
            $stmt = $this->execute($query, $params);
            return $stmt->fetchAll();
        } catch (PDOException $ex) {
            $this->throwError($ex);
        }
    }
    public function insert($query, $params = array())
    {
        try {
            $this->statement($query, $params);
            return $this->getPdo()->lastInsertId();
        } catch (PDOException $ex) {
            $this->throwError($ex);
        }
    }
    public function update($query, $params = array())
    {
        try {
            $stmt = $this->statement($query, $params);
            return $stmt->rowCount();
        } catch (PDOException $ex) {
            $this->throwError($ex);
        }
    }
    public function delete($query, $params = array())
    {
        try {
            $stmt = $this->statement($query, $params);
            return $stmt->rowCount();
        } catch (PDOException $ex) {
            $this->throwError($ex);
        }
    }
    /**
     * 运行普通语句
     * 有些数据库语句不会有任何返回值
     */
    public function query($query, $params = array())
    {
        return $this->statement($query, $params);
    }
    public function statement($query, $params = array())
    {
        try {
            $this->makeQueryLog($query, $params);
            $pdo = $this->getPdo();
            return $this->execute($query, $params, $pdo);
        } catch (PDOException $ex) {
            $this->throwError($ex);
        }
    }
    public function execute($query, $params = array(), $pdo = null, $option = null)
    {
        is_null($pdo) && $pdo = $this->getPdoForSelect();
        is_null($option) && $option = array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY);
        $this->statement = $pdo->prepare($query, $option);
        $this->prepared($this->statement);
        $this->statement->execute($params);
        static::$queryNum++;
        return $this->statement;
    }
    public function getStatement()
    {
        return $this->statement;
    }
    /**
     * Execute a Closure within a transaction.
     *
     * @param  \Closure  $callback
     * @param  int  $attempts
     * @return mixed
     *
     * @throws \Throwable
     */
    public function transaction(Closure $callback, $attempts = 1)
    {
        $pdo = $this->getPdo();
        for ($currentAttempt = 1; $currentAttempt <= $attempts; $currentAttempt++) {
            $pdo->beginTransaction();

            // We'll simply execute the given callback within a try / catch block and if we
            // catch any exception we can rollback this transaction so that none of this
            // gets actually persisted to a database or stored in a permanent fashion.
            try {
                $callbackResult = $callback($this);
            }

            // If we catch an exception we'll rollback this transaction and try again if we
            // are not out of attempts. If we are out of attempts we will just throw the
            // exception back out and let the developer handle an uncaught exceptions.
            catch (PDOException $ex) {
                $pdo->rollBack();
                continue;
            }

            try {
                $pdo->commit();
            } catch (PDOException $ex) {
                $pdo->rollBack();
                continue;
            }

            return $callbackResult;
        }
    }
    public function beginTransaction()
    {
        if(!$this->transactions){
        $this->transactions++;
        return $this->getPdo()->beginTransaction();
        }   
    }
    public function rollBack()
    {
        if ($this->transactions) {
            $this->transactions = 0;
            return $this->getPdo()->rollBack();
        }
    }
    public function commit()
    {
        if ($this->transactions) {
            $this->transactions = 0;
            return $this->getPdo()->commit();
        }
    }
    public function raw($sql)
    {
        $obj = new stdClass();
        $obj->raw = $sql;
        return $obj;
    }

    public function errorInfo()
    {
        try {
            if ($this->statement) {
                return $this->statement->errorInfo();
            }

            $pdo = $this->getPdo();
            return $pdo->errorInfo();
        } catch (PDOException $ex) {
            return [];
        }
    }
    public function version($v = null)
    {
        try {
            $pdo = $this->getPdo();
            $arr = array(
                $pdo->getAttribute(PDO::ATTR_SERVER_VERSION), //服务器数据库版本
                $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION) //客户端数据库版本
            );
            return $v === null ? $arr : $arr[$v];
        } catch (PDOException $ex) {
            $this->throwError($ex);
        }
    }
    public function exec($sql)
    {
        $pdo = $this->getPdo();
        try {
            return $pdo->exec($sql);
        } catch (PDOException $ex) {
            $error = $pdo->errorInfo();
            if ($error) {
                $message = $ex->getMessage();
                // $this->throwError($message, $error[1]);
                throw new sException($message, $error[1]);
            } else {
                $this->throwError($ex);
            }
            // var_dump($message, $error[1]);
            // throw new sException($message, $error[1]);
        }
    }
    public function hasTable($table = null)
    {
        // $table === null && $table = $this->table;
        // $result = $this->connection->tables();
        $result = $this->tables();
        $table  = $this->tablePrefix . iString::ltrim($table, $this->tablePrefix);
        return in_array($table, $result);
        // return array_column($result, $column, $index);
    }
    /**
     * @return Array
     */
    public function tables()
    {
    }
    public function addIdent($field)
    {
        if (is_array($field)) {
            $field = array_map([$this, 'addIdent'], $field);
        } else {
            $ident = $this->ident();
            $field = trim($field, $ident);
            $field = $ident . $field . $ident;
        }
        return $field;
    }
    public function ident()
    {
    }
    /**
     * @return Array
     */
    public function fullFields($table)
    {
    }
    public function ddl($table, $flag = false)
    {
    }
    public function copy($source, $target)
    {
        $prefix = $this->getTablePrefix();
        $target  = $prefix . iString::ltrim($target, $prefix);
        $source  = $prefix . iString::ltrim($source, $prefix);
        $sql = sprintf("CREATE TABLE IF NOT EXISTS %s LIKE %s", $target, $source);
        return $this->statement($sql);
    }
    public function rename($source, $target)
    {
        $prefix = $this->getTablePrefix();
        $target  = $prefix . iString::ltrim($target, $prefix);
        $source  = $prefix . iString::ltrim($source, $prefix);
        $sql = sprintf("RENAME TABLE %s TO %s", $source, $target);
        return $this->statement($sql);
    }
    public function quote($string)
    {
        return $this->getPdo()->quote($string);
    }
    public function getTableName($name, $prefix = null)
    {   
        if (strpos($name, '.')!==false) {
            return $name;
        }
        $tablePrefix = $this->getTablePrefix();
        is_null($prefix) && $prefix = $tablePrefix;
        return $tablePrefix . iString::ltrim($name, $prefix);
    }
    /**
     * Get the PDO connection to use for a select query.
     *
     * @param  bool  $useReadPdo
     * @return \PDO
     */
    protected function getPdoForSelect($useReadPdo = true)
    {
        return $useReadPdo ? $this->getReadPdo() : $this->getPdo();
    }
    /**
     * Get the current PDO connection.
     *
     * @return \PDO
     */
    public function getPdo()
    {
        if ($this->pdo instanceof Closure) {
            return $this->pdo = call_user_func($this->pdo);
        }

        return $this->pdo;
    }

    /**
     * Get the current PDO connection parameter without executing any reconnect logic.
     *
     * @return \PDO|\Closure|null
     */
    public function getRawPdo()
    {
        return $this->pdo;
    }

    /**
     * Get the current PDO connection used for reading.
     *
     * @return \PDO
     */
    public function getReadPdo()
    {
        if ($this->transactions > 0) {
            return $this->getPdo();
        }

        if ($this->recordsModified && $this->getConfig('sticky')) {
            return $this->getPdo();
        }

        if ($this->readPdo instanceof Closure) {
            return $this->readPdo = call_user_func($this->readPdo);
        }

        return $this->readPdo ?: $this->getPdo();
    }

    /**
     * Get the current read PDO connection parameter without executing any reconnect logic.
     *
     * @return \PDO|\Closure|null
     */
    public function getRawReadPdo()
    {
        return $this->readPdo;
    }

    /**
     * Set the PDO connection.
     *
     * @param  \PDO|\Closure|null  $pdo
     * @return $this
     */
    public function setPdo($pdo)
    {
        $this->transactions = 0;

        $this->pdo = $pdo;

        return $this;
    }

    /**
     * Set the PDO connection used for reading.
     *
     * @param  \PDO|\Closure|null  $pdo
     * @return $this
     */
    public function setReadPdo($pdo)
    {
        $this->readPdo = $pdo;

        return $this;
    }

    /**
     * Set the reconnect instance on the connection.
     *
     * @param  Closure  $reconnector
     * @return $this
     */
    public function setReconnector(Closure $reconnector)
    {
        $this->reconnector = $reconnector;

        return $this;
    }

    /**
     * Get the database connection name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getConfig('name');
    }

    /**
     * Get an option from the configuration options.
     *
     * @param  string|null  $option
     * @return mixed
     */
    public function getConfig($option = null)
    {
        return Arr::get($this->config, $option);
    }
    public function getCharset()
    {
        return $this->getConfig('charset');
    }
    public function getCharsetCollation()
    {
        return $this->getConfig('collation');
    }
    public function getEngine()
    {
        return $this->getConfig('engine') ?: 'INNODB';
    }
    /**
     * Get the PDO driver name.
     *
     * @return string
     */

    public function getDriverName()
    {
        return $this->getConfig('driver');
    }
    /**
     * Get the connection query log.
     *
     * @return array
     */
    public function getQueryLog($flag = null)
    {
        $flag && var_dump($this->queryLog);
        return $this->queryLog;
    }
    public function getQueryTrace($key = null, $idx = null)
    {
        if ($key) {
            if ($idx) return array_column($this->queryTrace, $idx, $key);
            return array_column($this->queryTrace, $key);
        }
        return $this->queryTrace;
    }
    /**
     * Get the name of the connected database.
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->database;
    }

    /**
     * Set the name of the connected database.
     *
     * @param  string  $database
     * @return $this
     */
    public function setDatabaseName($database)
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Get the table prefix for the connection.
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }
    /**
     * Register a connection resolver.
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     * @return void
     */
    public static function resolverFor($driver, Closure $callback)
    {
        static::$resolvers[$driver] = $callback;
    }

    /**
     * Get the connection resolver for the given driver.
     *
     * @param  string  $driver
     * @return mixed
     */
    public static function getResolver($driver)
    {
        return static::$resolvers[$driver] ?: null;
    }
    public function makeQueryLog($query, $params, $flag = false)
    {
        if (!is_string($query)) return;

        if (stripos($query, 'EXPLAIN EXTENDED ') !== false) {
            return;
        }

        $data = $params;
        $sql  = $query;
        $sql  = preg_replace('/\?/', "%s", $sql);
        $sql  = preg_replace('/:\w+/', "%s", $sql);

        if ($data) {
            foreach ($data as $key => $value) {
                if (is_object($value)) {
                    $data[$key] = $value->raw;
                } else {
                    !is_numeric($value) && $data[$key] = "'" . addslashes($value) . "'";
                }
            }
            array_unshift($data, $sql);
            $sql = call_user_func_array('sprintf', $data);
        }
        $this->queryLog = $sql;

        if ($flag) return $sql;

        if (!$this->loggingQueryLog) return;
        if ($this->loggingQueryTrace) {
            $this->makeQueryTrace($sql, $query, $params);
        }
    }

    public function makeQueryTrace($sql, $query, $params)
    {
        $trace = '';
        $backtrace = debug_backtrace();
        krsort($backtrace);
        // $backtrace = array_slice($backtrace,1,2);
        foreach ($backtrace as $i => $l) {
            $trace .= "\n[$i] in function <b>{$l['class']}{$l['type']}{$l['function']}</b>";
            $l['file'] = str_replace('\\', '/', $l['file']);
            $l['file'] = Security::filterPath($l['file']);
            $l['file'] && $trace .= " in <b>{$l['file']}</b>";
            $l['line'] && $trace .= " on line <b>{$l['line']}</b>";
        }
        $queryTrace = array(
            'query' => $sql,
            'time' => static::timerStop(true),
            'trace' => $trace
        );
        if ($this->loggingQueryExplain) {
            if (stripos($query, 'select ') !== false) {
                $queryTrace['explain'] = (array)$this->row('EXPLAIN EXTENDED ' . $query, $params);
            }
        }
        $this->queryTrace[] = $queryTrace;
        unset($trace, $backtrace);
    }
    public static function getQueryNum()
    {
        return static::$queryNum;
    }
    /**
     * Starts the timer, for debugging purposes
     */

    public static function timerStart()
    {
        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        static::$timeStart = $mtime[1] + $mtime[0];
        return true;
    }
    /**
     * Stops the debugging timer
     * @return int total time spent on the query, in milliseconds
     */
    public static function timerStop($restart = false)
    {
        $mtime      = microtime();
        $mtime      = explode(' ', $mtime);
        $time_end   = $mtime[1] + $mtime[0];
        $time_total = $time_end - static::$timeStart;
        $restart && static::$timeStart = $time_end;
        return round($time_total, 5);
    }
    public function throwError($ex, $code = 0)
    {

        $message = $ex->getMessage();
        $code = $ex->getCode();

        if ($this->loggingQueryLog) {
            $message = $this->getQueryLog() . '<hr />' . $message;
        }
        // if(strstr($message, 'SQLSTATE[')) { 
        //     preg_match('/SQLSTATE\[(\w+)\] \[(\w+)\] (.*)/', $message, $matches); 
        //     $code = ($matches[1] == 'HT000' ? $matches[2] : $matches[1]); 
        //     // $message = $matches[3]; 
        // } 
        throw new sException($message, $code);
    }
}
