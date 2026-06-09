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
    ->page(1, 20); // 第1页，每页20条

// 高级分页（带总数统计）
$users = DB::table('users')
    ->paging(20, 1); // 每页20条，第1页
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

