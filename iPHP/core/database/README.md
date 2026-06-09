I'll analyze the database operation classes for you. Let me start by examining these files.

Now let me examine the database directory structure to see what additional components are available.

Let me read the key database classes to complete the analysis.

Let me read the Connection class to complete my analysis.

# iCMS8 数据库操作类文档

## 概述

iCMS8 的数据库操作层基于 Laravel 框架的数据库类进行了重写和优化，提供了完整的数据库抽象层，支持 MySQL、PostgreSQL、SQLite 和 SQL Server 多种数据库。

## 核心架构

### 1. 架构层次

```
DB (静态门面)
    ↓
Manager (连接管理器)
    ↓
Connection (数据库连接)
    ↓
Builder (查询构造器)
    ↓
Model (模型层)
```

---

## 核心类详解

### **DB 类** (`@d:\src\cms\iCMS8\iPHP\core\database\src\DB.php`)

**职责**: 数据库操作的静态门面，提供全局访问入口

#### 主要功能

- **连接管理**
  - [connection($name = null)](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/DB.php:50:4-74:5): 获取数据库连接实例
  - [setDefaultConnection($name)](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/DB.php:81:4-85:5): 设置默认连接
  - [purgeConnection($name = null)](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/DB.php:110:4-124:5): 清除连接缓存
  
- **调试功能**
  - [debug($flag = 1)](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/DB.php:126:4-133:5): 启用查询日志记录
    - `$flag = 1`: 记录 SQL 日志
    - `$flag = 2`: 记录 SQL + 调用堆栈
    - `$flag = 3`: 记录 SQL + 堆栈 + EXPLAIN

- **错误处理**
  - [getError($ex, $code = 0)](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/DB.php:141:4-174:5): 获取格式化的错误信息
  - [errorText($code)](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/DB.php:135:4-139:5): 获取错误代码说明

#### 使用示例

```php
// 获取默认连接
$connection = DB::connection();

// 获取指定连接
$connection = DB::connection('mysql2');

// 启用调试
DB::debug(2);

// 直接调用查询方法（通过魔术方法转发）
DB::table('users')->where('id', 1)->get();
```

---

### **Manager 类** (`@d:\src\cms\iCMS8\iPHP\core\database\src\Manager.php`)

**职责**: 数据库连接管理器，负责创建和维护连接实例

#### 核心特性

- **单例模式**: 通过 [getInstance()](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/Model.php:230:4-238:5) 获取唯一实例
- **连接池**: 缓存已创建的连接，避免重复创建
- **读写分离**: 支持 `::read` 和 `::write` 连接后缀
- **连接工厂**: 使用 `ConnectionFactory` 创建不同类型的连接

#### 主要方法

- [connect($name = null)](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Manager.php:68:4-91:5): 创建或获取连接
- [disconnect($name = null)](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Manager.php:230:4-241:5): 断开连接
- [reconnect($name = null)](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Manager.php:243:4-258:5): 重新连接
- [setConfig($config)](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Manager.php:329:4-332:5): 设置数据库配置
- [supportedDrivers()](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Manager.php:333:4-341:5): 返回支持的驱动 `['mysql', 'pgsql', 'sqlite', 'sqlsrv']`

---

### **Connection 类** (`@d:\src\cms\iCMS8\iPHP\core\database\src\Connection\Connection.php`)

**职责**: 数据库连接的抽象基类，封装 PDO 操作

#### 核心属性

- `$pdo`: 主 PDO 连接（用于写操作）
- `$readPdo`: 读 PDO 连接（用于读操作）
- `$tablePrefix`: 表前缀
- `$fetchMode`: 默认 `PDO::FETCH_ASSOC`（关联数组）
- `$queryLog`: 查询日志
- `$transactions`: 事务计数器

#### 主要方法

**查询执行**
- [select($query, $params = [])](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Builder.php:470:4-478:5): 执行 SELECT 查询，返回多行
- [row($query, $params = [])](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Builder.php:480:4-512:5): 执行查询，返回单行
- [insert($query, $params = [])](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Builder.php:596:4-599:5): 执行 INSERT，返回插入 ID
- [update($query, $params = [])](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Builder.php:569:4-585:5): 执行 UPDATE，返回影响行数
- [delete($query, $params = [])](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Builder.php:620:4-635:5): 执行 DELETE，返回影响行数
- [statement($query, $params = [])](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Connection/Connection.php:255:4-261:5): 执行通用语句

