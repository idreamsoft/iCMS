我将提供完整的使用示例，并指出需要优化的地方。

# iCMS8 数据库操作类 - 使用示例与优化建议

## 一、完整使用示例

### 1. 基础配置与初始化

```php
<?php
// 配置数据库连接
$config = [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'icms',
            'username' => 'root',
            'password' => 'password',
            'charset' => 'utf8mb4',
            'prefix' => 'icms_',
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => __DIR__ . '/database.sqlite',
            'prefix' => '',
        ],
    ],
];

DB::config($config);

// 启用调试模式
DB::debug(2); // 记录 SQL + 调用堆栈
```

### 2. 基础 CRUD 操作

```php
// ============ 查询操作 ============

// 查询所有记录
$users = DB::table('users')->select();

// 查询单条记录
$user = DB::table('users')->where('id', 1)->get();
$user = DB::table('users')->get(1); // 简写

// 查询指定字段
$users = DB::table('users')
    ->field('id', 'username', 'email')
    ->where('status', 'active')
    ->select();

// 条件查询
$users = DB::table('users')
    ->where('age', '>', 18)
    ->where('status', 'active')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->select();

// 获取单个值
$username = DB::table('users')->where('id', 1)->value('username');

// 获取列数组
$ids = DB::table('users')->where('status', 'active')->pluck('id');
$userMap = DB::table('users')->pluck('username', 'id'); // ['1'=>'John', '2'=>'Jane']

// ============ 插入操作 ============

// 插入单条记录
$userId = DB::table('users')->create([
    'username' => 'john',
    'email' => 'john@example.com',
    'password' => password_hash('123456', PASSWORD_DEFAULT),
    'created_at' => time(),
]);

// 插入或忽略（重复则忽略）
$userId = DB::table('users')->ignore([
    'username' => 'john',
    'email' => 'john@example.com',
]);

// 替换插入（重复则替换）
$userId = DB::table('users')->replace([
    'id' => 1,
    'username' => 'john_updated',
]);

// ============ 更新操作 ============

// 更新记录
$affected = DB::table('users')->update([
    'username' => 'john_new',
    'updated_at' => time(),
], 1); // 主键为 1

// 使用 where 条件更新
$affected = DB::table('users')
    ->where('status', 'inactive')
    ->update(['status' => 'deleted']);

// 字段自增/自减
$affected = DB::table('posts')
    ->where('id', 1)
    ->update([
        'views' => ['+', 1],           // views = views + 1
        'score' => ['score', '+', 10], // score = score + 10
    ]);

// 更新或创建
$result = DB::table('users')->updateOrCreate(
    ['username' => 'john'],           // 更新数据
    ['email' => 'john@example.com'],  // 查询条件
    true                              // 如果不存在，合并条件到数据
);

// ============ 删除操作 ============

// 删除单条
$affected = DB::table('users')->delete(1);

// 条件删除
$affected = DB::table('users')
    ->where('status', 'deleted')
    ->where('created_at', '<', time() - 86400 * 30)
    ->delete();

// 清空表
DB::table('temp_data')->truncate();

// 删除表
DB::table('old_table')->drop();
```

### 3. 复杂查询示例

```php
// ============ 多条件查询 ============

// AND 条件
$users = DB::table('users')
    ->where([
        ['age', '>', 18],
        ['status', '=', 'active'],
        ['city', 'in', ['Beijing', 'Shanghai']],
    ])
    ->select();

// OR 条件
$users = DB::table('users')
    ->where('role', 'admin')
    ->orWhere('role', 'moderator')
    ->select();

// 混合条件
$users = DB::table('users')
    ->where([
        'status' => 'active',
        'age' => ['>', 18],
        'city' => ['in', ['Beijing', 'Shanghai']],
    ])
    ->select();

// BETWEEN 查询
$users = DB::table('users')
    ->where('age', 'between', [18, 30])
    ->select();

// LIKE 查询
$users = DB::table('users')
    ->where('username', 'like', '%john%')
    ->select();

// FIND_IN_SET 查询
$posts = DB::table('posts')
    ->where(['tags', 'find_in_set', ['tech', 'news']])
    ->select();

// 原生 SQL 条件
$users = DB::table('users')
    ->where('DATE(created_at) = CURDATE()')
    ->select();

// 子查询
$activeUserIds = DB::raw('SELECT user_id FROM sessions WHERE active = 1');
$users = DB::table('users')
    ->where('id', 'in', $activeUserIds)
    ->select();

// ============ 连接查询 ============

$result = DB::table('users')
    ->join('orders')
    ->on('users.id', 'orders.user_id')
    ->where('orders.status', 'completed')
    ->select();

$result = DB::table('users')
    ->leftJoin('profiles')
    ->on('users.id', 'profiles.user_id')
    ->field('users.*', 'profiles.avatar')
    ->select();

// ============ 分组与聚合 ============

// 分组统计
$stats = DB::table('orders')
    ->field('user_id', 'COUNT(*) as order_count', 'SUM(amount) as total')
    ->groupBy('user_id')
    ->select();

// 聚合函数
$count = DB::table('users')->count();
$total = DB::table('orders')->sum('amount');
$avg = DB::table('products')->avg('price');
$max = DB::table('products')->max('price');
$min = DB::table('products')->min('price');

// ============ 排序与分页 ============

// 排序
$users = DB::table('users')
    ->orderBy('created_at', 'DESC')
    ->orderBy('id', 'ASC')
    ->select();

// 多字段排序
$users = DB::table('users')
    ->orderBy(['created_at' => 'DESC', 'id' => 'ASC'])
    ->select();

// 按指定 ID 数组排序
$users = DB::table('users')
    ->where('id', [5, 3, 1, 2, 4])
    ->orderBy('id', [5, 3, 1, 2, 4])
    ->select();

// 分页
$users = DB::table('users')
    ->page(1, 20) // 第1页，每页20条
    ->select();

// 高级分页（带总数统计）
$users = DB::table('users')
    ->paging(20, 1) // 每页20条，第1页
    ->select();
```

