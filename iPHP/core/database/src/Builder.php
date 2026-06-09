<?php

//https://learnku.com/docs/laravel/7.x/queries/7494
class Builder implements Iterator
{
    /**
     * @var string 表名
     */
    protected $table;

    /**
     * @var string|null 表别名
     */
    protected $alias = null;

    /**
     * @var string 查询字段
     */
    protected $fields = '*';

    /**
     * @var string 主键字段名
     */
    protected $primaryKey = 'id';

    /**
     * @var array 查询结果数据转换规则
     */
    protected $casts = [];

    /**
     * @var array 事件处理器
     */
    protected $events = [];

    /**
     * @var array 查询结果集
     */
    protected $response = [];

    /**
     * @var bool 是否自动填充默认值
     */
    protected $autoFill = false;

    /**
     * @var array 查询条件
     */
    protected $query = [];

    /**
     * @var array 预处理参数
     */
    protected $params = [];

    /**
     * @var string|null 最后执行的SQL
     */
    protected $lastQuery = null;

    /**
     * @var array 查询缓存
     */
    public static $CACHE = [];

    /**
     * @var array 类型转换缓存
     */
    protected $castCache = [];

    /**
     * @var Connection 数据库连接实例
     */
    private $connection;

    /**
     * 构造函数
     *
     * @param Connection $connection 数据库连接实例
     * @param string $table 表名
     * @throws Exception
     */
    public function __construct(Connection $connection, $table)
    {
        if (empty($table)) {
            throw new Exception('Table name cannot be empty');
        }

        $this->params = [];
        $this->query = [];

        $this->connection = $connection;
        $this->table = $this->getTableName($table);
    }