**事务管理**
- [transaction(Closure $callback, $attempts = 1)](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Connection/Connection.php:281:4-320:5): 事务闭包
- [beginTransaction()](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Connection/Connection.php:322:4-328:5): 开始事务
- [commit()](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Connection/Connection.php:338:4-344:5): 提交事务
- [rollBack()](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Connection/Connection.php:330:4-336:5): 回滚事务

**表操作**
- [table($table)](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/Model.php:334:4-347:5): 创建 Builder 实例
- [hasTable($table)](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Connection/Connection.php:384:4-392:5): 检查表是否存在
- [copy($source, $target)](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Builder.php:669:4-674:5): 复制表结构
- [rename($source, $target)](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/database/src/Builder.php:676:4-681:5): 重命名表

---

### **Builder 类** (`@d:\src\cms\iCMS8\iPHP\core\database\src\Builder.php`)

**职责**: 查询构造器，提供流畅的链式查询接口

#### 核心属性

- `$table`: 表名
- `$fields`: 查询字段（默认 `*`）
- `$primaryKey`: 主键（默认 `id`）
- `$query`: 查询条件数组
- `$params`: 预处理参数
- `$response`: 查询结果
- `$casts`: 类型转换规则
- `$events`: 事件处理器

#### 查询方法

**数据检索**
```php
// 获取多行（返回对象）
$users = DB::table('users')->all();

// 获取多行（返回数组）
$users = DB::table('users')->select();

// 获取单行（返回对象）
$user = DB::table('users')->row(1);

// 获取单行（返回数组）
$user = DB::table('users')->get(1);

// 获取第一行
$user = DB::table('users')->first();

// 获取单个值
$name = DB::table('users')->where('id', 1)->value('name');

// 获取列数组
$ids = DB::table('users')->pluck('id');
$map = DB::table('users')->pluck('name', 'id'); // 键值对
```

**条件查询**
```php
// 基础 WHERE
->where('id', 1)
->where('age', '>', 18)
->where('name', 'like', '%John%')

// 数组条件
->where(['id' => 1, 'status' => 'active'])
->where([['age', '>', 18], ['status', '=', 'active']])

// IN 查询
->where('id', [1, 2, 3])
->where('status', 'in', ['active', 'pending'])

// BETWEEN
->where('age', 'between', [18, 30])

// OR 条件
->orWhere('status', 'admin')

// 原生 SQL
->where('DATE(created_at) = CURDATE()')
->where('id = ? AND status = ?', [1, 'active'])

// NULL 清空条件
->where(null)
```

**字段选择**
```php
// 指定字段
->field('id', 'name', 'email')
->field('id,name,email')
->field(['id', 'name', 'email'])

// 所有字段
->field(true)

// 排除字段
->withoutField('password', 'token')
```

**排序与分组**
```php
// 排序
->orderBy('id', 'DESC')
->orderBy('id') // 默认 DESC
->orderBy(['id' => 'ASC', 'created_at' => 'DESC'])
->orderBy('id ASC, name DESC')

// 按数组排序
->orderBy('id', [5, 3, 1, 2, 4])

// 分组
->groupBy('category')
->groupBy('category', 'ASC')
```

**限制与分页**
```php
// LIMIT
->limit(10)
->limit(10, 20) // OFFSET 10, LIMIT 20

// 分页
->page($page, $pageSize)
->paging($pageSize, $page)
```

**数据操作**

**插入**
```php
// 单行插入
$id = DB::table('users')->create([
    'name' => 'John',
    'email' => 'john@example.com'
]);

// 别名
$id = DB::table('users')->insert($data);

// 忽略重复
$id = DB::table('users')->ignore($data);

// 替换
$id = DB::table('users')->replace($data);
```

**更新**
```php
// 更新
$rows = DB::table('users')->update(['name' => 'Jane'], 1);
$rows = DB::table('users')->where('id', 1)->update(['name' => 'Jane']);

// 字段运算
->update(['views' => ['+', 1]]) // views = views + 1
->update(['score' => ['score', '+', 10]]) // score = score + 10

// 更新或创建
->updateOrCreate($data, $where);
```

**删除**
```php
// 删除
$rows = DB::table('users')->delete(1);
$rows = DB::table('users')->where('status', 'inactive')->delete();

// 清空表
DB::table('users')->truncate();

// 删除表
DB::table('users')->drop();
```

**聚合函数**
```php
->count()
->sum('amount')
->avg('score')
->max('price')
->min('price')
```

**连接查询**
```php
->join('orders')
->leftJoin('orders')
->rightJoin('orders')
->on('users.id', 'orders.user_id')
```

#### 事件系统