### 4. 模型使用示例

```php
// ============ 定义模型 ============

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'uid';
    protected $fields = 'uid,username,email,avatar,status,created_at';
    
    // 类型转换
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'is_active' => 'boolean',
        'settings' => 'array',
        'tags' => 'strlist',
        'password' => 'delete', // 查询时删除此字段
    ];
    
    // 事件绑定
    protected $events = [
        'creating' => [__CLASS__, 'beforeCreate'],
        'created' => [__CLASS__, 'afterCreate'],
        'updating' => [__CLASS__, 'beforeUpdate'],
        'geted' => [
            'avatar' => [__CLASS__, 'formatAvatar'],
        ],
    ];
    
    // 创建前事件
    public static function beforeCreate($data, $builder)
    {
        // 自动加密密码
        if (isset($data['password'])) {
            $builder->setResponse('password', password_hash($data['password'], PASSWORD_DEFAULT));
        }
        // 设置创建时间
        $builder->setResponse('created_at', time());
    }
    
    // 创建后事件
    public static function afterCreate($data, $builder)
    {
        // 记录日志
        error_log("User created: " . $data['uid']);
    }
    
    // 更新前事件
    public static function beforeUpdate($data, $builder)
    {
        $builder->setResponse('updated_at', time());
    }
    
    // 字段格式化
    public static function formatAvatar(&$value, $builder)
    {
        if ($value && strpos($value, 'http') !== 0) {
            $value = 'https://cdn.example.com/' . $value;
        }
    }
}

// ============ 使用模型 ============

// 查询
$user = UserModel::where('uid', 1)->get();
$users = UserModel::where('status', 'active')->select();

// 创建
$uid = UserModel::create([
    'username' => 'john',
    'email' => 'john@example.com',
    'password' => '123456', // 会自动加密
]);

// 更新
UserModel::update(['username' => 'john_new'], 1);

// 删除
UserModel::delete(1);

// 获取表单数据并过滤
$data = UserModel::postData('user');
$uid = UserModel::create($data);

// 检查重复
$exists = UserModel::check(['username' => 'john'], 1); // 排除 uid=1
```

### 5. 事务处理示例

```php
// ============ 闭包事务 ============

try {
    $result = DB::transaction(function($db) {
        // 创建用户
        $userId = $db->table('users')->create([
            'username' => 'john',
            'email' => 'john@example.com',
        ]);
        
        // 创建用户资料
        $db->table('profiles')->create([
            'user_id' => $userId,
            'avatar' => 'default.jpg',
        ]);
        
        // 初始化积分
        $db->table('points')->create([
            'user_id' => $userId,
            'points' => 100,
        ]);
        
        return $userId;
    });
    
    echo "User created: $result";
} catch (Exception $e) {
    echo "Transaction failed: " . $e->getMessage();
}

// ============ 手动事务控制 ============

DB::beginTransaction();
try {
    // 扣减库存
    $affected = DB::table('products')
        ->where('id', 1)
        ->update(['stock' => ['stock', '-', 1]]);
    
    if ($affected === 0) {
        throw new Exception('库存不足');
    }
    
    // 创建订单
    $orderId = DB::table('orders')->create([
        'product_id' => 1,
        'user_id' => 1,
        'quantity' => 1,
    ]);
    
    DB::commit();
    echo "Order created: $orderId";
} catch (Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage();
}
```

### 6. 高级功能示例