    // public function __debugInfo()
    // {
    //     return array(
    //         'data' => $this->response,
    //     );
    // }
    public function __get($name)
    {
        return $this->getResponse($name);
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        reset($this->response);
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->response);
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->response);
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        next($this->response);
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->current() !== false;
    }

    public function setValue($key, $value)
    {
        $this->$key = $value;
    }

    public function setEvents($param1 = null, $param2 = null, $param3 = null)
    {
        $num = func_num_args();
        if ($num == 1) {
            $this->events = array_replace_recursive($this->events, $param1);
        } elseif ($num == 2) {
            if (is_null($param2)) {
                unset($this->events[$param1]);
            } else {
                isset($this->events[$param1]) or $this->events[$param1] = [];
                $this->events[$param1] += $param2;
            }
        } elseif ($num == 3) {
            if (is_null($param3)) {
                unset($this->events[$param1][$param2]);
            } else {
                isset($this->events[$param1]) or $this->events[$param1] = [];
                isset($this->events[$param1][$param2]) or $this->events[$param1][$param2] = [];
                $this->events[$param1][$param2] += $param3;
            }
        }
    }

    /**
     * 优先级
     * orderByField
     * fieldGeted fieldUpdate fieldUpdated fieldCreate fieldCreated fieldDeleted
     * fieldChanged
     * changed
     * fieldEvent
     * geted,updated,created,deleted
     */
    public function runEvents($event = null, $isMulti = false)
    {
        $events = $this->events[$event];
        $response = $this->getResponse();
        if (empty($response)) {
            return false;
        }
        if ($events) {
            //获取 geted,updated,created,deleted事件最后执行
            if ($events[$event]) {
                $eventFunc = $events[$event];
                unset($events[$event]);
            }
            //单独处理orderByField事件
            if ($event == 'geted' && $events['orderByField']) {
                $orderByField = $events['orderByField'];
                call_user_func_array([$this, 'orderByField'], $orderByField);
                unset($events['orderByField']);
            }
            //执行 fieldGeted fieldUpdated fieldCreated fieldDeleted事件
            $this->runFieldEvent($events, $response, $event, $isMulti);
        }

        if (in_array($event, ['created', 'updated'])) {
            //变更事件绑定
            $events = $this->events['changed'] ?: [];
            if ($events['changed']) {
                $changed = $events['changed'];
                unset($events['changed']);
            }
            //执行字段事件 fieldCreated/fieldUpdated
            $this->runFieldEvent($events, $response, $event, $isMulti);
            //执行字段变更事件
            $changed && call_user_func_array($changed, [$response, $this, $event, $isMulti]);
        }

        //字段所有事件绑定
        $events = $this->events['event'];
        //执行字段事件 fieldEvent
        $this->runFieldEvent($events, $response, $event, $isMulti);
        //执行 geted,updated,created,deleted事件
        $eventFunc && call_user_func_array($eventFunc, [$response, $this, $event, $isMulti]);
    }

    public function runFieldEvent($events, $response, $event, $isMulti)
    {
        if ($events) {
            foreach ($events as $field => $func) {
                if (array_key_exists($field, $response)) {
                    $value = $response[$field];
                    call_user_func_array($func, [$value, $this, $event, $isMulti]);
                }
            }
        }
    }

    //输出值 自动转换
    public function runCasts(&$items, $isMulti = false)
    {
        if (empty($items) || empty($this->casts)) {
            return $items;
        }

        $isObject = is_object($items);
        $isObject && $items = $this->toArray($items);

        if ($isMulti) {
            $items = array_map(function ($item) {
                return $this->runCasts($item);
            }, $items);
            return $items;
        }

        foreach ($this->casts as $key => $type) {

            if (!isset($items[$key])) {
                continue;
            }

            $value = &$items[$key];

            // 跳过数组值的递归处理（保持原有逻辑）
            // if (is_array($value)) {
            //     $this->runCasts($value);
            //     continue;
            // }

            // // 检查缓存（只缓存简单类型）
            // $cacheKey = md5($key . serialize($value) . serialize($type));
            // if (isset($this->castCache[$cacheKey])) {
            //     $value = $this->castCache[$cacheKey];
            //     continue;
            // }

            // $originalValue = $value;
            $call = null;
            $fomat = null;

            // 处理事件
            if (isset($this->events['geted'][$key]) && $events = $this->events['geted'][$key]) {
                $type = $events;
                $this->casts[$key] = $events;
                unset($this->events['geted'][$key]);
            }

            // 解析类型
            if (is_array($type)) {
                $call = $type;
                $type = 'call';
            } elseif ($type && strpos($type, '::')) {
                $call = explode('::', $type);
                $type = 'call';
            } elseif ($type && strpos($type, ':')) {
                list($type, $fomat) = explode(':', $type);
            }

            // 执行转换
            switch ($type) {
                case 'as':
                    $items[$fomat] = $value;
                    // no break
                case 'delete':
                    unset($items[$key]);
                    break;
                case 'boolean':
                    $value = $value ? true : false;
                    break;
                case 'datetime':
                    $value = get_date($value, $fomat);
                    break;
                case 'html':
                    $value = htmlspecialchars_decode($value);
                    break;
                case 'json':
                    is_array($value) && $value = json_encode($value);
                    break;
                case 'call':
                    call_user_func_array($call, [&$value, $this]);
                    break;
                case 'strlist':
                    is_null($value) or $value = explode(',', $value);
                    break;
                case 'array':
                    $value && $value = json_decode($value, true);
                    empty($value) && $value = [];
                    break;
                default:
                    is_array($value) && $value = json_encode($value);
            }
            // // 缓存结果（只缓存简单类型，避免内存溢出）
            // if ($type !== 'call' && $type !== 'delete' && !is_array($value) && !is_object($value)) {
            //     $this->castCache[$cacheKey] = $value;
            // }
        }

        $isObject && $items = $this->toObject($items);
        return $items;
    }

    public function setCasts($casts)
    {
        $this->casts = $casts;
    }

    public function setFields($fields)
    {
        $this->fields = $fields;
        return $this;
    }

    public function setTable($name)
    {
        // $this->table = $this->connection->getTableName($name);
        $this->table = $this->getTableName($name, true);
        return $this;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function setPrimaryKey($key)
    {
        $this->primaryKey = $key;
    }

    public function getResponse($key = null)
    {
        if (empty($this->response)) {
            return null;
        }
        if ($key === null) {
            return $this->response;
        }
        $data = $this->toArray();
        return $data[$key];
    }

    public function setResponse($key = null, $value = null)
    {
        if (is_null($key) && is_null($value)) {
            $this->response = null;
        } elseif (is_array($key) && is_null($value)) {
            $this->response = $key;
        } else {
            $this->response[$key] = $value;
        }
    }

    /**
     * 获取预绑定参数值，数组自动转换成json
     */
    public function getParams($flag = false)
    {
        $params = $this->params;
        $flag && $this->params = null;

        //调换 params顺序 update前 where后
        if (isset($this->query['update']['values'])) {
            $params = array_merge($this->query['update']['values'], $params);
            unset($this->query['update']);
        }
        $result = [];
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    $result[] = json_encode($value);
                } elseif (is_null($value)) {
                    $result[] = '';
                } else {
                    $result[] = $value;
                }
            }
        }
        $length = $this->lastQuery ? substr_count($this->lastQuery, '?') : 0;
        $count = count($result);
        if ($count > $length) {
            $result = array_slice($result, 0, $length);
        } elseif ($count < $length) {
            $fill = array_fill($count, ($length - $count), '');
            $params = array_merge($params, $fill);
        }
        $this->lastQuery = $this->connection->makeQueryLog($this->lastQuery, $result, true);
        return $result;
    }

    public function setParams($params)
    {
        $this->params = $this->params ?
            array_merge($this->params, $params) :
            $params;
    }

    public function setAutoFill($value)
    {
        $this->autoFill = $value;
        return $this;
    }

    public function field()
    {
        $num = func_num_args();
        $args = func_get_args();
        if ($num > 1) {
            if ($args[0] === true) {
                //（true,'id','title')
                $this->fields = null;
                unset($args[0]);
            }
            //('id','title')
            $fields = implode(',', $args);
        } else {
            // "id,title"
            $fields = $args[0];
            if (is_bool($args[0]) && $args[0] === true) {
                $fieldArray = $this->fullFields();
                $fieldKeys = array_keys($fieldArray);
                $fields = implode(',', $fieldKeys);
            } elseif (is_array($args[0])) {
                //[id,title]
                $fields = implode(',', $args[0]);
            }
        }

        if ($this->fields != '*' && $this->fields) {
            $this->fields .= ',' . $fields;
        } else {
            $this->fields = $fields;
        }
        return $this;
    }

    public function withoutField()
    {
        $num = func_num_args();
        $args = func_get_args();

        $fieldArray = $this->fullFields($args, false);
        if ($fieldArray) {
            $fieldKeys = array_keys($fieldArray);
            $fields = implode(',', $fieldKeys);
            $this->fields = $fields;
        }
        return $this;
    }

    /**
     * @return object
     */
    public function all($query = null, $params = null)
    {
        if (is_array($query)) {
            $this->makeWhere($query, $params);
            $query = $this->makeQuery('SELECT');
        } else {
            $query === null && $query = $this->makeQuery('SELECT');
        }
        $params === null && $params = $this->getParams();
        if (is_array(Builder::$CACHE['ALL'])) {
            $hash = $this->getLastSql();
            if ($hash) {
                $cacheKey = md5($hash);
                $response = Builder::$CACHE['ALL'][$cacheKey];
            }
        }
        try {
            if (empty($response)) {
                $this->response = $this->connection->select($query, $params);
                $this->runCasts($this->response, true);
                $this->runEvents('geted', true);
                $cacheKey && Builder::$CACHE['ALL'][$cacheKey] = $this->response;
            } else {
                $cacheKey && $this->response = $response;
            }
            return $this->toObject();
        } catch (\Exception $ex) {
            return $this->throwError($ex);
        }
    }

    /**
     * @return array
     */
    public function select()
    {
        $args = func_get_args();
        $result = call_user_func_array([$this, 'all'], $args);
        return $this->response;
    }

    /**
     * 获取一条
     *
     * @param [type] $value
     * @param [type] $field
     * @return object
     */
    public function row($value = null, $field = null)
    {
        $this->makeWhere($value, $field);
        $this->limit(1);
        $query = $this->makeQuery('SELECT');
        $params = $this->getParams();

        if (is_array(Builder::$CACHE['ROW'])) {
            $ckey = md5($this->lastQuery);
            $response = Builder::$CACHE['ROW'][$ckey];
        }
        try {
            if (empty($response)) {
                $this->response = $this->connection->row($query, $params);
                $this->runCasts($this->response);
                $this->runEvents('geted');
                $ckey && Builder::$CACHE['ROW'][$ckey] = $this->response;
            } else {
                $ckey && $this->response = $response;
            }
            return new ModelEntity($this->response, $this);
            // return $this->toObject();
        } catch (\Exception $ex) {
            return $this->throwError($ex);
        }
    }

    /**
     * 获取一条
     * @return object
     */
    public function find()
    {
        $args = func_get_args();
        $obj = call_user_func_array([$this, 'row'], $args);
        return $this->response;
    }

    /**
     * 获取一条
     * @return array
     */
    public function get($value = null, $field = null)
    {
        $this->makeWhere($value, $field);
        $obj = $this->row();
        return $this->response;
    }

    // public function cursor($value = null, $field = null)
    // {
    //     if ($value) {
    //         $field === null && $field = $this->primaryKey;
    //         $this->where($field, $value);
    //     }
    //     $query = $this->makeQuery('SELECT');
    //     $params = $this->getParams();

    //     try {
    //         $result = $this->connection->cursor($query, $params);
    //         return $this->transCasts($result);
    //     } catch (\Exception $ex) {
    //         return $this->throwError($ex);
    //     }
    // }
    public function updateOrCreate($data, $where = [], $params = null)
    {
        $pk = $this->getPrimaryKey();
        $check = $this->field($pk)->where($where)->value();
        if ($check) {
            $flag = $this->update($data, $where);
        } else {
            if (is_array($params)) {
                $data = array_merge($data, $params);
            } elseif (is_bool($params) && $params === true) {
                $data = array_merge($data, $where);
            }
            $flag = $this->create($data, true);
        }
        return $flag;
    }
    public function insertOrUpdate($data, $where = [], $params = null)
    {
        return $this->updateOrCreate($data, $where, $params);
    }

    public function update($data = null, $where = null)
    {
        $num = func_num_args();
        $args = func_get_args();
        $args && $this->makeUpdate($args, $num);

        $query = $this->makeQuery('UPDATE');
        $params = $this->getParams();

        try {
            $query && $flag = $this->connection->update($query, $params);
            $flag && $this->runEvents('updated');
            return $flag;
        } catch (\Exception $ex) {
            return $this->throwError($ex);
        }
    }

    public function ignore($query = null, $params = null)
    {
        return $this->create($query, $params, true);
    }

    public function replace($query = null, $params = null)
    {
        return $this->create($query, $params, 'REPLACE');
    }
    public function insert($query = null, $params = null)
    {
        return $this->create($query, $params);
    }

    public function add($query = null, $params = null)
    {
        return $this->create($query, $params);
    }

    public function create($query = null, $params = null)
    {
        $num = func_num_args();
        $args = func_get_args();
        $args && $this->makeInsert($args, $num);

        $query = $this->makeQuery('INSERT');
        $params = $this->getParams(true);

        try {
            $id = $this->connection->insert($query, $params);
            $id && $this->response[$this->primaryKey] = $id;
            $id && $this->runEvents('created');
            return $id;
        } catch (\Exception $ex) {
            return $this->throwError($ex);
        }
    }

    public function delete($value = null, $field = null)
    {
        $this->makeWhere($value, $field);
        $query = $this->makeQuery('DELETE');
        $params = $this->getParams();

        try {
            //删除前
            $this->runEvents('delete');
            $flag = $this->connection->delete($query, $params);
            $this->runEvents('deleted');
            return $flag;
        } catch (\Exception $ex) {
            return $this->throwError($ex);
        }
    }

    public function truncate()
    {
        $query = $this->makeQuery('TRUNCATE');
        try {
            return $this->connection->statement($query);
        } catch (\Exception $ex) {
            return $this->throwError($ex);
        }
        return $this;
    }

    public function drop()
    {
        $query = $this->makeQuery('DROP');
        try {
            return $this->connection->statement($query);
        } catch (\Exception $ex) {
            return $this->throwError($ex);
        }
        return $this;
    }

    public function query($query, $params = null)
    {
        try {
            return $this->connection->statement($query);
        } catch (\Exception $ex) {
            return $this->throwError($ex);
        }
        return $this;
    }

    public function copy($target)
    {
        $prefix = $this->connection->getTablePrefix();
        $target = $prefix . iString::ltrim($target, $prefix);
        return $this->connection->copy($this->table, $target);
    }

    public function rename($target)
    {
        $prefix = $this->connection->getTablePrefix();
        $target = $prefix . iString::ltrim($target, $prefix);
        return $this->connection->rename($this->table, $target);
    }

    public function show($cmd, $field = null)
    {
        try {
            $query = sprintf('SHOW %s FROM %s', $cmd, $this->table);
            $result = $this->connection->select($query);
            return $field ? $result[$field] : $result;
        } catch (\Exception $ex) {
            return $this->throwError($ex);
        }
        return $this;
    }

    public function ddl($flag = false)
    {
        try {
            return $this->connection->ddl($this->table, $flag);
        } catch (\Exception $ex) {
            return $this->throwError($ex);
        }
        return $this;
    }
    public function page($page = 1, $pageSize = 20)
    {
        return $this->paging($pageSize, $page);
    }
    /**
     * lastId 方式还未完成
     *
     * @param integer $pageSize
     * @param string $unit
     * @return void
     */
    public function paging($pageSize = 20, $page = null, $unit = null)
    {
        $total = Paging::getCount();
        $total = $total ?: $this->count();
        isset($page) && Paging::setPage($page);
        $pageSize = Paging::getPageSize($pageSize);
        $offset = Paging::getOffset($total, $pageSize, $unit);

        if ($lastId = Paging::getLastId()) {
            $whereLastId = [$this->primaryKey, '<', $lastId];
        }
        if (isset($offset) || $lastId) {
            // if ($offset > 1000 && $total > 2000 && $offset >= $total / 2) {
            if ($offset >= $total / 2 && 0) {
                $_offset = $total - $offset - $pageSize;
                $_offset < 0 && $_offset = 0;
                $offset = $_offset;
                $this->orderBy($this->primaryKey, 'ASC');
                $whereLastId && $whereLastId[1] = '>';
                // $this->query['orderBy'] = $this->primaryKey . " ASC";
                // $limit = 'LIMIT ' . $_offset . ',' . $pageSize;
            }
            if ($whereLastId) {
                // $offset -= $pageSize;
                $this->where([$whereLastId]);
            }
            $idArray = $this->field($this->primaryKey)->limit($offset, $pageSize)->pluck();

            // file_put_contents('log.txt', print_r([DB::getQueryLog(),$idArray], true).PHP_EOL,FILE_APPEND);
            if (isset($_offset)) {
                $idArray = array_reverse($idArray, true);
                // $orderby   = "id DESC";
            }
            $limit = '';
        }
        // echo DB::getQueryLog(1);

        $this->setFields('*');
        if ($idArray) {
            // file_put_contents('log.txt', print_r($idArray, true).PHP_EOL,FILE_APPEND);
            $this->where(null)->where($this->primaryKey, $idArray);
            $this->orderBy($this->primaryKey, $idArray);
            $this->limit(true);
        } else {
            $this->limit($offset, $pageSize);
        }
        $result = $this->select();
        // file_put_contents('log.txt', print_r([DB::getQueryLog(),$idArray,$result], true).PHP_EOL,FILE_APPEND);
        // DB::getQueryLog(1);
        // exit;
        $column = array_column($result, $this->primaryKey);
        Paging::setLastId(end($column));
        Paging::getOffset($total, $pageSize, $unit);
        return $result;
    }

    /**
     * 游标分页（适合大数据集，避免深度分页性能问题）
     * 
     * @param int $pageSize 每页数量
     * @param mixed $lastId 上一页最后一条记录的ID
     * @return array
     */
    public function cursorPaginate($pageSize = 20, $lastId = null)
    {
        if ($lastId) {
            $this->where($this->primaryKey, '>', $lastId);
        }

        $results = $this->limit($pageSize + 1)->select();

        $hasMore = count($results) > $pageSize;
        if ($hasMore) {
            array_pop($results);
        }

        $nextCursor = $hasMore && !empty($results) ? end($results)[$this->primaryKey] : null;

        return [
            'data' => $results,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ];
    }

    public function first()
    {
        $this->row();
        return $this->toArray();
    }

    public function getId($value = null, $field = null)
    {
        $this->makeWhere($value, $field);
        return $this->field($this->primaryKey)->value();
    }
    public function getValue($value = null, $field = null)
    {
        $this->makeWhere($value, $this->primaryKey);
        return $this->field($field)->value();
    }

    public function column($key = null){
        return $this->pluck($key);
    }
    public function value($key = null)
    {
        $result = $this->first();
        $this->query['field'] = null;
        $value = is_null($key) ? current($result) : $result[$key];
        return is_null($value) ? '' : $value;
    }

    public function pluck($column = null, $index = null)
    {
        $result = $this->select();
        if (is_null($column) && is_array($result) && !empty($result)) {
            $column = key(current($result));
        }
        if ($index) {
            return array_column($result, $index, $column);
        }
        return array_column($result, $column);
    }

    public function getSql($s = 'SELECT')
    {
        $query = $this->makeQuery($s);
        $params = $this->getParams();
        return $this->lastQuery;
    }

    public function getWhere()
    {
        $sql = $this->getSql();
        $where = stristr($sql, 'WHERE');
        $where = iString::ltrim($where, 'WHERE');
        return $where;
    }

    public function makeQuery($cmd)
    {
        $pieces = [];
        $this->query['on'] && $pieces[] = 'ON ' . implode(' ON ', $this->query['on']);
        $this->query['where'] && $pieces[] = 'WHERE ' . $this->query['where'];
        $this->query['groupBy'] && $pieces[] = $this->query['groupBy'];
        $this->query['having'] && $pieces[] = 'HAVING ' . $this->query['having'];
        $this->query['orderBy'] && $pieces[] = $this->query['orderBy'];
        $this->query['limit'] && $pieces[] = $this->query['limit'];
        $query = implode(' ', $pieces);
        switch ($cmd) {
            case 'SELECT':
                $fields = $this->fields;
                $this->query['field'] && $fields = $this->query['field'];
                // empty($fields) && $fields = '*';
                $fieldsArray = explode(',', $fields);
                $fieldsArray = array_unique($fieldsArray);
                if ($this->query['distinct']) {
                    $key = array_search($this->query['distinct'], $fieldsArray);
                    if ($key !== false) {
                        unset($fieldsArray[$key]);
                    }
                    $distinct = $this->getDistinct();
                    array_unshift($fieldsArray, $distinct);
                }
                if ($this->alias) {
                    $fieldsArray = array_map(function ($a) {
                        return $this->addAlias($a);
                    }, $fieldsArray);
                    if (stripos($this->table, ' AS ') === false) {
                        $this->table = sprintf('%s AS %s', $this->table, $this->alias);
                    }
                }
                $tables = [$this->table];
                $this->query['join'] && $tables = array_merge($tables, $this->query['join']);
                $fields = implode(',', $fieldsArray);
                if ($this->query['leftJoin']) {
                    $table = $this->query['join'] ?
                        implode(' LEFT JOIN ', $tables) :
                        $this->joinOn($tables, 'LEFT');
                } elseif ($this->query['rightJoin']) {
                    $table = $this->query['join'] ?
                        implode(' RIGHT JOIN ', $tables) :
                        $this->joinOn($tables, 'RIGHT');
                } else {
                    if ($this->query['on']) {
                        $table = $this->query['join'] ?
                            implode(' JOIN ', $tables) :
                            $this->joinOn($tables);
                    } else {
                        $table = implode(',', $tables);
                    }
                }
                $sql = sprintf(
                    'SELECT %s FROM %s %s',
                    $fields,
                    $table,
                    $query
                );
                break;
            case 'UPDATE':
                $this->query['update']['fields'] && $sql = sprintf(
                    'UPDATE %s SET %s %s',
                    $this->table,
                    $this->query['update']['fields'],
                    $query
                );
                break;
            case 'DELETE':
                $query && $sql = sprintf(
                    'DELETE FROM %s %s',
                    $this->table,
                    $query
                );
                break;
            case 'REPLACE':
                //此语句的作用是当我们在插入一条数据时，如果此条已经存在，那么先删除原来存在的数据再添加插入的数据，
                //如果不存在那么直接插入新的数据。
                //注意：是否存在是通过主键来确定的
            case 'INSERT IGNORE':
                //此语句的作用是如果插入的数据已经存在那么就忽略插入的数据（也就是不改变原来的数据），
                //如果不存在则插入新的数据。
                //注意：是否存在是通过主键来确定的
            case 'INSERT':
                $sql = sprintf(
                    '%s INTO %s (%s) VALUES (%s)',
                    $this->query['insert']['mode'],
                    $this->table,
                    $this->query['insert']['fields'],
                    $this->query['insert']['values']
                );
                break;
            case 'TRUNCATE': //清空表
                $sql = sprintf('TRUNCATE TABLE %s', $this->table);
                break;
            case 'DROP': //删除表
                $sql = sprintf('DROP TABLE IF EXISTS %s', $this->table);
                break;
            case 'CREATE': //创建表
                $sql = sprintf(
                    'CREATE TABLE %s (%s) ENGINE=%s AUTO_INCREMENT=0 DEFAULT CHARSET=%s;',
                    $this->table,
                    $this->query['create']['fields'],
                    $this->connection->getEngine(),
                    $this->connection->getCharset()
                );
                break;
        }
        $this->lastQuery = $sql;
        return $sql;
    }

    public function makeInsert($args, $num)
    {
        $this->query['insert'] = [];
        $data = [];
        $autoFill = false;
        $mode = 'INSERT';
        //create(['aa'=>1,'bb'=>'22'])
        if ($num == 1 && is_array($args[0])) {
            $data = $args[0];
            // } elseif ($num == 2) {
        } else {
            //create('aa','bb')
            if (is_string($args[0])) {
                $data = [$args[0] => $args[1]];
            } elseif (is_array($args[0])) {
                //create(['aa'=>1,'bb'=>'22'],false)
                //create([['aa'=>1,'bb'=>'11'],['aa'=>2,'bb'=>'22']],false)
                $data = $args[0];
                $autoFill = (bool)$args[1];
            }
            //create(['aa'=>1,'bb'=>'22'],true,'REPLACE')
            //create(['aa'=>1,'bb'=>'22'],true,'IGNORE')
            //create(['aa'=>1,'bb'=>'22'],true,true)==IGNORE
            if (isset($args[2])) {
                if (is_string($args[2])) {
                    $mode = $args[2];
                } elseif (is_bool($args[2])) {
                    $mode .= $args[2] ? ' IGNORE' : '';
                }
            }
        }
        if ($data) {
            // $response = $data;
            // if (!$this->isAssocArr($data)) {
            //     var_dump('sdf');
            //     $response = [$data];
            // }
            $autoFill && $this->autoFill($data);

            $cleanData = [];
            foreach ($data as $field => $value) {
                // 验证字段名
                if ($this->validateFieldName($field)) {
                    $cleanData[$field] = $value;
                }
            }
            $this->response = $cleanData;
            $this->runEvents('creating');
            $data = $this->response;
            $params = array_values($data);
            $fields = array_keys($data);
            $count = count($fields);
            $values = array_fill(0, $count, '?');

            $fields = $this->ident($fields);
            $this->query['insert']['mode'] = $mode;
            $this->query['insert']['fields'] = implode(',', $fields);
            $this->query['insert']['values'] = implode(',', $values);
            $this->params = $params;
        }
    }

    public function makeUpdate($args, $num)
    {
        $data = [];
        if ($num == 1) {
            //update(array('nickname'=>'bbb','nickname'=>'bbb'));
            is_array($args[0]) && $data = $args[0];
            //update(" aa='1',bbb='2' ")
            // is_string($args[0]) && $this->query['update'] = $args[0];
        } elseif ($num == 2) {
            // update(
            // array(
            //     'nickname'=>'ccc',
            //     'column'=>array('+',1),
            //     'column'=>array('column2','+',1)
            // ),//更新
            //     array('uid'=>'1') //条件
            // );
            // update(
            //     array('nickname'=>'ccc'),//更新
            //     1 //主键 条件
            // );
            if (is_array($args[0])) {
                $data = $args[0];
                if (isset($args[1])) {
                    $where = [$this->primaryKey => $args[1]];
                    if (is_array($args[1])) {
                        //判断是否为关联数组
                        $this->isAssocArr($args[1]) or $where = $args[1];
                    }
                    $this->query['where'] = null;
                    $this->where($where);
                }
            } else {
                //update('nickname','aaa');
                $data = [$args[0] => $args[1]];
            }
        }
        $update = $params = [];
        if ($data) {
            $this->response = $data;
            $this->runEvents('updating');
            $data = $this->response;
            $this->autoFill($data, false);
            foreach ($data as $field => $value) {
                // 验证字段名
                if (!$this->validateFieldName($field)) {
                    continue;
                }
                $field = $this->ident($field);
                $prepare = sprintf('%s = ?', $field);
                if (is_array($value)) {
                    $vc = count($value);
                    $exp = null;
                    //['raw'=>'CONCAT(?,dir)',1]
                    //['raw'=>'REPLACE(title,?,?)',[1,2]]
                    if ($raw = $value['raw']) {
                        $prepare = sprintf(' %s = %s ', $field, $raw);
                        if (is_array($value[0])) {
                            $params = array_merge($params, $value[0]);
                        } else {
                            $params[] = $value[0];
                        }
                    } else {
                        if ($vc == 2) { //['+',1]
                            $expKey = $field;
                            list($exp, $val) = $value;
                        } elseif ($vc == 3) { //['aa','+',1]
                            list($expKey, $exp, $val) = $value;
                        }

                        if ($exp && is_string($exp) && strpos('+-*/', $exp) !== false) {
                            $prepare = sprintf(' %s = %s %s ? ', $field, $expKey, $exp);
                            // $prepare = " {$field} = {$expKey} {$exp} ? ";
                            $params[] = $val;
                        } elseif ($vc == 2 && $exp == '=') { //['=',0]
                            $params[] = $value[1];
                        } else {
                            $params[] = $value;
                        }
                    }
                } else {
                    if (is_object($value)) {
                        if (is_callable($value)) {
                            $sql = call_user_func($value);
                        } else {
                            // 子查询
                            //where('id',DB::raw('sql'))
                            $sql = $value->raw;
                        }
                        $prepare = sprintf(' %s = (%s)', $field, $sql);
                        // $prepare = " {$field} = {$raw}";
                    } else {
                        $params[] = $value;
                    }
                }
                $update[] = $prepare;
            }
        }

        $update && $this->query['update']['fields'] = implode(',', $update);
        $params && $this->query['update']['values'] = $params;
    }

    public function makeWhere($value = null, $field = null)
    {
        if (!is_null($value)) {
            is_null($field) && $field = $this->primaryKey;
            //数组
            if (is_array($value)) {
                if ($this->isAssocArr($value)) {
                    //delete([1,2,3,4])
                    //row([1,2,3,4])
                    //get([1,2,3,4])
                    $value = array_map('intval', $value);
                    // $this->where($field, $value);
                } else {
                    //关联数组
                    //delete(['id'=>11])
                    //row(['id'=>11])
                    //get(['id'=>11])
                    return $this->where($value);
                }
            } else {
                if (is_numeric($value)) {
                    $value = (int)$value;
                    //delete(10)
                    //row(10)
                    //get(10)
                    // $this->where($this->primaryKey, (int)$value);
                } else {
                    //delete('id',aa)
                    //row('id',aa)
                    //get('id',aa)
                }
            }
            if ($value === true) {
                $this->query['where'] = ' 1=1 ';
            } else {
                $this->where($field, $value);
            }
        }
    }

    public function where()
    {
        $args = func_get_args();
        return $this->whereRaw($args);
    }

    public function orWhere()
    {
        $args = func_get_args();
        return $this->whereRaw($args, null, ' OR ');
    }

    public function whereQuery($args, $split = ' AND ', $flag = false)
    {
        $num = count($args);
        $where = [];
        $params = [];
        $expArray = explode(',', '=,<,>,<>,!=,<=,>=,like,not like,regexp');
        $fxpArray = explode(',', 'in,not in,between,not between');

        if ($num == 1) {
            if (is_array($args[0])) {
                if ($this->isAssocArr($args[0]) && $this->isNumArr($args[0])) {
                    if ($args[0]) {
                        //where([1,2,3,4])
                        $args[0] = array_map('intval', $args[0]);
                        $where[] = [$this->primaryKey, 'IN', $args[0]];
                    }
                } else {
                    foreach ($args[0] as $key => $value) {
                        if (is_array($value)) {
                            $vc = count($value);
                            //二维数组
                            if (is_numeric($key)) {
                                //where(array(
                                //    array('id',1),
                                //    array('vid',[1,2,3])
                                //))
                                if ($vc == 2) {
                                    if (is_array($value[1])) {
                                        $where[] = [$value[0], 'IN', $value[1]];
                                    } else {
                                        $where[] = [$value[0], '=', $value[1]];
                                    }
                                } else {
                                    //where(array(
                                    //    array('id','>',1),
                                    //    array('vid','not in',[1,2,3])
                                    //))
                                    $where[] = [$value[0], $value[1], $value[2]];
                                }
                            } else {
                                ////判断是否为数字索引              //判断是否为纯属数字数组
                                if ($this->isAssocArr($value) && $this->isNumArr($value)) {
                                    //where(array('id'=>[1,2,3]))
                                    !is_null($value) && $where[] = [$key, 'IN', $value];
                                } else {
                                    if ($this->isTwoArr($value)) {
                                        list($_query, $_params) = $this->whereQuery([$value], ' OR ', true);
                                        $object = new stdClass();
                                        $object->query = $_query;
                                        $object->params = $_params;
                                        $where[] = $object;
                                    } else {
                                        $exp = $value[0] ? strtolower(trim($value[0])) : '';
                                        $exp1 = in_array($exp, $expArray);
                                        $exp2 = in_array($exp, $fxpArray);
                                        if ($vc == 2 && ($exp1 || $exp2)) {
                                            //where(array('id'=>array('>',1)))
                                            //where(array('title'=>array('REGEXP',1)))
                                            $where[] = [$key, $value[0], $value[1]];
                                        } elseif ($value[1] && strtolower($value[1]) == 'find_in_set') {
                                            // where(array('aa'=>array('aa','FIND_IN_SET',1)))
                                            $where[] = $value;
                                        } else {
                                            //where(array('id'=>[1,2,3]))
                                            !is_null($value) && $where[] = [$key, 'IN', $value];
                                        }
                                    }
                                }
                            }
                            // } else if($value['raw']){
                            //     $where[] = $value['raw'];
                        } elseif (is_object($value)) {
                            $where[] = $value->raw;
                        } else {
                            //where(array('id'=>1,'aa'=>11))
                            //where(array('id'=>DB::raw()))
                            $where[] = [$key, '=', $value];
                        }
                    }
                }
            } elseif (is_object($args[0])) {
                $where[] = $args[0]->raw;
            } else {
                //where(10)
                if (is_numeric($args[0])) {
                    $where[] = [$this->primaryKey, '=', (int)$args[0]];
                } else {
                    //原生SQL
                    //where('id = 111 and aa=22')
                    $where[] = $args[0];
                }
            }
        } elseif ($num == 2) {
            if (is_array($args[0]) && is_array($args[1])) {
                //where(
                //     ['id = ?','aaa=?'],
                //     [1,2]
                // )
                $where[] = implode($split, $args[0]);
                $params = array_merge($params, $args[1]);
            } else {
                if (is_array($args[1]) && strpos($args[0], '?') !== false) {
                    //where(
                    //     'id = ? and aaa=?',
                    //     [1,2]
                    // )
                    $where[] = $args[0];
                    $params = array_merge($params, $args[1]);
                } else {
                    if (is_array($args[1])) {
                        //where('id',[1,2])
                        $where[] = [$args[0], 'IN', $args[1]];
                    } else {
                        //where('id',1)
                        $where[] = [$args[0], '=', $args[1]];
                    }
                }
            }
        } else {
            //where('id','>=',1)
            //where('id','>=',DB::raw('sql'))
            $args && $where[] = $args;
        }
        foreach ($where as $value) {
            if (is_array($value)) {
                $field = $value[0];
                $field = preg_replace('/[^\w\d_\(\),\.]/i', '', $field);
                if (stripos($field, '.') !== false) {
                    // list($t,$f) = explode('.',$field);
                } elseif (stripos($field, '(') === false && stripos($field, '(') === false) {
                    $field = $this->ident($field);
                }
                $this->addAlias($field);
                // 用问号形式准备 SQL 语句参数
                $exp = strtolower(trim($value[1]));
                //=,<,>,<>,!=,<=,>=,like,not like
                if (in_array($exp, $expArray)) {
                    // id = ?
                    if ($value[2] === '') {
                        //id <>''
                        // $query[] = "{$field} {$value[1]} ''";
                        $qv = "''";
                    } elseif (is_object($value[2])) {
                        if (is_callable($value[2])) {
                            $sql = call_user_func($value[2]);
                        } else {
                            // 子查询
                            //where('id','>=',DB::raw('sql'))
                            $sql = $value[2]->raw;
                        }
                        // $query[] = "{$field} {$value[1]} {$sql}";
                        $qv = sprintf('(%s)', $sql);
                    } else {
                        // $query[] = "{$field} {$value[1]} ?";
                        $qv = '?';
                        $params = array_merge($params, [$value[2]]);
                    }
                } else {
                    // in,not in,between,not between
                    if (is_array($value[2])) {
                        //array('id' => ['between', [1, 2]])
                        if (stripos($exp, 'between') !== false) {
                            // id between ? AND ?
                            // $query[] = "{$field} {$value[1]} ? AND ?";
                            $qv = '? AND ?';
                        } else {
                            $vc = count($value[2]);
                            $marks = implode(',', array_fill(0, $vc, '?'));
                            // id IN (?,?,?)
                            // $query[] = "{$field} {$value[1]} (" . $marks . ")";
                            $qv = sprintf('(%s)', $marks ?: "''");
                        }
                        //FIND_IN_SET(%s,?)
                        if ($exp == 'find_in_set') {
                            $params = array_merge($params, [implode(',', $value[2])]);
                        } else {
                            // $value[2] = array_map('strval', $value[2]);
                            $params = array_merge($params, $value[2]);
                        }
                    } elseif (is_object($value[2])) {
                        if (is_callable($value[2])) {
                            $sql = call_user_func($value[2]);
                        } else {
                            // 子查询
                            //where('id','>=',DB::raw('sql'))
                            $sql = $value[2]->raw;
                        }
                        // id IN (sql)
                        // $query[] = "{$field} {$value[1]} ({$sql}) ";
                        $qv = sprintf('(%s)', $sql);
                        // $this->setParams(array($value[2]));
                    } else {
                        // var_dump($value);
                        // id IN (?)
                        // $query[] = "{$field} {$value[1]} (?) ";
                        $qv = '(?)';
                        $params = array_merge($params, [$value[2]]);
                    }
                }
                if ($exp == 'find_in_set') {
                    $query[] = sprintf('FIND_IN_SET(?,%s)', $field);
                } else {
                    $query[] = sprintf(' %s %s %s ', $field, $value[1], $qv);
                }
            } else {
                $sql = $value;
                if (is_object($value)) {
                    $sql = $value->query;
                    $params = array_merge($params, $value->params);
                }
                //原生SQL
                $query[] = sprintf('(%s)', $sql);
            }
        }
        if ($flag) {
            $query = implode($split, $query);
        }
        return [$query, $params];
    }

    public function whereRaw($args, $params = [], $split = ' AND ', $flag = false)
    {
        //where(null)
        if (is_null($args[0])) {
            $this->query['where'] = '';
            $this->params = [];
            return $this;
        }

        $query = [];
        if (is_string($args)) {
            //where('id = 1 and aa=2')
            $query[] = $args;
            is_array($params) && $this->setParams($params);
            // is_array($params) && array_push($this->params, $params);
        } else {
            list($query, $_params) = $this->whereQuery($args, $split);
            $_params && $this->setParams($_params);
            // var_dump([$query, $this->params]);
        }
        // var_dump($this->query['where']);
        $query && $whereSql = implode($split, $query);
        if ($flag) {
            // var_dump($whereSql,$this->params);
            return $whereSql;
        }
        if ($whereSql) {
            $prefix = $this->query['where'] ? 'AND' : '';
            $this->query['where'] .= sprintf(' %s (%s) ', $prefix, $whereSql);
            // $this->query['where'] .= ' AND ' . $whereSql;
        }
        // var_dump($this->query['where'], $this->params);
        return $this;
    }

    /**
     * 排给定的ID数组排序
     * 等价于 order by FIELD(`id`, "60,2,18,4,3,20,50");
     */
    public function orderByField($field, $idArray)
    {
        $array = array_column($this->response, $field);
        $result = [];
        foreach ($idArray as $id) {
            $key = array_search($id, $array);
            $key !== false && $result[] = $this->response[$key];
        }
        $this->response = $result;
        $this->setEvents('geted', 'orderByField', null);
    }

    public function getBy()
    {
        return $this->query['by'];
    }
    public function rand()
    {
        return $this->orderBy('rand()');
    }
    public function order($field = null, $by = 'DESC')
    {
        return $this->orderBy($field, $by);
    }

    public function orderBy($field = null, $by = 'DESC')
    {
        $field === null && $field = $this->primaryKey;
        //按给定的id排序
        //orderBy('id',[1,2,3,4,5])
        if (is_string($field) && is_array($by)) {
            $this->setEvents('geted', 'orderByField', [$field, $by]);
            $this->query['orderBy'] = '';
        } elseif (is_string($field) && strtolower($field) == 'rand()') { //随机 orderBy('rand()')
            $this->query['orderBy'] = 'ORDER BY RAND()';
        } else {
            $orderByArr = [];

            //orderBy('id ASC') id ASC
            //orderBy('id ASC,aaa DESC') id ASC,aaa DESC
            if (is_string($field) && (stripos($field, ' ') !== false || stripos($field, ',') !== false)) {
                // 安全处理多字段排序字符串
                $parts = explode(',', $field);
                foreach ($parts as $part) {
                    $part = trim($part);
                    // 验证格式: 字段名 [ASC|DESC]
                    if (preg_match('/^(\w+)(?:\s+(ASC|DESC))?$/i', $part, $matches)) {
                        $f = $matches[1];
                        $d = isset($matches[2]) ? strtoupper($matches[2]) : 'DESC';
                        // 验证字段名
                        if ($this->validateFieldName($f)) {
                            $orderByArr[] = $this->makeOrderBySql($f, $d);
                        }
                    }
                }
            } else {
                //orderBy('id') id DESC
                //orderBy('id','ASC') id ASC
                //orderBy(['id','aaa']) id DESC,aaa DESC
                //orderBy(['id','aaa'],'ASC) id ASC,aaa ASC
                //orderBy(['id'=>'ASC','aaa'=>'DESC']) id ASC,aaa DESC
                $fieldArr = is_array($field) ? $field : [$field => $by];
                foreach ($fieldArr as $field => $_by) {
                    if (is_numeric($field)) {
                        $field = $_by;
                        $_by = $by;
                    }
                    // 验证字段名和排序方向
                    if ($this->validateFieldName($field) && $this->validateOrderDirection($_by)) {
                        $_by && $by = $_by;
                        $orderByArr[] = $this->makeOrderBySql($field, strtoupper($by));
                    }
                }
            }

            if (!empty($orderByArr)) {
                $sql = implode(',', $orderByArr);
                $this->query['orderBy'] = sprintf('ORDER BY %s', $sql);
            }
        }
        $this->query['by'] = $by;
        return $this;
    }

    public function makeOrderBySql($field, $by)
    {
        if (strpos($field, ' ') !== false) {
            list($field, $by) = explode(' ', trim($field));
        }
        $this->addAlias($field);
        return sprintf('%s %s', $field, $by);
        // $this->query['orderBy'] = sprintf('ORDER BY %s %s', $field, $by);
    }

    public function groupBy($field = null, $by = 'DESC')
    {
        $field === null && $field = $this->primaryKey;
        if (strpos($field, ' ') !== false) {
            list($field, $by) = explode(' ', $field);
        }
        $this->addAlias($field);
        $this->query['groupBy'] = sprintf('GROUP BY %s %s', $field, $by);
        return $this;
    }

    public function addAlias(&$field)
    {
        if (strpos($field, ')') !== false) {
            return $field;
        }
        if (strpos($field, '.') !== false) {
            list($t, $f) = explode('.', $field);
            $t = $this->ident($t);
            $f = $this->ident($f);
            $field = sprintf('%s.%s', $t, $f);
            return $field;
        }
        if ($this->alias) {
            $field = $this->ident($field);
            $field = sprintf('%s.%s', $this->alias, $field);
            return $field;
        }
        $field = $this->ident($field);
        return $field;
    }

    public function as($name)
    {
        return $this->alias($name);
    }

    public function alias($name)
    {
        $this->alias = $this->ident($name);
        return $this;
    }

    public function count($field = null)
    {
        $field === null && $field = $this->primaryKey;
        $this->addAlias($field);
        if ($this->query['distinct']) {
            $field = $this->getDistinct($distinct);
            $this->query['distinct'] = null;
        }
        $this->query['field'] = sprintf('COUNT(%s) AS _count', $field);
        $orderby = $this->query['orderBy'];
        $limit = $this->query['limit']; //保存原有limit
        $this->query['orderBy'] = null; //不需要orderBy
        $count = $this->value('_count');
        $this->query['orderBy'] = $orderby;
        $this->query['limit'] = $limit;
        $this->query['distinct'] = $distinct;
        return $count;
    }

    public function max($field = null)
    {
        $field === null && $field = $this->primaryKey;
        $this->addAlias($field);
        $this->query['field'] = sprintf('MAX(%s) AS _max', $field);
        return $this->value('_max');
    }

    public function min($field = null)
    {
        $field === null && $field = $this->primaryKey;
        $this->addAlias($field);
        $this->query['field'] = sprintf('MIN(%s) AS _min', $field);
        return $this->value('_min');
    }

    public function sum($field = null)
    {
        if (empty($field)) {
            return false;
        }
        $this->query['field'] = sprintf('SUM(%s) AS _sum', $field);
        return $this->value('_sum');
    }
    public function avg($field = null)
    {
        if (empty($field)) {
            return false;
        }
        $this->query['field'] = sprintf('AVG(%s) AS _avg', $field);
        return $this->value('_avg');
    }

    // public function updateRaw($query, $params = null, $data = null)
    // {
    //     $this->query['update'] = $query;
    //     if ($params) { //调用params顺序 update前 where后
    //         $_params = $this->params;
    //         $this->params = $params;
    //         $this->setParams($_params);
    //         // array_push($this->params, $_params);
    //     }
    //     return $this->update($data);
    // }

    public function increment($field)
    {
        return $this->inc($field);
    }
    public function inc($field)
    {
        //inc('count');
        $args = func_get_args();
        $step = 1;
        //inc('count',11);
        if (is_numeric($args[1])) {
            $step = $args[1];
        } elseif (is_array($args[1])) {
            // inc('count', ['pubdate' => time()]);
            $data = $args[1];
        }
        // inc('count', 12,['pubdate' => time()]);
        if (isset($args[2])) {
            $step = $args[1];
            $data = $args[2];
        }
        $data[$field] = ['+', $step];
        $this->update($data);
        return $this;
    }
    public function decrement($field)
    {
        return $this->dec($field);
    }
    public function dec($field)
    {
        //dec('count');
        $args = func_get_args();
        $step = 1;
        //dec('count',11);
        if (is_numeric($args[1])) {
            $step = $args[1];
        } elseif (is_array($args[1])) {
            // dec('count', ['pubdate' => time()]);
            $data = $args[1];
        }
        // dec('count', 12,['pubdate' => time()]);
        if (isset($args[2])) {
            $step = $args[1];
            $data = $args[2];
        }
        $data[$field] = ['-', $step];
        $this->update($data);
        return $this;
    }

    public function getDistinct(&$distinct = null)
    {
        $distinct = $this->query['distinct'];
        $distinct = $this->ident($distinct);
        $this->addAlias($distinct);
        return sprintf('DISTINCT(%s)', $distinct);
    }

    public function distinct($field = null)
    {
        $field === null && $field = $this->primaryKey;
        $this->query['distinct'] = $field;
        return $this;
    }

    public function exists($args)
    {
        foreach ($args as $key => $value) {
            $pieces[] = sprintf('exists(%s)', $value->raw);
        }
        $this->query['where'] .= ' AND ' . implode(' AND ', $pieces);
        return $this;
    }

    public function joinOn($tables, $flag = '')
    {
        foreach ($this->query['joinOn'] as $key => $join) {
            $sql = $flag . ' JOIN ' . $join[0];
            $join[1] && $sql .= ' ON ' . $join[1];
            $tables[] = $sql;
        }
        return implode(' ', $tables);
    }

    public function join($args)
    {
        if (count($args) === 2 && is_string($args[0])) {
            $this->query['joinOn'][] = [$args[0], $args[1]];
            return $this;
        }
        $vars = $args;
        if ($vars) {
            foreach ($vars as $key => $value) {
                //$join[] = [
                //     [['id','=','nodeMap.iid']]
                //     [NodeMap::getTableName(),'nodeMap'],
                //     DB::raw($sql)
                // ];
                if (is_array($value[1])) {
                    $table = $value[1][0];
                    $table = sprintf('%s AS %s', $table, $value[1][1]);
                } else {
                    //$join[] = [
                    //     [['id','=','nodeMap.iid']]
                    //     NodeMap::getTableName(),
                    //     DB::raw($sql)
                    // ];
                    $table = $value[1];
                }
                $this->where($value[0]);
                $table && $this->query['join'][] = $table;
                $value[2] && $this->where($value[2]);
            }
        }
        return $this;
    }

    /**
     * leftjoin(table,on)
     * leftjoin('table as t1','t1.id=t0.t1_id')
     *
     * @return void
     */
    public function leftJoin()
    {
        $args = func_get_args();
        $this->join($args);
        $this->query['leftJoin'] = ($this->query['join'] || $this->query['joinOn']);
        return $this;
    }

    /**
     * rightJoin(table,on)
     *
     * @return void
     */
    public function rightJoin()
    {
        $args = func_get_args();
        $this->join($args);
        $this->query['rightJoin'] = ($this->query['join'] || $this->query['joinOn']);
        return $this;
    }

    public function on()
    {
        $args = func_get_args();
        $this->query['on'][] = $this->whereRaw($args, null, ' AND ', true);
        return $this;
    }

    public function having()
    {
        $args = func_get_args();
        $this->query['having'] = $this->whereRaw($args, null, ' AND ', true);
        return $this;
    }

    public function sharedLock()
    {
        return $this;
    }

    public function lockForUpdate()
    {
        return $this;
    }

    public function offset($offset = 0)
    {
        $this->query['offset'] = (int) $offset;
        return $this;
    }

    public function limit($reset = false)
    {
        if ($reset === true) {
            $this->query['limit'] = '';
            return $this;
        }
        $num = func_num_args();
        $args = func_get_args();
        if ($num == 2) {
            $this->query['limit'] = sprintf('LIMIT %d,%d', $args[0], $args[1]);
        } else {
            $this->query['limit'] = sprintf('LIMIT %d', $args[0]);
            if (is_numeric($this->query['offset'])) {
                $this->query['limit'] = sprintf('LIMIT %d,%d', $this->query['offset'], $args[0]);
            }
        }
        return $this;
    }

    public function cache($flag = true)
    {
        if ($flag) {
            Builder::$CACHE['ROW'] = [];
            Builder::$CACHE['ALL'] = [];
        } else {
            unset(
                Builder::$CACHE['ROW'],
                Builder::$CACHE['ALL']
            );
        }
        return $this;
    }

    public function handler()
    {
        return $this->connection;
    }

    public function getLastSql($flag = null)
    {
        return $this->connection->getQueryLog($flag);
    }

    public function getQueryLog($flag = null)
    {
        return $this->connection->getQueryLog($flag);
    }

    public function getQueryTrace($key = null)
    {
        return $this->connection->getQueryTrace($key);
    }

    public function getTableName($name = null, $ident = false)
    {
        $table = $this->table;
        if ($name) {
            is_bool($name) && $name = $this->table;
            $table = $this->connection->getTableName($name);
            if (strpos($name, '.') === false) {
                $ident && $table = $this->ident($table);
            }
        }
        if (stripos($table, ' AS ') === false && $this->alias) {
            $table = sprintf('%s AS %s', $table, $this->alias);
        }
        return $table;
    }

    /**
     * getFields description
     *
     * @param   String $key     [$key 该字段为数组键名]
     * @param   String $value   [$value 该字段为数组值]
     *
     * @return  [type]         [return description]
     */
    public function getFields($key = null, $value = null)
    {
        $fields = $this->fullFields();
        // $column = array_column($fields,null,'field');
        if ($key && $value) {
            $column = array_column($fields, $value, $key);
        } elseif ($key) {
            $column = array_column($fields, $key);
        } else {
            $column = array_column($fields, 'field');
        }
        // $column = array_column($fields, 'type', $key);
        return $column;
    }

    /**
     * $flag true 保留字段 false 移除字段
     *
     */
    public function fullFields($keys = null, $flag = null)
    {
        is_string($keys) && $keysArray = explode(',', $keys);
        is_array($keys) && $keysArray = $keys;
        // var_dump(Builder::$CACHE);
        // $variable = $GLOBALS['Builder.table.fields'][$this->table];
        $variable = Builder::$CACHE['FIELDS'][$this->table];
        if (empty($variable)) {
            $variable = [];
            $result = (array)$this->connection->fullFields($this->table);
            foreach ($result as $row) {
                preg_match('~^([^( ]+)(?:\\((.+)\\))?( unsigned)?( zerofill)?$~', $row['Type'], $match);
                $isPrimary = ($row['Key'] == 'PRI');
                $isPrimary && $this->primaryKey = $row['Field'];
                $variable[$row['Field']] = [
                    'field'          => $row['Field'],
                    'full_type'      => $row['Type'],
                    'type'           => strtoupper($match[1]),
                    'length'         => $match[2],
                    'unsigned'       => ltrim($match[3] . $match[4]),
                    'default'        => ($row['Default'] != '' || preg_match('~char|set~', $match[1]) ? (string)$row['Default'] : ($row['Null'] == 'YES' ? null : '')),
                    'null'           => ($row['Null'] == 'YES'),
                    'auto_increment' => ($row['Extra'] == 'auto_increment'),
                    'on_update'      => (preg_match('~^on update (.+)~i', $row['Extra'], $match) ? $match[1] : ''), //! available since MySQL 5.1.23
                    'collation'      => $row['Collation'],
                    'privileges'     => $row['Privileges'] ? array_flip(preg_split('~, *~', $row['Privileges'])) : '',
                    'comment'        => ($row['Comment'] ? $row['Comment'] : strtoupper($row['Field'])),
                    // "callback"       => (preg_match('~\[F:(.+)\]~i', $row["Comment"], $match) ? $match[1] : ""),
                    'primary'        => $isPrimary,
                ];
            }
            Builder::$CACHE['FIELDS'][$this->table] = $variable;
        }

        if ($flag !== null && $keysArray) {
            $variable = array_filter_keys($variable, $keysArray, $flag);
        }

        return $variable;
    }

    public function autoFill(&$data, $all = true)
    {
        $defaults = $this->defaults();
        $data = array_filter($data, 'is_not_null');
        $all && $data = array_merge($defaults, $data);
        $data = array_intersect_key($data, $defaults);

        foreach ($data as $field => $value) {
            if (empty($value)) {
                if (!is_numeric($value) && is_numeric($defaults[$field])) {
                    $data[$field] = (int)$defaults[$field];
                }
            }
        }
    }

    //所有 非 auto_increment 字段默认值
    public function defaults($keys = null, $flag = null)
    {
        $fieldArray = $this->fullFields($keys, $flag);
        $defaults = [];
        foreach ($fieldArray as $key => $value) {
            if (!$value['auto_increment']) {
                if (in_array($value['type'], ['BIGINT', 'INT', 'MEDIUMINT', 'SMALLINT', 'TINYINT'])) {
                    $value['default'] = (int)$value['default'];
                }
                $defaults[$value['field']] = $value['default'];
            }
        }
        return $defaults;
    }

    public function ident($field)
    {
        return $this->connection->addIdent($field);
    }

    public function toArray($data = null)
    {
        is_null($data) && $data = $this->response;
        if (empty($data)) {
            return [];
        }
        return json_decode(json_encode($data), true);
    }

    public function toObject($data = null)
    {
        is_null($data) && $data = $this->response;
        if (empty($data)) {
            return new stdClass();
        }
        return json_decode(json_encode($data));
    }

    public function isMultiArray($arr)
    {
        return is_array($arr) && count(array_filter($arr, 'is_array')) > 0;
    }

    /**
     * 判断数组的键是否为纯数字
     *
     * @param array $array
     * @return boolean
     */
    public function isAssocArr($array)
    {
        $arr = array_filter(array_keys($array), function ($key) {
            return !is_numeric($key);
        });
        return empty($arr);
    }

    /**
     * 判断数组的值是否为纯数字
     *
     * @param array $array
     * @return boolean
     */
    public function isNumArr($array)
    {
        $arr = array_filter($array, function ($value) {
            return !is_numeric($value);
        });
        return empty($arr);
    }

    /**
     * 判断数组是否为二维数组
     *
     * @param array $array
     * @return boolean
     */
    public function isTwoArr($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $arr = array_filter($array, function ($value) {
            return !is_array($value);
        });
        return empty($arr);
    }

    /**
     * 验证字段名是否合法
     * @param string $field 字段名
     * @return bool
     */
    protected function validateFieldName($field)
    {
        // 只允许字母、数字、下划线，且必须以字母或下划线开头
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field)) {
            return false;
        }

        // SQL关键字黑名单
        // $keywords = [
        //     'SELECT', 'FROM', 'WHERE', 'DROP', 'DELETE', 'UPDATE', 'INSERT',
        //     'TABLE', 'DATABASE', 'UNION', 'JOIN', 'EXEC', 'EXECUTE', 'SCRIPT'
        // ];

        // if (in_array(strtoupper($field), $keywords)) {
        //     return false;
        // }

        return true;
    }

    /**
     * 验证排序方向是否合法
     * @param string $direction 排序方向
     * @return bool
     */
    protected function validateOrderDirection($direction)
    {
        $allowed = ['ASC', 'DESC', 'asc', 'desc'];
        return in_array($direction, $allowed);
    }

    public function throwError($ex)
    {
        $text = $ex->getMessage();
        $code = $ex->getCode();

        if ($ex instanceof sException) {
            $code = $ex->getState();
        }
        $driver = $this->connection->getDriverName();
        if ($driver == 'sqlite') {
            if (strpos($text, 'no such table') !== false) {
                $code = '42S02'; //表不存在
            }
        }

        $ekey = 'SQLSTATE:' . $code;
        $event = $this->events['called'][$ekey];
        // var_dump($this->events,$ekey);
        // var_dump($ekey,$this->events);
        if ($event) {
            $count = &Builder::$CACHE['ERROR'][$ekey];
            if (is_callable($event) && intval($count) < 1) {
                $count++;
                $flag = call_user_func_array($event, [$this, $ex]);
                if ($flag === true) {
                    return true;
                }
                if ($flag instanceof PDOStatement) {
                } else {
                    is_null($flag) or $code .= $flag;
                }
            }
        }
        $this->lastQuery && $text = $this->lastQuery . '<hr />' . $text;
        throw new sException($text, $code);
    }
}