Builder 支持丰富的事件钩子：

```php
// 事件类型
- geted: 查询后
- creating: 创建前
- created: 创建后
- updating: 更新前
- updated: 更新后
- delete: 删除前
- deleted: 删除后
- changed: 创建或更新后
- event: 所有事件

// 字段级事件
- fieldGeted: 字段查询后
- fieldUpdated: 字段更新后
- fieldCreated: 字段创建后
- fieldDeleted: 字段删除后
- fieldChanged: 字段变更后
- fieldEvent: 字段所有事件
```

#### 类型转换 (Casts)

```php
protected $casts = [
    'is_active' => 'boolean',      // 布尔值
    'created_at' => 'datetime:Y-m-d H:i:s', // 日期时间
    'settings' => 'array',         // JSON 转数组
    'tags' => 'strlist',           // 逗号分隔字符串转数组
    'content' => 'html',           // HTML 解码
    'data' => 'json',              // 数组转 JSON
    'field' => 'as:newField',      // 字段重命名
    'temp' => 'delete',            // 删除字段
    'custom' => [Class, 'method'], // 自定义处理
];
```

---

### **Model 类** (`@d:\src\cms\iCMS8\iPHP\core\Model.php`)

**职责**: 模型基类，提供 ORM 功能

#### 核心属性

```php
protected $connection;    // 连接名称
protected $table;         // 表名
protected $primaryKey;    // 主键（默认 id）
protected $fields;        // 查询字段
protected $casts;         // 类型转换
protected $attributes;    // 默认属性值
protected $events;        // 事件映射
protected $sharding;      // 分表号
protected $queryLog;      // 记录查询日志
protected $queryTrace;    // 追踪查询
```

#### 主要方法

**静态方法（通过魔术方法转发到 Builder）**
```php
// 所有 Builder 方法都可以静态调用
UserModel::where('id', 1)->get();
UserModel::create($data);
UserModel::update($data, $where);
```

**数据处理**
```php
// 获取表单数据
UserModel::getData();      // 从 GET 获取
UserModel::postData('rs'); // 从 POST 获取

// 过滤数据
UserModel::filter($post, $args);

// 检查重复
UserModel::check($where, $primaryValue);

// 类型转换
UserModel::castData($data, $types);

// 构造数据
UserModel::makeData($data, $fields);
```

**表名生成**
```php
// UserModel -> user
// UserInfoModel -> user_info
// UserInfoModel (sharding=1) -> user_info_p1
Model::table('UserModel');
Model::table('UserInfoModel', 1);
```

**实例管理**
```php
// 获取单例
$instance = UserModel::getInstance();

// 重置实例
UserModel::reset();

// 设置表名
UserModel::setTable('custom_table');
```

---

## 辅助功能

### **数据库工具函数** (`@d:\src\cms\iCMS8\iPHP\core\Database.php`)

#### [runQuery($sql, $prefix, $DROP_TABLE_IF_EXISTS = false)](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/Database.php:4:0-95:1)

执行 SQL 语句，自动处理表前缀替换

**功能**:
- 解析多条 SQL 语句
- 移除注释行
- 替换表前缀 `icms_` 为自定义前缀
- 可选择是否先删除已存在的表

**支持的 SQL 操作**:
- CREATE TABLE
- INSERT INTO
- ALTER TABLE
- DROP TABLE
- RENAME TABLE
- UPDATE
- DELETE
- TRUNCATE

#### [convertMySQLToSQLite($mysqlQuery)](cci:1://file:///d:/src/cms/iCMS8/iPHP/core/Database.php:97:0-241:1)

将 MySQL 的 CREATE TABLE 语句转换为 SQLite 兼容语法

**转换内容**:
- 数据类型映射
- 移除 MySQL 特有修饰符（UNSIGNED、ZEROFILL、BINARY）
- 处理自增主键
- 转换索引和唯一索引
- 处理外键约束

**数据类型映射**:
```
整数: TINYINT/SMALLINT/INT/BIGINT → INTEGER
浮点: FLOAT/DOUBLE/DECIMAL → REAL
文本: VARCHAR/TEXT/JSON → TEXT
二进制: BLOB/BINARY → BLOB
日期: DATE/DATETIME/TIMESTAMP → TEXT
```

#### `DBEvent 类`

表事件基类，可继承实现自定义事件处理器