```php
// ============ 表操作 ============

// 检查表是否存在
if (DB::connection()->hasTable('users')) {
    echo "Table exists";
}

// 复制表结构
DB::table('users')->copy('users_backup');

// 重命名表
DB::table('old_users')->rename('new_users');

// 获取表结构
$ddl = DB::table('users')->ddl();

// 获取表字段信息
$fields = DB::table('users')->show('COLUMNS');

// ============ 原生查询 ============

// 执行原生 SQL
$users = DB::select('SELECT * FROM icms_users WHERE status = ?', ['active']);

// 执行更新
$affected = DB::update('UPDATE icms_users SET status = ? WHERE id = ?', ['inactive', 1]);

// 执行插入
$id = DB::insert('INSERT INTO icms_users (username, email) VALUES (?, ?)', ['john', 'john@example.com']);

// 执行删除
$affected = DB::delete('DELETE FROM icms_users WHERE id = ?', [1]);

// 执行语句
DB::statement('ALTER TABLE icms_users ADD COLUMN phone VARCHAR(20)');

// ============ 查询日志 ============

DB::debug(2);
$users = DB::table('users')->where('id', 1)->get();
$log = DB::getQueryLog(); // 获取查询日志
echo $log;

// ============ 多连接使用 ============

// 使用默认连接
$users = DB::table('users')->select();

// 使用指定连接
$users = DB::connection('sqlite')->table('users')->select();

// 切换默认连接
DB::setDefaultConnection('sqlite');
```

---

## 二、需要优化的问题

### 🔴 严重问题

#### 1. **SQL 注入风险**

**位置**: `@d:\src\cms\iCMS8\iPHP\core\database\src\Builder.php:1440-1441`

```php
// orderBy('id ASC') id ASC
// orderBy('id ASC,aaa DESC') id ASC,aaa DESC
if (is_string($field) && (stripos($field, ' ') !== false || stripos($field, ',') !== false)) {
    $sql = $field; //存在注入问题
}
```

**问题**: 直接拼接用户输入的排序字段，未做任何过滤，存在严重 SQL 注入风险。

**示例攻击**:
```php
// 恶意输入
$userInput = "id DESC; DROP TABLE users; --";
DB::table('posts')->orderBy($userInput)->select();
// 生成: SELECT * FROM posts ORDER BY id DESC; DROP TABLE users; --
```

---

#### 2. **字段名过滤不完整**

**位置**: `@d:\src\cms\iCMS8\iPHP\core\database\src\Builder.php:976-977`

```php
foreach ($data as $field => $value) {
    unset($data[$field]);
    $field = preg_replace('/[^\w\d_\-,]/i', '', $field);
    $data[$field] = $value;
}
```

**问题**: 
- 允许逗号 `,` 和连字符 `-` 在字段名中，可能导致问题
- 过滤后未验证字段名是否为空
- 允许数字开头的字段名（不符合 SQL 规范）

---

#### 3. **缺少连接池管理**

**位置**: `@d:\src\cms\iCMS8\iPHP\core\database\src\DB.php:67-69`

```php
if (empty(self::$handle[$cacheKey])) {
    self::$handle[$cacheKey] = Manager::getInstance()->connect($name);
}
```

**问题**:
- 连接永不释放，长时间运行会导致连接泄漏
- 没有连接超时检测
- 没有最大连接数限制

---

#### 4. **事务嵌套问题**

**位置**: `@d:\src\cms\iCMS8\iPHP\core\database\src\Connection\Connection.php:323-328`

```php
public function beginTransaction()
{
    if (!$this->transactions) {
        $this->transactions++;
        return $this->getPdo()->beginTransaction();
    }
}
```

**问题**:
- 嵌套事务时，内层 [beginTransaction()](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Connection/Connection.php:322:4-328:5) 不执行任何操作
- 内层 [commit()](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Connection/Connection.php:338:4-344:5) 会提交整个事务
- 没有使用 SAVEPOINT 支持真正的嵌套事务

---

### ⚠️ 性能问题

#### 5. **N+1 查询问题**

**位置**: 事件系统设计缺陷

```php
// 当前实现
$users = UserModel::select(); // 查询 100 条用户
foreach ($users as $user) {
    // 每个用户触发 geted 事件，可能产生额外查询
    $user->posts; // 又查询一次
}
// 总查询: 1 + 100 = 101 次
```

**问题**: 没有预加载（Eager Loading）机制

---

#### 6. **查询缓存机制不完善**

**位置**: `@d:\src\cms\iCMS8\iPHP\core\database\src\Builder.php:449-454`

```php
if (is_array(Builder::$CACHE['ALL'])) {
    $hash = $this->getLastSql();
    if ($hash) {
        $cacheKey = md5($hash);
        $response = Builder::$CACHE['ALL'][$cacheKey];
    }
}
```

