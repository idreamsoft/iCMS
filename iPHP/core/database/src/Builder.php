<?php
//https://learnku.com/docs/laravel/7.x/queries/7494
class Builder implements Iterator
{
    protected $table;
    protected $alias = null;
    protected $fields = '*';
    protected $primaryKey = 'id';
    protected $casts = array();
    protected $events = array();
    protected $response = array();
    protected $autoFill = false;

    protected $query = array();
    protected $params = array();
    protected $lastQuery = null;
    public static $CACHE = array();
    private $connection = array();

    /**
     * @param Connection $connection
     */
    public function __construct($connection, $table)
    {
        if (empty($table)) throw new Exception('EMPTY TABLE NAME');

        $this->params     = array();
        $this->query      = array();

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
    public function rewind()
    {
        reset($this->response);
    }

    public function current()
    {
        return current($this->response);
    }

    public function key()
    {
        return key($this->response);
    }

    public function next()
    {
        return next($this->response);
    }

    public function valid()
    {
        $data = $this->current() !== false;
        return $data;
    }

    public function setValue($key, $value)
    {
        $this->$key = $value;
    }

    public function setEvents($param1 = null, $param2 = null, $param3 = null)
    {
        $num = func_num_args();
        if ($num == 1) {
            $this->events = array_merge_recursive($this->events, $param1);
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
     * attrGeted attrUpdated attrCreated attrDeleted
     * attrChanged
     * changed
     * attrEvent
     * geted,updated,created,deleted
     */
    public function runEvents($event = null, $isMulti = false)
    {
        $events = $this->events[$event];
        $response = $this->toArray();
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
            //执行 attrGeted attrUpdated attrCreated attrDeleted事件
            if ($events) foreach ($events as $attr => $func) {
                if (array_key_exists($attr, $response)) {
                    $value = $response[$attr];
                    call_user_func_array($func, [$value, $this, $attr, $isMulti]);
                }
            }
        }

        if ($event == 'created' || $event == 'updated') {
            //变更事件绑定
            $events = $this->events['changed'];
            if ($events['changed']) {
                $changed = $events['changed'];
                unset($events['changed']);
            }
            //执行 attrChanged
            if ($events) foreach ($events as $attr => $func) {
                if (array_key_exists($attr, $response)) {
                    $value = $response[$attr];
                    call_user_func_array($func, [$value, $event, $this, $attr]);
                }
            }
            $changed && call_user_func_array($changed, [$response, $event, $this]);
        }

        //所有事件绑定
        $events = $this->events['event'];
        //执行 attrEvent
        if ($events) foreach ($events as $attr => $func) {
            if (array_key_exists($attr, $response)) {
                $value = $response[$attr];
                call_user_func_array($func, [$value, $event, $this]);
            }
        }

        //执行 geted,updated,created,deleted事件
        $eventFunc && call_user_func_array($eventFunc, [$response, $this, $isMulti]);
    }
    //输出值 自动转换
    public function runCasts(&$items)
    {
        if ($items) {
            $isObject = is_object($items);
            $isObject && $items = $this->toArray($items);
            foreach ($items as $key => &$value) {
                if (is_array($value)) {
                    $this->runCasts($value);
                } else {
                    $type = $this->casts[$key];
                    if ($events = $this->events['geted'][$key]) {
                        $type = $events;
                        $this->casts[$key] = $events;
                        unset($this->events['geted'][$key]);
                    }
                    if (is_array($type)) {
                        $call = $type;
                        $type = 'call';
                    } elseif (strpos($type, '::')) {
                        $call = explode('::', $type);
                        $type = 'call';
                    } elseif (strpos($type, ':')) {
                        list($type, $fomat) = explode(':', $type);
                    }
                    switch ($type) {
                        case 'as':
                            $items[$fomat] = $value;
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
                            call_user_func_array($call, array(&$value, $this));
                            break;
                        case 'array':
                            $value = json_decode($value, true);
                            empty($value) && $value = array();
                            break;
                        default:
                            is_array($value) && $value = json_encode($value);
                    }
                }
            }
            $isObject && $items = $this->toObject($items);
        }
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
        } else if (is_array($key) && is_null($value)) {
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
        $result = array();
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $result[] = json_encode($value);
            } elseif (is_null($value)) {
                $result[] = '';
            } else {
                $result[] = $value;
            }
        }
        $length = substr_count($this->lastQuery, '?');
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
    public function setAutoFill($value){
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
            $fields = implode(",", $args);
        } else {
            // "id,title"
            $fields = $args[0];
            if (is_bool($args[0]) && $args[0] === true) {
                $fieldArray = $this->fullFields();
                $fieldKeys = array_keys($fieldArray);
                $fields = implode(",", $fieldKeys);
            } elseif (is_array($args[0])) {
                //[id,title]
                $fields = implode(",", $args[0]);
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
        $fieldKeys = array_keys($fieldArray);
        $fields = implode(",", $fieldKeys);
        $this->fields = $fields;
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
            $cacheKey = md5($this->lastQuery);
            $response = Builder::$CACHE['ALL'][$cacheKey];
        }

        try {
            if (empty($response)) {
                $this->response = $this->connection->select($query, $params);
                $this->runCasts($this->response);
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
        $result = call_user_func_array(array($this, 'all'), $args);
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

            return $this->toObject();
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
        $obj = call_user_func_array(array($this, 'row'), $args);
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
    public function updateOrCreate($data, $where = array(), $params = null)
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
            // var_dump($query, $params);
            $this->runEvents('deleted');
            return $this->connection->delete($query, $params);
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
    /**
     * lastId 方式还未完成
     *
     * @param integer $pageSize
     * @param string $unit
     * @return void
     */
    public function paging($pageSize = 20, $unit = '')
    {
        $total    = Paging::getTotal();
        $total    = $total ?: $this->count();
        $pageSize = Paging::getPageSize($pageSize);
        $offset   = Paging::get($total, $pageSize, $unit);
        if ($lastId = Paging::getLastId()) {
            $whereLastId = [$this->primaryKey, '<', $lastId];
        }
        if ($offset || $lastId) {
            // if ($offset > 1000 && $total > 2000 && $offset >= $total / 2) {
            if ($offset >= $total / 2) {
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

            // $ids_array = i D xB::all("
            //     SELECT `id` FROM `#iCMS@__article` {$sql}
            //     ORDER BY {$orderby} {$limit}
            // ");
            if (isset($_offset)) {
                $idArray = array_reverse($idArray, true);
                // $orderby   = "id DESC";
            }

            // $ids = iSQL::values($ids_array);
            // $ids = $ids ? $ids : '0';
            // $sql = "WHERE `id` IN({$ids})";
            // }else{
            // $sql = ",(
            // SELECT `id` AS aid FROM `#iCMS@__article` {$sql}
            // ORDER BY {$orderby} {$limit}
            // ) AS art WHERE `id` = art.aid ";
            // }
            $limit = '';
        }
        // echo DB::getQueryLog(1);

        $this->setFields('*');
        if ($idArray) {
            $this->where(null)->where($idArray);
            $this->orderBy($this->primaryKey, $idArray);
            $this->limit(true);
        } else {
            $this->limit($offset, $pageSize);
        }
        $result = $this->select();
        // echo DB::getQueryLog(1);

        $column = array_column($result, $this->primaryKey);
        Paging::setLastId(end($column));
        Paging::get($total, $pageSize, $unit);
        return $result;
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
    public function value($key = null)
    {
        $result = $this->first();
        $this->query['field'] = null;
        $value = is_null($key) ? current($result) : $result[$key];
        return $value;
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
        $query  = $this->makeQuery($s);
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
        $pieces = array();
        $this->query['on']      && $pieces[] = 'ON ' . $this->query['on'];
        $this->query['where']   && $pieces[] = 'WHERE ' . $this->query['where'];
        $this->query['groupBy'] && $pieces[] = $this->query['groupBy'];
        $this->query['having']  && $pieces[] = 'HAVING ' . $this->query['having'];
        $this->query['orderBy'] && $pieces[] = $this->query['orderBy'];
        $this->query['limit']   && $pieces[] = $this->query['limit'];
        $query = implode(' ', $pieces);
        switch ($cmd) {
            case 'SELECT':
                $fields = $this->fields;
                $this->query['field'] && $fields = $this->query['field'];
                $fieldsArray = explode(",", $fields);
                $fieldsArray = array_unique($fieldsArray);
                if ($this->query['distinct']) {
                    $key = array_search($this->query['distinct'], $fieldsArray);
                    if ($key !== false) unset($fieldsArray[$key]);
                    $distinct = $this->getDistinct();
                    array_unshift($fieldsArray, $distinct);
                }
                if ($this->alias) {
                    $fieldsArray = array_map([$this, 'addAlias'], $fieldsArray);
                    if (stripos($this->table, ' AS ') === false) {
                        $this->table = sprintf('%s AS %s', $this->table, $this->alias);
                    }
                }
                $tables = [$this->table];
                $this->query['join'] && $tables = array_merge($tables, $this->query['join']);

                $fields = implode(',', $fieldsArray);

                if ($this->query['leftJoin']) {
                    $table = implode(' LEFT JOIN ', $tables);
                } elseif ($this->query['rightJoin']) {
                    $table = implode(' RIGHT JOIN ', $tables);
                } else {
                    if ($this->query['on']) {
                        $table = implode(' JOIN ', $tables);
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
                    "CREATE TABLE %s (%s) ENGINE=%s AUTO_INCREMENT=0 DEFAULT CHARSET=%s;",
                    $this->table,
                    $this->query['create']['fields'],
                    $this->connection->getEngine(),
                    $this->connection->getCharset()
                );
                break;
        }
        // var_dump($sql);
        $this->lastQuery = $sql;
        return $sql;
    }
    public function makeInsert($args, $num)
    {
        $this->query['insert'] = array();
        $data = array();
        $autoFill = false;
        $mode = 'INSERT';
        //create(['aa'=>1,'bb'=>'22'])
        if ($num == 1 && is_array($args[0])) {
            $data = $args[0];
            // } elseif ($num == 2) {
        } else {
            //create('aa','bb')
            if (is_string($args[0])) {
                $data = array($args[0] => $args[1]);
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

            foreach ($data as $field => $value) {
                unset($data[$field]);
                $field = preg_replace('/[^\w\d_\-,]/i', '', $field);
                $data[$field] = $value;
            }
            $this->response = $data;
            $params = array_values($data);
            $fields = array_keys($data);
            $count  = count($fields);
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
        $data = array();
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
                if ($args[1]) {
                    $where = array($this->primaryKey => $args[1]);
                    if (is_array($args[1])) {
                        //判断是否为关联数组
                        $this->isAssocArr($args[1]) or $where = $args[1];
                    }
                    $this->query['where'] = null;
                    $this->where($where);
                }
            } else {
                //update('nickname','aaa');
                $data = array($args[0] => $args[1]);
            }
        }
        $update = $params = array();
        if ($data) {
            $this->response = $data;
            $this->autoFill($data, false);
            foreach ($data as $field => $value) {
                $field = preg_replace('/[^\w\d_\-,]/i', '', $field);
                $field = $this->ident($field);
                $prepare = sprintf('%s = ?', $field);
                if (is_array($value)) {
                    $vc = count($value);
                    $exp = null;
                    //['raw'=>'CONCAT(?,dir)',1]
                    //['raw'=>'REPLACE(title,?,?)',[1,2]]
                    if ($raw = $value['raw']) {
                        $prepare = sprintf(" %s = %s ",$field,$raw);
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
                            $prepare = sprintf(" %s = %s %s ? ", $field, $expKey, $exp);
                            // $prepare = " {$field} = {$expKey} {$exp} ? ";
                            $params[] = $val;
                        } else if ($vc == 2 && $exp == '=') { //['=',0]
                            $params[] = $value[1];
                        } else {
                            $params[] = $value;
                        }
                    }
                } else {
                    if (is_object($value)) {
                        $raw = $value->raw;
                        $prepare = sprintf(" %s = %s", $field, $raw);
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
            $this->where($field, $value);
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
    public function whereRaw($args, $params = array(), $split = ' AND ', $flag = false)
    {
        //where(null)
        if (is_null($args[0])) {
            $this->query['where'] = '';
            return $this;
        }

        $query    = array();
        $expArray = explode(',', '=,<,>,<>,!=,<=,>=,like,not like');
        $fxpArray = explode(',', 'in,not in,between,not between');
        if (is_string($args)) {
            //where('id = 1 and aa=2')
            $query[] = $args;
            is_array($params) && $this->setParams($params);
            // is_array($params) && array_push($this->params, $params);
        } else {
            $num = count($args);
            $where = array();
            // var_dump($args[0]);
            if ($num == 1) {
                // where([
                //     'id = ?',
                //     'aaa=?'
                // ])
                if (is_array($args[0])) {
                    if ($this->isAssocArr($args[0]) && $this->isNumArr($args[0])) {
                        //where([1,2,3,4])
                        $args[0] = array_map('intval', $args[0]);
                        $where[] = array($this->primaryKey, 'IN', $args[0]);
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
                                            $where[] = array($value[0], 'IN', $value[1]);
                                        } else {
                                            $where[] = array($value[0], '=', $value[1]);
                                        }
                                    } else {
                                        //where(array(
                                        //    array('id','>',1),
                                        //    array('vid','not in',[1,2,3])
                                        //))
                                        $where[] = array($value[0], $value[1], $value[2]);
                                    }
                                } else {
                                    if ($this->isAssocArr($value) && $this->isNumArr($value)) {
                                        //where(array('id'=>[1,2,3]))
                                        $where[] = array($key, 'IN', $value);
                                    } else {
                                        $exp = strtolower(trim($value[0]));
                                        $exp1 = in_array($exp, $expArray);
                                        $exp2 = in_array($exp, $fxpArray);
                                        if ($vc == 2 && ($exp1 || $exp2)) {
                                            //where(array('id'=>array('>',1)))
                                            //where(array('title'=>array('REGEXP',1)))
                                            $where[] = array($key, $value[0], $value[1]);
                                        } else {
                                            //where(array('id'=>[1,2,3]))
                                            $where[] = array($key, 'IN', $value);
                                        }
                                    }
                                }
                            } else {
                                //where(array('id'=>1,'aa'=>11))
                                //where(array('id'=>DB::raw()))
                                $where[] = array($key, '=', $value);
                            }
                        }
                    }
                } elseif (is_object($args[0])) {
                    $where[] = $args[0]->raw;
                } else {
                    //where(10)
                    if (is_numeric($args[0])) {
                        $where[] = array($this->primaryKey, '=', (int)$args[0]);
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
                    $this->setParams($args[1]);
                } else {
                    if (is_array($args[1]) && strpos($args[0], '?') !== false) {
                        //where(
                        //     'id = ? and aaa=?',
                        //     [1,2]
                        // )
                        $where[] = $args[0];
                        $this->setParams($args[1]);
                    } else {
                        if (is_array($args[1])) {
                            //where('id',[1,2])
                            $where[] = array($args[0], 'IN', $args[1]);
                        } else {
                            //where('id',1)
                            $where[] = array($args[0], '=', $args[1]);
                        }
                    }
                }
            } else {
                //where('id','>=',1)
                //where('id','>=',DB::raw('sql'))
                $args && $where[] = $args;
            }
            // var_dump($where);
            foreach ($where as $value) {
                if (is_array($value)) {
                    $field = $value[0];
                    $field = preg_replace('/[^\w\d_\(\),\.]/i', '', $field);
                    if (stripos($field, '.') !== false) {
                        // list($t,$f) = explode('.',$field);
                    } else if (stripos($field, '(') === false && stripos($field, '(') === false) {
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
                            $sql = $value[2]->raw;
                            // $query[] = "{$field} {$value[1]} {$sql}";
                            $qv = $sql;
                        } else {
                            // $query[] = "{$field} {$value[1]} ?";
                            $qv = "?";
                            $this->setParams(array($value[2]));
                        }
                    } else {
                        // in,not in,between,not between
                        if (is_array($value[2])) {
                            //array('id' => ['between', [1, 2]])
                            if (stripos($value[1], 'between') !== false) {
                                // id between ? AND ?
                                // $query[] = "{$field} {$value[1]} ? AND ?";
                                $qv = '? AND ?';
                            } else {
                                $vc = count($value[2]);
                                $marks = implode(',', array_fill(0, $vc, '?'));
                                // id IN (?,?,?)
                                // $query[] = "{$field} {$value[1]} (" . $marks . ")";
                                $qv = sprintf('(%s)',$marks);
                            }
                            // $value[2] = array_map('strval', $value[2]);
                            $this->setParams($value[2]);
                        } elseif (is_object($value[2])) {
                            $sql = $value[2]->raw;
                            // 子查询
                            //where('id','>=',DB::raw('sql'))
                            // id IN (sql)
                            // $query[] = "{$field} {$value[1]} ({$sql}) ";
                            $qv = sprintf('(%s)',$sql);
                            // $this->setParams(array($value[2]));                            
                        } else {
                            // var_dump($value);
                            // id IN (?)
                            // $query[] = "{$field} {$value[1]} (?) ";
                            $qv = '(?)';
                            $this->setParams(array($value[2]));
                        }
                    }
                    $query[] = sprintf(" %s %s %s ", $field, $value[1], $qv);
                } else {
                    //原生SQL
                    $query[] = $value;
                }
            }
        }
        // var_dump($query);
        // var_dump($this->query['where']);
        $query && $whereSql = implode($split, $query);
        if ($flag) return $whereSql;
        if ($whereSql) {
            $prefix = $this->query['where'] ? $split : '';
            $this->query['where'] .= $prefix . $whereSql;
        }
        return $this;
    }
    /**
     * 排给定的ID数组排序
     * 等价于 order by FIELD(`id`, "60,2,18,4,3,20,50");
     */
    public function orderByField($field, $idArray)
    {
        $array = array_column($this->response, $field);
        $result = array();
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
    public function orderBy($field = null, $by = 'DESC')
    {
        $field === null && $field = $this->primaryKey;
        if (is_array($by)) {
            $this->setEvents('geted', 'orderByField', array($field, $by));
            $this->query['orderBy'] = '';
        } else {
            if (strpos($field, ' ') !== false) {
                list($field, $by) = explode(' ', $field);
            }
            $this->addAlias($field);
            $this->query['orderBy'] = sprintf('ORDER BY %s %s', $field, $by);
        }
        $this->query['by'] = $by;
        return $this;
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
            $field = $this->ident($field);
            return $field;
        }
        if ($this->alias) {
            $field = $this->ident($field);
            $field = sprintf('%s.%s', $this->alias, $field);
        }
        return $field;
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
        $this->query['field'] = sprintf("COUNT(%s) AS _count",$field);
        $orderby = $this->query['orderBy'];
        $limit   = $this->query['limit']; //保存原有limit
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
        $this->query['field'] = sprintf("MAX(%s) AS _max",$field);
        return $this->value('_max');
    }
    public function min($field = null)
    {
        $field === null && $field = $this->primaryKey;
        $this->addAlias($field);
        $this->query['field'] = sprintf("MIN(%s) AS _min",$field);
        return $this->value('_min');
    }
    public function sum($field = null)
    {
        if (empty($field)) {
            return false;
        }
        $this->query['field'] = sprintf("SUM(%s) AS _sum",$field);
        return $this->value('_sum');
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
        $data[$field] = array('+', $step);
        $this->update($data);
        return $this;
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
        $data[$field] = array('-', $step);
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
    public function join($vars)
    {

        if ($vars) foreach ($vars as $key => $value) {
            //$join[] = [
            //     ['id','=','nodeMap.iid']
            //     [NodeMap::getTableName(),'nodeMap'],
            //     DB::raw($sql)
            // ];
            if (is_array($value[1])) {
                $table = $value[1][0];
                $table = sprintf('%s AS %s', $table, $value[1][1]);
            } else {
                //$join[] = [
                //     ['id','=','nodeMap.iid']
                //     NodeMap::getTableName(),
                //     DB::raw($sql)
                // ];
                $table = $value[1];
            }
            $this->where(...$value[0]);
            $table && $this->query['join'][] = $table;
            $value[2] && $this->where($value[2]);
        }
        return $this;
    }
    public function leftJoin($vars)
    {
        $this->join($vars);
        $this->query['join'] && $this->query['leftJoin'] = true;
        return $this;
    }
    public function rightJoin($vars)
    {
        $this->join($vars);
        $this->query['join'] && $this->query['rightJoin'] = true;
        return $this;
    }
    public function on()
    {
        $args = func_get_args();
        $this->query['on'] = $this->whereRaw($args, null, ' AND ', true);
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
        $num  = func_num_args();
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
            Builder::$CACHE['ROW']    = array();
            Builder::$CACHE['ALL'] = array();
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
        }
        if (strpos($name, '.')===false) {
            $ident && $table = $this->ident($table);
        }
        return $table;
    }
    /**
     * [getFields description]
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
            $variable = array();
            $result = $this->connection->fullFields($this->table);
            foreach ($result as $row) {
                preg_match('~^([^( ]+)(?:\\((.+)\\))?( unsigned)?( zerofill)?$~', $row["Type"], $match);
                $isPrimary = ($row["Key"] == "PRI");
                $isPrimary && $this->primaryKey = $row["Field"];
                $variable[$row["Field"]] = array(
                    "field"          => $row["Field"],
                    "full_type"      => $row["Type"],
                    "type"           => strtoupper($match[1]),
                    "length"         => $match[2],
                    "unsigned"       => ltrim($match[3] . $match[4]),
                    "default"        => ($row["Default"] != "" || preg_match("~char|set~", $match[1]) ? (string)$row["Default"] : ($row["Null"] == "YES" ? null : '')),
                    "null"           => ($row["Null"] == "YES"),
                    "auto_increment" => ($row["Extra"] == "auto_increment"),
                    "on_update"      => (preg_match('~^on update (.+)~i', $row["Extra"], $match) ? $match[1] : ""), //! available since MySQL 5.1.23
                    "collation"      => $row["Collation"],
                    "privileges"     => array_flip(preg_split('~, *~', $row["Privileges"])),
                    "comment"        => ($row["Comment"] ? $row["Comment"] : strtoupper($row["Field"])),
                    // "callback"       => (preg_match('~\[F:(.+)\]~i', $row["Comment"], $match) ? $match[1] : ""),
                    "primary"        => $isPrimary,
                );
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
        $defaults = array();
        foreach ($fieldArray as $key => $value) {
            if (!$value['auto_increment']) {
                if (in_array($value['type'], array('BIGINT', 'INT', 'MEDIUMINT', 'SMALLINT', 'TINYINT'))) {
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
            return array();
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
    public function isAssocArr($arr)
    {
        $keys = array_keys($arr);
        $ks   = implode('', $keys);
        return is_numeric($ks);
    }
    public function isNumArr($arr)
    {
        $values = array_values($arr);
        $vs   = @implode('', $values);
        return is_numeric($vs);
    }
    public function throwError($ex)
    {

        $text = $ex->getMessage();
        $code = $ex->getCode();
        if ($ex instanceof sException) {
            $code = $ex->getState();
        }
        // $this->lastQuery && $text = $this->lastQuery . '<hr />' . $text;

        $ekey = 'SQLSTATE:' . $code;
        $event = $this->events['called'][$ekey];
        // var_dump($this->events,$ekey);
        // var_dump($event);
        if ($event) {
            $count = &Builder::$CACHE['ERROR'][$ekey];
            if (is_callable($event) && intval($count) < 1) {
                $count++;
                $flag = call_user_func_array($event, array($this, $ex));
                if ($flag instanceof PDOStatement) {
                } else {
                    is_null($flag) or $code .= $flag;
                }
                throw new sException($text, $code);
            }
        }
        if ($ex instanceof sException) {
            throw $ex;
        }
        throw new sException($text, $code);
    }
}
