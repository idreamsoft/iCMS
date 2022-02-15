<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
defined('iPHP') or exit('What are you doing?');

class AppsTable
{
    const DATA_TABLE_NAME    = '%s_cdata';
    const DATA_UNION_KEY     = '%s_id';
    const DATA_PRIMARY_KEY   = 'id';
    const MASTER_PRIMARY_KEY = 'id';

    public static function getDataTableName($name)
    {
        return sprintf(self::DATA_TABLE_NAME, $name);
    }
    public static function getDataUnionKey($name)
    {
        return sprintf(self::DATA_UNION_KEY, $name);
    }
    public static function getPrimaryKey($app = null)
    {
        if ($app) {
            $masterTable = self::getMasterTable($app);
            return $masterTable['primary'];
        }
        return self::MASTER_PRIMARY_KEY;
    }
    public static function getMasterTable($app)
    {
        $appArray = Apps::get($app);
        if ($table = $appArray['table'][$app]) {
            return $table;
        }
        return null;
    }
    public static function getDataTable($appArray, $app)
    {
        $dtn = self::getDataTableName($app);
        if ($table = $appArray['table'][$dtn]) {
            return $table;
        }
        return null;
    }
    /**
     * 获取主表索引
     */
    public static function getMasterIndex($indexs = [])
    {
        $default = array(
            'index_id'         => 'KEY `id` (`status`,`id`)',
            'index_hits'       => 'KEY `hits` (`status`,`hits`)',
            'index_pubdate'    => 'KEY `pubdate` (`status`,`pubdate`)',
            'index_hits_week'  => 'KEY `hits_week` (`status`,`hits_week`)',
            'index_hits_month' => 'KEY `hits_month` (`status`,`hits_month`)',
            'index_cid_hits'   => 'KEY `cid_hits` (`status`,`cid`,`hits`)'
        );
        return array_merge($default, (array)$indexs);
    }
    /**
     * 应用附加表基础字段
     */
    public static function getDataBaseFields($name = null)
    {
        $a[self::DATA_PRIMARY_KEY] = array(
            'id'       => self::DATA_PRIMARY_KEY,
            'label'    => '附加表id',
            'comment'  => '主键 自增ID',
            'field'    => 'PRIMARY',
            'name'     => self::DATA_PRIMARY_KEY,
            'default'  => '',
            'type'     => 'PRIMARY',
            'len'      => '10',
            'unsigned' => '1',
        );
        if ($name) {
            $unionKey = self::getDataUnionKey($name);
            $a[$unionKey] = array(
                'id'       => $unionKey,
                'label'    => '关联内容ID',
                'comment'  => '内容ID 关联' . $name . '表',
                'field'    => 'INT',
                'name'     => $unionKey,
                'default'  => '',
                'type'     => 'union',
                'len'      => '10',
                'unsigned' => '1',
            );
        }

        return $a;
    }