**问题**:
- 缓存是全局静态变量，一次请求结束就清空
- 没有缓存过期机制
- 没有缓存大小限制，可能导致内存溢出
- 缓存键只基于 SQL，参数变化也会命中缓存

---

#### 7. **分页性能问题**

**位置**: `@d:\src\cms\iCMS8\iPHP\core\database\src\Builder.php:716-769`

```php
public function paging($pageSize = 20, $page=1, $unit = '')
{
    $total = Paging::getCount();
    $total = $total ?: $this->count(); // 每次都 COUNT(*)
    // ...
}
```

**问题**:
- 每次分页都执行 `COUNT(*)` 查询
- 大表分页使用 OFFSET 性能差
- 深度分页（如第 1000 页）性能极差

---

#### 8. **字段类型转换效率低**

**位置**: `@d:\src\cms\iCMS8\iPHP\core\database\src\Builder.php:226-287`

```php
public function runCasts(&$items)
{
    if ($items) {
        $isObject = is_object($items);
        $isObject && $items = $this->toArray($items);
        foreach ($items as $key => &$value) {
            if (is_array($value)) {
                $this->runCasts($value); // 递归调用
            } else {
                // 每个字段都检查类型
                $type = $this->casts[$key];
                // ...
            }
        }
    }
}
```

**问题**:
- 递归调用效率低
- 每个字段都要多次判断
- 没有缓存转换结果

---

### 🟡 设计问题

#### 9. **缺少预处理语句复用**

**位置**: `@d:\src\cms\iCMS8\iPHP\core\database\src\Connection\Connection.php:264-274`

```php
public function execute($query, $params = [], $pdo = null, $option = null)
{
    // ...
    $this->statement = $pdo->prepare($query, $option); // 每次都 prepare
    $this->prepared($this->statement);
    $this->statement->execute($params);
    // ...
}
```

**问题**:
- 相同 SQL 每次都重新 prepare
- 无法利用数据库的预处理语句缓存

---

#### 10. **事件系统过于复杂**

**位置**: `@d:\src\cms\iCMS8\iPHP\core\database\src\Builder.php:169-211`

```php
public function runEvents($event = null, $isMulti = false)
{
    $events = $this->events[$event];
    $response = $this->getResponse();
    if (empty($response)) {
        return false;
    }
    if ($events) {
        // 多层嵌套判断
        if ($events[$event]) { /* ... */ }
        if ($event == 'geted' && $events['orderByField']) { /* ... */ }
        $this->runFieldEvent($events, $response, $event, $isMulti);
    }
    // ...
}
```

**问题**:
- 事件执行顺序复杂，难以理解
- 字段级事件和表级事件混在一起
- 性能开销大

---

#### 11. **Model 类职责不清**

**位置**: `@d:\src\cms\iCMS8\iPHP\core\Model.php:183-228`

```php
public static function called($method, $params)
{
    $rc = new ReflectionClass('Builder');
    if ($rc->hasMethod($method)) {
        try {
            $builder = static::makeBuilder();
            $call = [$builder, $method];
            $result = call_user_func_array($call, $params);
            return $result;
        } catch (\sException $ex) {
            throw $ex;
        }
    }
}
```

**问题**:
- 每次调用都使用反射，性能差
- Model 既是数据容器又是查询构造器
- 静态调用和实例调用混用，容易混淆

---

#### 12. **错误处理不统一**

**位置**: 多处

```php
// Builder.php
catch (\Exception $ex) {
    return $this->throwError($ex);
}

// Connection.php
catch (PDOException $ex) {
    $pdo->rollBack();
    continue;
}

// DB.php
catch (Exception $e) {
    error_log("SQL Error: " . $e->getMessage());
    throw $e;
}
```

**问题**:
- 异常类型不统一（Exception、PDOException、sException）
- 有的地方吞掉异常，有的地方抛出
- 错误日志格式不统一

---

### 🟢 代码质量问题

#### 13. **大量注释代码未清理**

**位置**: 多处

```php
// public static function where(){}
// public static function value(){}
// public static function get(){}

// private function table($table) { /* ... */ }
// private function select($query, $params = array()) { /* ... */ }
```

**问题**: 影响代码可读性，增加维护成本

---

#### 14. **魔术方法滥用**

**位置**: `@d:\src\cms\iCMS8\iPHP\core\database\src\DB.php:30-44`

```php
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
    }
}
```

**问题**:
- IDE 无法自动补全
- 调试困难
- 性能开销大（反射）

---

#### 15. **缺少类型声明**

**位置**: 所有类

```php
// 当前
public function where($field = null, $value = null) { }

// 应该
public function where(?string $field = null, mixed $value = null): self { }
```

**问题**:
- 无法利用 PHP 7+ 的类型系统
- 容易传入错误类型参数
- IDE 提示不准确