```php
class CustomEvent extends DBEvent {
    public static function geted($response, $Builder, $event, $isMulti) {
        // 查询后处理
    }
    
    public static function created($response, $Builder, $event, $isMulti) {
        // 创建后处理
    }
    
    public static function updated($response, $Builder, $event, $isMulti) {
        // 更新后处理
    }
    
    public static function delete($response, $Builder, $event, $isMulti) {
        // 删除前处理
    }
    
    public static function deleted($response, $Builder, $event, $isMulti) {
        // 删除后处理
    }
}
```

---

## 数据库驱动

### 支持的数据库

- **MySQL** (`MySqlConnection`, `MySqlConnector`)
- **PostgreSQL** (`PostgresConnection`, `PostgresConnector`)
- **SQLite** (`SQLiteConnection`, `SQLiteConnector`)
- **SQL Server** (`SqlServerConnection`, `SqlServerConnector`)

### 连接配置示例

```php
$config = [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'icms',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'prefix' => 'icms_',
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => '/path/to/database.sqlite',
            'prefix' => '',
        ],
    ],
];

DB::config($config);
```

---

## 最佳实践

### 1. 使用模型类

```php
class UserModel extends Model {
    protected $table = 'users';
    protected $primaryKey = 'uid';
    protected $fields = 'uid,username,email,created_at';
    
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];
    
    protected $events = [
        'creating' => [__CLASS__, 'beforeCreate'],
        'created' => [__CLASS__, 'afterCreate'],
    ];
    
    public static function beforeCreate($data, $builder) {
        // 创建前处理
    }
}

// 使用
$user = UserModel::where('uid', 1)->get();
$id = UserModel::create($data);
```

### 2. 事务处理

```php
DB::transaction(function($db) {
    $db->table('users')->create($userData);
    $db->table('profiles')->create($profileData);
});

// 或手动控制
DB::beginTransaction();
try {
    DB::table('users')->create($userData);
    DB::table('profiles')->create($profileData);
    DB::commit();
} catch (Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### 3. 查询优化

```php
// 只查询需要的字段
->field('id', 'name')

// 使用索引字段作为条件
->where('indexed_field', $value)

// 分页查询
->page($page, $pageSize)

// 缓存查询结果
Builder::$CACHE['ALL'] = [];
Builder::$CACHE['ROW'] = [];
```

### 4. 安全性

```php
// 使用预处理参数，防止 SQL 注入
->where('id = ? AND status = ?', [1, 'active'])

// 字段名过滤
// Builder 自动过滤非法字符: /[^\w\d_\-,]/i

// 避免直接拼接 SQL
// ❌ ->where("id = {$_GET['id']}")
// ✅ ->where('id', $_GET['id'])
```

---

## 总结

iCMS8 数据库操作层提供了：

✅ **完整的数据库抽象** - 支持多种数据库驱动  
✅ **流畅的查询接口** - 链式调用，易于使用  
✅ **强大的事件系统** - 灵活的钩子机制  
✅ **类型转换支持** - 自动处理数据类型  
✅ **事务管理** - 完善的事务支持  
✅ **查询日志** - 便于调试和性能分析  
✅ **安全防护** - 预处理语句防止注入  
✅ **ORM 支持** - Model 层简化开发

已完成 iCMS8 数据库操作类的分析和文档总结。

## 分析成果

我已经深入分析了以下核心组件：

### **核心架构**
- **DB 类** - 静态门面，提供全局数据库访问入口
- **Manager 类** - 连接管理器，单例模式管理多个数据库连接
- **Connection 类** - PDO 连接抽象层，封装底层数据库操作
- **Builder 类** - 查询构造器，提供流畅的链式查询接口（2023行代码）
- **Model 类** - ORM 模型基类，简化业务逻辑开发

### **关键特性**

1. **多数据库支持** - MySQL、PostgreSQL、SQLite、SQL Server
2. **查询构造器** - 支持复杂的 WHERE、JOIN、聚合、分页等操作
3. **事件系统** - 10+ 种事件钩子（geted、created、updated、deleted等）
4. **类型转换** - 自动转换 boolean、datetime、array、json 等类型
5. **事务管理** - 完整的事务支持（transaction、beginTransaction、commit、rollBack）
6. **安全防护** - 预处理语句防止 SQL 注入
7. **调试工具** - 查询日志、堆栈追踪、EXPLAIN 分析
8. **辅助工具** - MySQL 转 SQLite、SQL 批量执行、表前缀替换

### **设计模式**

- 单例模式（Manager）
- 工厂模式（ConnectionFactory）
- 门面模式（DB）
- 构造器模式（Builder）
- 迭代器模式（Builder implements Iterator）

文档已包含详细的 API 说明、使用示例和最佳实践建议。