    public static $baseFieldsKeys = null;
    public static function getBaseFieldsKeys($key = null)
    {
        if (self::$baseFieldsKeys === null) {
            $fields  = Etc::get('apps', 'default/field');
            $array1  = array_column($fields['base']['fields'], 'name');
            $array2  = array_column($fields['publish']['fields'], 'name');
            $array = array_merge($array1, $array2);
        } else {
            $array = self::$baseFieldsKeys;
        }
        if ($key) {
            return in_array($key, $array);
        }
        return $array;
    }
    /**
     * 创建xxx_data附加表
     * @param  [type] $fieldata [description]
     * @param  [type] $name     [description]
     * @return Array           [description]
     */
    public static function createDataTable($fieldata, $name, $unionKey)
    {
        $table = AppsTable::create(
            $name,
            $fieldata, //获取字段数组
            array( //索引
                'index_' . $unionKey => 'KEY `' . $unionKey . '` (`' . $unionKey . '`)'
            )
        );
        array_push($table, $unionKey, '附加');
        return array($name => $table);
    }
    public static function makeFieldSql($vars = null, $alter = null, $origin = null)
    {
        is_array($vars) or $vars = json_decode($vars, true);

        $fieldType  = $vars['field']; //字段类型
        $label   = $vars['label']; //字段名称
        $name    = $vars['name'];  //字 段 名
        $default = $vars['default']; //默 认 值
        $len     = $vars['len']; //数据长度
        $comment = $vars['comment'] ? $vars['comment'] : $label;
        $unsigned = $vars['unsigned']; //无符号

        empty($name) && $name = Pinyin::get($label);
        $fieldType = strtolower($fieldType);
        switch ($fieldType) {
            case 'varchar':
            case 'multivarchar':
                $data_type = 'VARCHAR';
                break;
            case 'tinyint':
                $len or $len = '1';
                $data_type = 'TINYINT';
                $default   = (int) $default;
                empty($default) && $default = '0';
                break;
            case 'primary':
            case 'int':
            case 'time':
                $len or $len = '10';
                $data_type = 'INT';
                $default   = (int) $default;
                empty($default) && $default = '0';
                break;
            case 'bigint':
                $len or $len = '20';
                $data_type = 'BIGINT';
                $default   = (int) $default;
                empty($default) && $default = '0';
                break;
            case 'radio':
            case 'select':
                $len or $len = '6';
                $data_type = 'SMALLINT';
                $default   = (int) $default;
                empty($default) && $default = '0';
                break;
            case 'checkbox':
            case 'multiselect':
                $len or $len = '255';
                $data_type = 'VARCHAR';
                break;
            case 'image':
            case 'file':
                $len or $len = '255';
                $data_type = 'VARCHAR';
                break;
            case 'multiimage':
            case 'multifile':
                $len or $len = '10240';
                $data_type = 'VARCHAR';
                break;
            case 'text':
                $data_type = 'TEXT';
                $len = null;
                $default = null;
                break;
            case 'mediumtext':
            case 'editor':
                $data_type = 'MEDIUMTEXT';
                $len = null;
                $default = null;
                break;
            case 'float':
            case 'double':
            case 'decimal':
                $data_type = strtoupper($fieldType);
                $default   = '0.0';
                break;
            default:
                $len or $len = '255';
                $data_type = 'VARCHAR';
                break;
        }
        $len === null or $data_len  = '(' . $len . ')';

        if (in_array($data_type, array('BIGINT', 'INT', 'MEDIUMINT', 'SMALLINT', 'TINYINT'))) {
            $unsigned && $data_len .= ' UNSIGNED';
        }

        !is_null($default) && $default_sql = " DEFAULT '$default'";

        if ($fieldType == 'primary') {
            $default_sql = 'AUTO_INCREMENT';
        }
        $name = DB::addIdent($name);
        $sql = sprintf(
            "%s %s NOT NULL %s COMMENT '%s'",
            $name,
            $data_type . $data_len,
            $default_sql,
            $comment
        );
        switch ($alter) {
            case 'ADD':
                $sql = sprintf('ADD COLUMN %s', $sql);
                break;
            case 'CHANGE':
                $sql = sprintf('CHANGE %s %s', DB::addIdent($origin), $sql);
                break;
            case 'DROP':
                $sql = sprintf('DROP COLUMN %s', $name);
                break;
        }

        return $sql;
    }
    /**
     * [makeAlterSql description]
     * @param  Array $newFields     [新字段]
     * @param  Array $oldFields     [旧字段]
     * @param  [type] $field_origin [description]
     * @return [type]               [description]
     */
    public static function makeAlterSql($newFields, $oldFields, $field_origin)
    {
        $newFields = is_array($newFields) ? array_map('json_encode', $newFields) : array();
        $oldFields = is_array($oldFields) ? array_map('json_encode', $oldFields) : array();
        $diff = array_diff_values($newFields, $oldFields);

        $result = array();
        //删除 或者更改过
        if ($diff['-']) {
            foreach ($diff['-'] as $key => $value) {
                if (isset($field_origin[$key])) {
                    //新字段名
                    $nfield = $field_origin[$key];
                    //新数据json
                    $nvalue = $newFields[$nfield];
                    if ($nvalue) {
                        $result[$key] = self::makeFieldSql($nvalue, 'CHANGE', $key);
                        //将更改的字段从新增数据里移除
                        unset($diff['+'][$nfield]);
                    }
                } else {
                    //删除字段
                    $result[$key] = self::makeFieldSql($value, 'DROP');
                }
            }
        }
        //新增
        if ($diff['+']) {
            foreach ($diff['+'] as $key => $value) {
                if (!isset($field_origin[$key])) {
                    $result[$key] = self::makeFieldSql($value, 'ADD');
                }
            }
        }
        return $result;
    }
    public static function makeTableSql($tableArray)
    {
        if ($tableArray) {
            $tableArray = AppsTable::items($tableArray);
            foreach ($tableArray as $key => $value) {
                DB::hasTable($value['table']) && $tables[] = $value['table'];
            }
            if ($tables) {
                $sql = self::makeDDL($tables, false);
                $sql = preg_replace('/\sAUTO_INCREMENT=\d+/is', '', $sql);
                $sql = str_replace('`' . DB::getTablePrefix(), '`' . iPHP_DB_PREFIX_TAG, $sql);
                return $sql;
            }
        }
        return false;
    }
    public static function makeDDL($tabledb, $exists = true)
    {
        $sql = '';
        foreach ($tabledb as $table) {
            $exists && $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $result = DB::table($table)->ddl(true);
            $ddl = str_replace($result['Table'], $table, $result['Create Table']);
            $sql .= $ddl . ";\n\n";
        }
        return $sql;
    }
    public static function query($sql)
    {
        $num = 0;
        $sql = str_replace("\r", "\n", $sql);
        $resource = array();
        $array = explode(";\n", trim($sql));
        foreach ($array as $query) {
            $queries = explode("\n", trim($query));
            foreach ($queries as $query) {
                $resource[$num] .= $query[0] == '#' ? '' : $query;
            }
            $num++;
        }
        unset($sql);
        $prefix = DB::getTablePrefix();
        // var_dump($resource);
        foreach ($resource as $key => $query) {
            $query = trim($query);
            $query = str_replace('`' . DB::getTablePrefix(), '`' . $prefix, $query);
            $query = str_replace('`' . iPHP_DB_PREFIX_TAG, '`' . $prefix, $query);
            $query && DB::query($query);
        }
    }
    public static function alter($name, $sqlArray = null)
    {
        if (empty($sqlArray)) {
            return;
        }

        $fields  = DB::table($name)->getFields('field', 'type');
        foreach ($sqlArray as $k => $sql) {
            $unset = false;
            if (strpos($sql, 'DROP COLUMN') !== false) {
                preg_match('/`(.+)`/i', $sql, $match);
                // 删除字段时 字段不存在 移除语句
                !$fields[$match[1]] && $unset = true;
            } elseif (strpos($sql, 'ADD COLUMN') !== false) {
                preg_match('/`(.+)`/i', $sql, $match);
                // 添加字段时 字段存在 移除语句
                $fields[$match[1]] && $unset = true;
            } elseif (strpos($sql, 'CHANGE ') !== false) {
                preg_match('/`(.+)`\s`(.+)`/i', $sql, $match);
                // 旧字段不存在 或者 新字段存在 移除语句
                if (!$fields[$match[1]] || $fields[$match[2]]) {
                    $unset = true;
                }
            }
            if ($unset) {
                unset($sqlArray[$k]);
            }
        }

        if (!is_array($sqlArray)) {
            return;
        }
        $prefix = DB::getTablePrefix();
        $sql = sprintf(
            "ALTER TABLE `%s`%s;",
            $prefix . $name,
            implode(',', $sqlArray)
        );
        self::query($sql);
    }
    public static function create($name, $fields = null, $indexs = null, $query = true)
    {
        $fields_sql = array();
        if (is_array($fields)) {
            foreach ($fields as $key => $arr) {
                if ($arr) {
                    $fields_sql[$arr['name']] = self::makeFieldSql($arr);
                    if ($arr['field'] == 'PRIMARY') {
                        $PRIMARY = $arr['name'];
                    }
                }
            }
        }
        $fields_sql['primary_' . $PRIMARY] = 'PRIMARY KEY (`' . $PRIMARY . '`)';
        $indexs && $fields_sql = array_merge($fields_sql, $indexs);
        $prefix = DB::getTablePrefix();
        $sql = sprintf(
            "CREATE TABLE `%s` (%s) 
            ENGINE=%s AUTO_INCREMENT=0 DEFAULT CHARSET=%s;",
            $prefix . $name,
            implode(',', $fields_sql),
            DB::getEngine(),
            DB::getCharset()
        );
        if ($query === 'sql') {
            return $sql;
        }
        $query && DB::query($sql);
        return array($name, $PRIMARY);
    }
    public static function dropTable($fieldata, &$table_array, $name)
    {
        if (empty($fieldata) && $table_array[$name] && DB::hasTable($name)) {
            self::drop($name);
            unset($table_array[$name]);
        }
    }
    public static function drop($name)
    {
        return DB::table($name)->drop();
    }
    /** Get table indexes
     * @param string
     * @param string Min_DB to use
     * @return array array($key_name => array("type" => , "columns" => array(), "lengths" => array(), "descs" => array()))
     */
    public static function getIndex($table)
    {
        $result = array();
        $indexs = DB::table($table)->show('INDEX');
        foreach ((array) $indexs as $row) {
            $result[$row["Key_name"]]["type"] = ($row["Key_name"] == "PRIMARY" ? "PRIMARY" : ($row["Index_type"] == "FULLTEXT" ? "FULLTEXT" : ($row["Non_unique"] ? "INDEX" : "UNIQUE")));
            $result[$row["Key_name"]]["columns"][] = $row["Column_name"];
            $result[$row["Key_name"]]["lengths"][] = $row["Sub_part"];
            $result[$row["Key_name"]]["descs"][] = null;
        }
        return $result;
    }
    /** Get table status
     * @param string
     * @param bool return only "Name", "Engine" and "Comment" fields
     * @return array array($name => array("Name" => , "Engine" => , "Comment" => , "Oid" => , "Rows" => , "Collation" => , "Auto_increment" => , "Data_length" => , "Index_length" => , "Data_free" => )) or only inner array with $name
     */
    public static function getStatus($name = "", $fast = false)
    {
        return DB::status($name);
    }
    public static function fullFields($name)
    {
        return DB::table($name)->fullFields();
    }
    public static function getFields($name)
    {
        $fields = DB::table($name)->getFields();
        return array_flip($fields);
    }
    public static function hasField($name, $key)
    {
        $fields = DB::table($name)->getFields();
        $fields = array_flip($fields);
        return $fields[$key] ? true : false;
    }
    public static function items($variable)
    {
        $table = array();
        is_string($variable) && $variable = json_decode($variable, true);
        if (is_array($variable)) {
            $prefix = DB::getTablePrefix();
            foreach ($variable as $key => $value) {
                if (count($value) > 3) {
                    $table[$value[0]] = array(
                        'table'   => $prefix . $value[0],
                        'name'    => $value[0],
                        'primary' => $value[1],
                        'union'   => $value[2],
                        'label'   => $value[3],
                    );
                } else {
                    $table[$value[0]] = array(
                        'table'   => $prefix . $value[0],
                        'name'    => $value[0],
                        'primary' => $value[1],
                        'label'   => $value[2],
                    );
                }
            }
        }
        return $table;
    }
}