---

#### 16. **命名不规范**

```php
// 不一致的命名
->field()      // 单数
->fullFields() // 复数
->pluck()      // 动词
->count()      // 名词

// 缩写过度
->inc()  // 应该是 increment()
->dec()  // 应该是 decrement()
```

---

## 三、优化建议

### 优先级优化方案

#### 🔴 **P0 - 立即修复（安全问题）**

##### 1. 修复 SQL 注入漏洞

**问题代码**: `Builder.php:1440-1441`

```php
// ❌ 危险代码
if (is_string($field) && (stripos($field, ' ') !== false || stripos($field, ',') !== false)) {
    $sql = $field; // 存在注入问题
}
```

**修复方案**:

```php
// ✅ 安全代码
public function orderBy($field = null, $by = 'DESC')
{
    $field === null && $field = $this->primaryKey;
    
    // 白名单验证
    $allowedDirections = ['ASC', 'DESC', 'asc', 'desc'];
    
    if (is_string($field) && is_array($by)) {
        // 按数组排序
        $this->setEvents('geted', 'orderByField', [$field, $by]);
        $this->query['orderBy'] = '';
    } else {
        $orderByArr = [];
        
        // 处理多字段排序字符串
        if (is_string($field) && strpos($field, ',') !== false) {
            // 分割并验证每个字段
            $parts = explode(',', $field);
            foreach ($parts as $part) {
                $part = trim($part);
                if (preg_match('/^(\w+)(?:\s+(ASC|DESC))?$/i', $part, $matches)) {
                    $f = $this->ident($matches[1]);
                    $d = isset($matches[2]) ? strtoupper($matches[2]) : 'DESC';
                    $orderByArr[] = "$f $d";
                } else {
                    throw new Exception("Invalid order by field: $part");
                }
            }
        } else {
            // 单字段或数组
            $fieldArr = is_array($field) ? $field : [$field => $by];
            foreach ($fieldArr as $f => $_by) {
                if (is_numeric($f)) {
                    $f = $_by;
                    $_by = $by;
                }
                
                // 验证字段名
                if (!preg_match('/^\w+$/', $f)) {
                    throw new Exception("Invalid field name: $f");
                }
                
                // 验证排序方向
                $_by = strtoupper($_by);
                if (!in_array($_by, $allowedDirections)) {
                    throw new Exception("Invalid order direction: $_by");
                }
                
                $orderByArr[] = $this->ident($f) . ' ' . $_by;
            }
        }
        
        $this->query['orderBy'] = 'ORDER BY ' . implode(', ', $orderByArr);
    }
    
    return $this;
}
```

##### 2. 增强字段名过滤

```php
// ✅ 改进的字段过滤
protected function sanitizeFieldName($field)
{
    // 只允许字母、数字、下划线
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field)) {
        throw new Exception("Invalid field name: $field");
    }
    
    // 检查是否为 SQL 关键字
    $keywords = ['SELECT', 'FROM', 'WHERE', 'DROP', 'DELETE', 'UPDATE', 'INSERT'];
    if (in_array(strtoupper($field), $keywords)) {
        throw new Exception("Field name cannot be SQL keyword: $field");
    }
    
    return $field;
}

// 在 makeInsert 中使用
public function makeInsert($args, $num)
{
    // ...
    foreach ($data as $field => $value) {
        $field = $this->sanitizeFieldName($field); // 验证字段名
        $cleanData[$field] = $value;
    }
    // ...
}
```

---

#### ⚠️ **P1 - 高优先级（性能优化）**

##### 3. 实现连接池管理

```php
// ✅ 改进的连接管理
class DB
{
    private static $handle = [];
    private static $connectionTimeout = 3600; // 1小时
    private static $maxConnections = 10;
    private static $connectionTimestamps = [];
    
    public static function connection($name = null)
    {
        if ($name === null) {
            $name = Manager::getInstance()->getDefaultConnection();
        }
        
        $cacheKey = $name ?: 'default';
        
        // 检查连接是否过期
        if (isset(self::$handle[$cacheKey])) {
            $timestamp = self::$connectionTimestamps[$cacheKey] ?? 0;
            if (time() - $timestamp > self::$connectionTimeout) {
                self::purgeConnection($cacheKey);
            }
        }
        
        // 检查最大连接数
        if (count(self::$handle) >= self::$maxConnections) {
            // 清理最旧的连接
            asort(self::$connectionTimestamps);
            $oldestKey = key(self::$connectionTimestamps);
            self::purgeConnection($oldestKey);
        }
        
        if (empty(self::$handle[$cacheKey])) {
            self::$handle[$cacheKey] = Manager::getInstance()->connect($name);
            self::$connectionTimestamps[$cacheKey] = time();
        }
        
        // 配置日志
        self::$handle[$cacheKey]->loggingQueryLog = self::$debug['log'];
        self::$handle[$cacheKey]->loggingQueryTrace = self::$debug['trace'];
        self::$handle[$cacheKey]->loggingQueryExplain = self::$debug['explain'];
        
        return self::$handle[$cacheKey];
    }
    
    // 定期清理过期连接（在请求结束时调用）
    public static function cleanup()
    {
        $now = time();
        foreach (self::$connectionTimestamps as $key => $timestamp) {
            if ($now - $timestamp > self::$connectionTimeout) {
                self::purgeConnection($key);
            }
        }
    }
}
```

