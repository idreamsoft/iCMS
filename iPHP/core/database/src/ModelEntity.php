<?php
/**
 * ORM 行对象
 * 用于处理单行数据的增删改查
 * 主要用于模型类的单行数据操作
 */
class ModelEntity
{
    // 当前对象的数据
    protected $attributes = [];
    protected $original = []; // 原始数据，用于判断哪些字段被修改
    protected $exists = false; // 标记是否是已存在的记录
    protected $db = null; // 标记是否是已存在的记录
    protected $primaryKey = 'id'; // 标记是否是已存在的记录

    // 构造函数
    public function __construct($data, $db)
    {
        $this->db = $db;
        $this->fill($data);
        if (!empty($data)) {
            $this->exists = true;
            $this->syncOriginal();
        }
    }

    // 填充数据
    public function fill($data)
    {
        foreach ($data as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    // 同步原始数据
    public function syncOriginal()
    {
        $this->original = $this->attributes;
        return $this;
    }

    // 魔术方法：获取属性
    public function __get($name)
    {
        return isset($this->attributes[$name]) ?$this->attributes[$name]: null;
    }

    // 魔术方法：设置属性
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    // 魔术方法：检查属性是否存在
    public function __isset($name)
    {
        return isset($this->attributes[$name]);
    }

    // 获取被修改的字段
    public function getDirty()
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    // 判断是否有修改
    public function isDirty()
    {
        return !empty($this->getDirty());
    }

    // 保存方法
    public function save()
    {
        if ($this->exists) {
            return $this->performUpdate();
        } else {
            return $this->performInsert();
        }
    }

    // 更新方法
    public function update()
    {
        if ($this->exists) {
            return $this->performUpdate();
        }
    }

    // 执行插入操作
    protected function performInsert()
    {
        $primaryKey = $this->db->getPrimaryKey();
        $primaryKeyValue = $this->db->create($this->attributes);
        // 假设插入成功，获取插入的ID
        $this->attributes[$primaryKey] = $primaryKeyValue;
        $this->exists = true;
        $this->syncOriginal();

        return $primaryKeyValue; // 返回插入结果
    }

    // 执行更新操作
    protected function performUpdate()
    {
        if (!$this->isDirty()) {
            return true; // 没有修改，直接返回
        }

        $dirty = $this->getDirty();
        $primaryKey = $this->db->getPrimaryKey();
        $primaryKeyValue = $this->attributes[$primaryKey];

        // 移除主键字段（主键不应该被更新）
        unset($dirty[$primaryKey]);

        if (empty($dirty)) {
            return true;
        }

        $this->db->where($primaryKey, $primaryKeyValue)->update($dirty);
        // 更新成功后同步原始数据
        $this->syncOriginal();
        return true; // 返回更新结果
    }

    // 删除方法
    public function delete()
    {
        if (!$this->exists) {
            return false;
        }

        $primaryKey = $this->db->getPrimaryKey();
        $primaryKeyValue = $this->attributes[$primaryKey];

        if (is_null($primaryKeyValue)) {
            return false; // 主键值为空，无法删除
        }
        $this->db->where($primaryKey, $primaryKeyValue)->delete();
        $this->exists = false;

        return true; // 返回删除结果
    }

    // 刷新数据（从数据库重新加载）
    public function refresh()
    {
        if (!$this->exists) {
            return false;
        }

        $primaryKeyValue = $this->attributes[$this->primaryKey];
        if (is_null($primaryKeyValue)) {
            return false;
        }

        // 重新查询数据
        $freshData = $this->db->where($this->primaryKey, $primaryKeyValue)->row();
        if ($freshData) {
            $this->attributes = (array) $freshData;
            $this->syncOriginal();
            return true;
        }

        return false;
    }

    // 获取所有属性
    public function toArray()
    {
        return $this->attributes;
    }

    // 获取主键值
    public function getKey()
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    // 获取主键字段名
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    // 转换为 JSON
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    // 魔术方法：转换为字符串
    public function __toString()
    {
        return $this->toJson();
    }
}