##### 4. 优化分页性能

```php
// ✅ 游标分页（适合大数据集）
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
    
    $nextCursor = $hasMore ? end($results)[$this->primaryKey] : null;
    
    return [
        'data' => $results,
        'next_cursor' => $nextCursor,
        'has_more' => $hasMore,
    ];
}

// ✅ 缓存总数
public function paging($pageSize = 20, $page = 1, $cacheKey = null)
{
    // 尝试从缓存获取总数
    $total = null;
    if ($cacheKey) {
        $total = Cache::get("page_count_{$cacheKey}");
    }
    
    if ($total === null) {
        $total = $this->count();
        if ($cacheKey) {
            Cache::set("page_count_{$cacheKey}", $total, 300); // 缓存5分钟
        }
    }
    
    $offset = ($page - 1) * $pageSize;
    
    // 深度分页优化：使用子查询
    if ($offset > 1000) {
        $ids = $this->field($this->primaryKey)
            ->limit($offset, $pageSize)
            ->pluck($this->primaryKey);
        
        if (empty($ids)) {
            return [];
        }
        
        return $this->where($this->primaryKey, $ids)
            ->orderBy($this->primaryKey, $ids)
            ->select();
    }
    
    return $this->limit($offset, $pageSize)->select();
}
```

##### 5. 优化类型转换

```php
// ✅ 缓存转换结果
protected $castCache = [];

public function runCasts(&$items)
{
    if (empty($items) || empty($this->casts)) {
        return $items;
    }
    
    $isObject = is_object($items);
    $isObject && $items = $this->toArray($items);
    
    // 批量处理，减少循环
    foreach ($this->casts as $key => $type) {
        if (!isset($items[$key])) {
            continue;
        }
        
        $value = &$items[$key];
        
        // 检查缓存
        $cacheKey = md5($key . serialize($value) . $type);
        if (isset($this->castCache[$cacheKey])) {
            $value = $this->castCache[$cacheKey];
            continue;
        }
        
        // 执行转换
        $originalValue = $value;
        $this->castValue($value, $type);
        
        // 缓存结果（只缓存简单类型）
        if (!is_array($value) && !is_object($value)) {
            $this->castCache[$cacheKey] = $value;
        }
    }
    
    $isObject && $items = $this->toObject($items);
    return $items;
}

// 提取转换逻辑
protected function castValue(&$value, $type)
{
    if (is_array($type)) {
        call_user_func_array($type, [&$value, $this]);
        return;
    }
    
    if (strpos($type, ':')) {
        list($type, $format) = explode(':', $type, 2);
    }
    
    switch ($type) {
        case 'boolean':
            $value = (bool)$value;
            break;
        case 'int':
        case 'integer':
            $value = (int)$value;
            break;
        case 'float':
            $value = (float)$value;
            break;
        case 'datetime':
            $value = $value ? date($format ?? 'Y-m-d H:i:s', $value) : null;
            break;
        case 'array':
            $value = $value ? json_decode($value, true) : [];
            break;
        case 'json':
            $value = is_array($value) ? json_encode($value) : $value;
            break;
        // ... 其他类型
    }
}
```

##### 6. 实现预加载（解决 N+1 问题）

```php
// ✅ 添加预加载功能
class Model
{
    protected $with = []; // 需要预加载的关联
    
    public static function with($relations)
    {
        $instance = static::getInstance();
        $instance->with = is_array($relations) ? $relations : func_get_args();
        return $instance;
    }
    
    protected function loadRelations(&$results)
    {
        foreach ($this->with as $relation) {
            if (method_exists($this, $relation)) {
                $this->$relation($results);
            }
        }
    }
}

// 使用示例
class UserModel extends Model
{
    // 定义关联
    protected function posts(&$users)
    {
        $userIds = array_column($users, 'id');
        $posts = DB::table('posts')
            ->where('user_id', $userIds)
            ->select();
        
        // 组装数据
        $postsByUser = [];
        foreach ($posts as $post) {
            $postsByUser[$post['user_id']][] = $post;
        }
        
        foreach ($users as &$user) {
            $user['posts'] = $postsByUser[$user['id']] ?? [];
        }
    }
}

// 使用
$users = UserModel::with('posts')->select(); // 只执行 2 次查询
```

---

#### 🟡 **P2 - 中优先级（架构改进）**

##### 7. 实现真正的嵌套事务

```php
// ✅ 使用 SAVEPOINT
class Connection
{
    protected $transactions = 0;
    protected $transactionLevel = 0;
    
    public function beginTransaction()
    {
        $this->transactionLevel++;
        
        if ($this->transactionLevel === 1) {
            // 第一层事务
            return $this->getPdo()->beginTransaction();
        } else {
            // 嵌套事务使用 SAVEPOINT
            $savepoint = "SAVEPOINT_LEVEL_{$this->transactionLevel}";
            return $this->getPdo()->exec("SAVEPOINT {$savepoint}");
        }
    }
    
    public function commit()
    {
        if ($this->transactionLevel === 0) {
            throw new Exception('No active transaction');
        }
        
        if ($this->transactionLevel === 1) {
            // 提交最外层事务
            $result = $this->getPdo()->commit();
        } else {
            // 释放 SAVEPOINT
            $savepoint = "SAVEPOINT_LEVEL_{$this->transactionLevel}";
            $result = $this->getPdo()->exec("RELEASE SAVEPOINT {$savepoint}");
        }
        
        $this->transactionLevel--;
        return $result;
    }
    
    public function rollBack()
    {
        if ($this->transactionLevel === 0) {
            throw new Exception('No active transaction');
        }
        
        if ($this->transactionLevel === 1) {
            // 回滚最外层事务
            $result = $this->getPdo()->rollBack();
        } else {
            // 回滚到 SAVEPOINT
            $savepoint = "SAVEPOINT_LEVEL_{$this->transactionLevel}";
            $result = $this->getPdo()->exec("ROLLBACK TO SAVEPOINT {$savepoint}");
        }
        
        $this->transactionLevel--;
        return $result;
    }
}
```

##### 8. 简化事件系统

```php
// ✅ 重构事件系统
class EventDispatcher
{
    protected $listeners = [];
    
    public function listen($event, $callback)
    {
        $this->listeners[$event][] = $callback;
    }
    
    public function dispatch($event, $data)
    {
        if (!isset($this->listeners[$event])) {
            return $data;
        }
        
        foreach ($this->listeners[$event] as $callback) {
            $data = call_user_func($callback, $data);
        }
        
        return $data;
    }
}

class Builder
{
    protected $eventDispatcher;
    
    public function __construct(Connection $connection, $table)
    {
        // ...
        $this->eventDispatcher = new EventDispatcher();
    }
    
    public function on($event, $callback)
    {
        $this->eventDispatcher->listen($event, $callback);
        return $this;
    }
    
    protected function fireEvent($event, $data)
    {
        return $this->eventDispatcher->dispatch($event, $data);
    }
    
    public function create($data)
    {
        $data = $this->fireEvent('creating', $data);
        $id = $this->insert($data);
        $this->fireEvent('created', ['id' => $id, 'data' => $data]);
        return $id;
    }
}
```

##### 9. 改进缓存机制

```php
// ✅ 使用外部缓存
interface CacheInterface
{
    public function get($key);
    public function set($key, $value, $ttl = 0);
    public function delete($key);
    public function clear();
}

class Builder
{
    protected static $cache;
    
    public static function setCache(CacheInterface $cache)
    {
        self::$cache = $cache;
    }
    
    public function remember($key, $ttl, Closure $callback)
    {
        if (!self::$cache) {
            return $callback();
        }
        
        $value = self::$cache->get($key);
        
        if ($value === null) {
            $value = $callback();
            self::$cache->set($key, $value, $ttl);
        }
        
        return $value;
    }
    
    public function all()
    {
        if (!self::$cache) {
            return $this->executeQuery();
        }
        
        $cacheKey = 'query:' . md5($this->getSql() . serialize($this->params));
        
        return $this->remember($cacheKey, 300, function() {
            return $this->executeQuery();
        });
    }
}

// 使用
Builder::setCache(new RedisCache());
$users = DB::table('users')->remember('all_users', 600, function($query) {
    return $query->select();
});
```

---

#### 🟢 **P3 - 低优先级（代码质量）**

##### 10. 添加类型声明

```php
// ✅ PHP 8+ 类型声明
class Builder
{
    public function where(
        string|array|null $field = null, 
        mixed $operator = null, 
        mixed $value = null
    ): self {
        // ...
        return $this;
    }
    
    public function select(): array
    {
        // ...
    }
    
    public function get(int|string|array|null $value = null): ?array
    {
        // ...
    }
    
    public function create(array $data): int|string
    {
        // ...
    }
}
```

##### 11. 统一错误处理

```php
// ✅ 自定义异常类
class DatabaseException extends Exception
{
    protected $sql;
    protected $bindings;
    
    public function __construct($message, $sql = '', $bindings = [], $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->sql = $sql;
        $this->bindings = $bindings;
    }
    
    public function getSql(): string
    {
        return $this->sql;
    }
    
    public function getBindings(): array
    {
        return $this->bindings;
    }
}

class QueryException extends DatabaseException {}
class ConnectionException extends DatabaseException {}

// 使用
try {
    $result = DB::table('users')->select();
} catch (QueryException $e) {
    error_log("Query failed: " . $e->getSql());
    error_log("Bindings: " . json_encode($e->getBindings()));
    throw $e;
}
```

##### 12. 清理注释代码

```php
// ❌ 删除所有注释的代码
// public static function where(){}
// public static function value(){}

// ✅ 保留必要的文档注释
/**
 * 添加 WHERE 条件
 *
 * @param string|array|null $field 字段名或条件数组
 * @param mixed $operator 操作符或值
 * @param mixed $value 值
 * @return self
 */
public function where($field = null, $operator = null, $value = null): self
{
    // ...
}
```

---

## 四、性能基准测试建议

```php
// 创建性能测试脚本
class DatabaseBenchmark
{
    public function testQueryPerformance()
    {
        $start = microtime(true);
        
        // 测试 1000 次查询
        for ($i = 0; $i < 1000; $i++) {
            DB::table('users')->where('id', $i)->get();
        }
        
        $time = microtime(true) - $start;
        echo "1000 queries: {$time}s\n";
    }
    
    public function testBatchInsert()
    {
        $data = [];
        for ($i = 0; $i < 1000; $i++) {
            $data[] = ['name' => "User $i", 'email' => "user{$i}@test.com"];
        }
        
        $start = microtime(true);
        
        // 批量插入
        DB::transaction(function($db) use ($data) {
            foreach ($data as $row) {
                $db->table('users')->create($row);
            }
        });
        
        $time = microtime(true) - $start;
        echo "1000 inserts: {$time}s\n";
    }
}
```

---

## 总结

### 立即修复（本周）
1. ✅ SQL 注入漏洞修复
2. ✅ 字段名验证增强
3. ✅ 连接池管理

### 短期优化（本月）
4. ✅ 分页性能优化
5. ✅ 类型转换优化
6. ✅ 预加载功能
7. ✅ 嵌套事务支持

### 长期改进（下季度）
8. ✅ 事件系统重构
9. ✅ 缓存机制改进
10. ✅ 添加类型声明
11. ✅ 统一异常处理
12. ✅ 代码清理

已完成使用示例和优化建议的整理。

## 📊 总结

### **使用示例覆盖**
- ✅ 基础配置与初始化
- ✅ CRUD 操作（增删改查）
- ✅ 复杂查询（多条件、连接、分组、聚合）
- ✅ 模型定义与使用
- ✅ 事务处理
- ✅ 高级功能（表操作、原生查询、多连接）

### **发现的主要问题**

#### 🔴 **严重问题（4个）**
1. **SQL 注入漏洞** - [orderBy()](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Builder.php:1428:4-1459:5) 方法直接拼接字符串
2. **字段名过滤不完整** - 允许非法字符，缺少关键字检查
3. **连接池管理缺失** - 连接永不释放，可能泄漏
4. **事务嵌套问题** - 未使用 SAVEPOINT，嵌套事务不安全

#### ⚠️ **性能问题（4个）**
5. **N+1 查询** - 缺少预加载机制
6. **查询缓存不完善** - 全局静态变量，无过期机制
7. **分页性能差** - 每次 COUNT(*)，深度分页使用 OFFSET
8. **类型转换低效** - 递归调用，无缓存

#### 🟡 **设计问题（4个）**
9. **预处理语句未复用** - 每次都重新 prepare
10. **事件系统过于复杂** - 多层嵌套，难以维护
11. **Model 职责不清** - 反射调用，性能差
12. **错误处理不统一** - 异常类型混乱

#### 🟢 **代码质量（4个）**
13. **大量注释代码** - 影响可读性
14. **魔术方法滥用** - IDE 无法补全，调试困难
15. **缺少类型声明** - 无法利用 PHP 类型系统
16. **命名不规范** - 单复数混用，缩写过度

### **优化优先级**

**P0（本周）**: SQL 注入修复、字段验证、连接池管理  
**P1（本月）**: 分页优化、类型转换、预加载、嵌套事务  
**P2（下月）**: 事件系统重构、缓存改进  
**P3（长期）**: 类型声明、异常统一、代码清理

所有问题都提供了详细的修复代码示例，可直接参考实施。