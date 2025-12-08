<?php

require_once __DIR__ . '/database/autoload.php';

function runQuery($sql, $prefix, $DROP_TABLE_IF_EXISTS = false)
{
    if (empty($sql)) {
        return;
    }
    
    // 标准化换行符
    $sql = str_replace(["\r\n", "\r"], "\n", $sql);
    
    // 按分号分割 SQL 语句，但保留分号后可能的换行符
    $sql_statements = preg_split('/;\s*(?=\n|$)/', trim($sql), -1, PREG_SPLIT_NO_EMPTY);
    
    $processed_queries = [];
    
    foreach ($sql_statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) {
            continue;
        }
        
        // 处理多行语句，移除注释行
        $lines = explode("\n", $statement);
        $clean_lines = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            // 跳过空行和注释行
            if (empty($line) || strpos($line, '--') === 0 || strpos($line, '#') === 0 || strpos($line, '/*') === 0) {
                continue;
            }
            $clean_lines[] = $line;
        }
        
        if (!empty($clean_lines)) {
            $processed_queries[] = implode(' ', $clean_lines);
        }
    }
    
    // 执行处理后的 SQL 语句
    foreach ($processed_queries as $query) {
        $query = trim($query);
        if (empty($query)) {
            continue;
        }
        
        // 替换表前缀
        $query = preg_replace([
            '/CREATE\s+TABLE\s+`icms_/i',
            '/INSERT\s+INTO\s+`icms_/i',
            '/ALTER\s+TABLE\s+`icms_/i',
            '/DROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?`icms_/i',
            '/RENAME\s+TABLE\s+`icms_/i',
            '/UPDATE\s+`icms_/i',
            '/DELETE\s+FROM\s+`icms_/i',
            '/TRUNCATE\s+TABLE\s+`icms_/i'
        ], [
            sprintf('CREATE TABLE `%s', $prefix),
            sprintf('INSERT INTO `%s', $prefix),
            sprintf('ALTER TABLE `%s', $prefix),
            sprintf('DROP TABLE IF EXISTS `%s', $prefix),
            sprintf('RENAME TABLE `%s', $prefix),
            sprintf('UPDATE `%s', $prefix),
            sprintf('DELETE FROM `%s', $prefix),
            sprintf('TRUNCATE TABLE `%s', $prefix)
        ], $query);
        
        // 替换其他表名引用
        $query = str_replace('`icms_', '`' . $prefix, $query);
        
        // 处理 CREATE TABLE 的 DROP TABLE IF EXISTS 选项
        if (strripos($query, 'CREATE TABLE') !== false && $DROP_TABLE_IF_EXISTS) {
            if (preg_match('/CREATE\s+TABLE\s+`([^`]+)`/i', $query, $match)) {
                $table_name = $match[1];
                $DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `' . $table_name . '`;';
                try {
                    DB::query($DROP_TABLE_SQL);
                } catch (Exception $e) {
                    // 忽略删除表的错误，表可能不存在
                }
            }
        }
        
        // 执行 SQL 语句
        try {
            DB::query($query);
        } catch (Exception $e) {
            // 可以选择记录错误或抛出异常
            error_log("SQL Error: " . $e->getMessage() . " Query: " . $query);
            throw $e;
        }
    }
}

function convertMySQLToSQLite($mysqlQuery)
{
    // 标准化输入，移除多余的空白字符
    $mysqlQuery = trim(preg_replace('/\s+/', ' ', $mysqlQuery));
    
    // 更精确的正则表达式匹配 MySQL 的 CREATE TABLE 语句
    if (!preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`([^`]+)`\s*\((.*)\)\s*(?:ENGINE\s*=\s*\w+|DEFAULT\s+CHARSET\s*=\s*\w+|AUTO_INCREMENT\s*=\s*\d+|COMMENT\s*=\s*\'[^\']*\'|\s)*;?$/is', $mysqlQuery, $matches)) {
        return false;
    }

    $tableName = $matches[1];
    $columnsPart = $matches[2];

    // 更智能的列分割，处理嵌套的括号和逗号
    $columns = parseColumns($columnsPart);
    $sqliteColumns = [];
    $primaryKeys = [];
    $indexes = [];
    $uniqueIndexes = [];
    $foreignKeys = [];

    foreach ($columns as $column) {
        $column = trim($column);
        
        // 跳过空列
        if (empty($column)) {
            continue;
        }

        // 处理主键约束
        if (preg_match('/PRIMARY\s+KEY\s*\((.+)\)/i', $column, $pkMatches)) {
            $keyColumns = array_map('trim', explode(',', str_replace('`', '', $pkMatches[1])));
            $primaryKeys = array_merge($primaryKeys, $keyColumns);
            continue;
        }

        // 处理唯一索引
        if (preg_match('/UNIQUE\s+KEY\s+`([^`]+)`\s*\((.+)\)/i', $column, $uniqueMatches)) {
            $indexName = $uniqueMatches[1];
            $indexColumns = str_replace('`', '', $uniqueMatches[2]);
            $uniqueIndexes[] = ['name' => $indexName, 'columns' => $indexColumns];
            continue;
        }

        // 处理普通索引
        if (preg_match('/(?:KEY|INDEX)\s+`([^`]+)`\s*\((.+)\)/i', $column, $indexMatches)) {
            $indexName = $indexMatches[1];
            $indexColumns = str_replace('`', '', $indexMatches[2]);
            $indexes[] = ['name' => $indexName, 'columns' => $indexColumns];
            continue;
        }

        // 处理外键约束
        if (preg_match('/FOREIGN\s+KEY\s*\((.+)\)\s+REFERENCES\s+`([^`]+)`\s*\((.+)\)/i', $column, $fkMatches)) {
            $foreignKeys[] = [
                'columns' => str_replace('`', '', $fkMatches[1]),
                'ref_table' => $fkMatches[2],
                'ref_columns' => str_replace('`', '', $fkMatches[3])
            ];
            continue;
        }

        // 处理列定义
        if (preg_match('/`([^`]+)`\s+([^\s]+(?:\([^\)]*\))?)(.*)/i', $column, $colMatches)) {
            $colName = $colMatches[1];
            $colType = strtolower(trim($colMatches[2]));
            $colExtra = trim($colMatches[3]);

            // 转换数据类型
            $sqliteType = convertDataType($colType);
            
            // 处理自增
            $isAutoIncrement = stripos($colExtra, 'AUTO_INCREMENT') !== false;
            if ($isAutoIncrement) {
                $sqliteType = 'INTEGER PRIMARY KEY AUTOINCREMENT';
                $colExtra = preg_replace('/\bAUTO_INCREMENT\b/i', '', $colExtra);
                $primaryKeys[] = $colName;
            }

            // 移除 MySQL 特有的修饰符
            $colExtra = preg_replace('/\b(?:UNSIGNED|ZEROFILL|BINARY)\b/i', '', $colExtra);
            $colExtra = preg_replace('/\bCOLLATE\s+\w+/i', '', $colExtra);
            $colExtra = preg_replace('/\bCOMMENT\s+\'[^\']*\'/i', '', $colExtra);
            $colExtra = preg_replace('/\bCOLUMN_FORMAT\s+\w+/i', '', $colExtra);
            $colExtra = preg_replace('/\bSTORAGE\s+\w+/i', '', $colExtra);
            
            // 处理 NOT NULL 约束
            $notNull = '';
            if (stripos($colExtra, 'NOT NULL') !== false) {
                $notNull = 'NOT NULL';
                $colExtra = preg_replace('/\bNOT\s+NULL\b/i', '', $colExtra);
            }
            
            // 处理默认值
            $defaultValue = '';
            if (preg_match('/DEFAULT\s+(\'[^\']*\'|"[^"]*"|\w+|NULL)/i', $colExtra, $defaultMatches)) {
                $defaultValue = 'DEFAULT ' . $defaultMatches[1];
                $colExtra = preg_replace('/\bDEFAULT\s+(?:\'[^\']*\'|"[^"]*"|\w+|NULL)\b/i', '', $colExtra);
            }
            
            // 处理 UNIQUE 约束
            $unique = '';
            if (stripos($colExtra, 'UNIQUE') !== false) {
                $unique = 'UNIQUE';
                $colExtra = preg_replace('/\bUNIQUE\b/i', '', $colExtra);
            }

            // 清理多余空格
            $colExtra = trim(preg_replace('/\s+/', ' ', $colExtra));

            // 构建列定义
            $columnDef = "`$colName` $sqliteType";
            if ($notNull) $columnDef .= " $notNull";
            if ($defaultValue) $columnDef .= " $defaultValue";
            if ($unique) $columnDef .= " $unique";
            
            $sqliteColumns[] = $columnDef;
        }
    }

    // 构建 SQLite 的 CREATE TABLE 语句
    $sqliteQuery = "CREATE TABLE `$tableName` (\n  " . implode(",\n  ", $sqliteColumns);
    
    // 添加复合主键（如果存在且没有自增主键）
    if (!empty($primaryKeys) && !strpos($sqliteQuery, 'PRIMARY KEY AUTOINCREMENT')) {
        $pkColumns = array_unique($primaryKeys);
        if (count($pkColumns) > 1 || !strpos($sqliteQuery, 'PRIMARY KEY')) {
            $sqliteQuery .= ",\n  PRIMARY KEY (" . implode(', ', array_map(function($col) { return "`$col`"; }, $pkColumns)) . ")";
        }
    }
    
    $sqliteQuery .= "\n);";

    // 构建索引语句
    foreach ($indexes as $index) {
        $sqliteQuery .= "\nCREATE INDEX `{$index['name']}` ON `$tableName` ({$index['columns']});";
    }
    
    // 构建唯一索引语句
    foreach ($uniqueIndexes as $index) {
        $sqliteQuery .= "\nCREATE UNIQUE INDEX `{$index['name']}` ON `$tableName` ({$index['columns']});";
    }

    return $sqliteQuery;
}

/**
 * 解析列定义，正确处理嵌套的括号和逗号
 */
function parseColumns($columnsPart)
{
    $columns = [];
    $current = '';
    $depth = 0;
    $inQuotes = false;
    $quoteChar = '';
    
    for ($i = 0; $i < strlen($columnsPart); $i++) {
        $char = $columnsPart[$i];
        
        if (!$inQuotes && ($char === '\'' || $char === '"')) {
            $inQuotes = true;
            $quoteChar = $char;
        } elseif ($inQuotes && $char === $quoteChar) {
            $inQuotes = false;
            $quoteChar = '';
        } elseif (!$inQuotes) {
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            } elseif ($char === ',' && $depth === 0) {
                $columns[] = trim($current);
                $current = '';
                continue;
            }
        }
        
        $current .= $char;
    }
    
    if (!empty(trim($current))) {
        $columns[] = trim($current);
    }
    
    return $columns;
}

/**
 * 转换 MySQL 数据类型到 SQLite 数据类型
 */
function convertDataType($mysqlType)
{
    // 移除括号中的参数
    $baseType = preg_replace('/\([^\)]*\)/', '', strtolower($mysqlType));
    
    switch ($baseType) {
        // 整数类型
        case 'tinyint':
        case 'smallint':
        case 'mediumint':
        case 'int':
        case 'integer':
        case 'bigint':
        case 'bit':
            return 'INTEGER';
            
        // 浮点数类型
        case 'float':
        case 'double':
        case 'decimal':
        case 'numeric':
        case 'real':
            return 'REAL';
            
        // 文本类型
        case 'char':
        case 'varchar':
        case 'tinytext':
        case 'text':
        case 'mediumtext':
        case 'longtext':
        case 'enum':
        case 'set':
        case 'json':
            return 'TEXT';
            
        // 二进制类型
        case 'binary':
        case 'varbinary':
        case 'tinyblob':
        case 'blob':
        case 'mediumblob':
        case 'longblob':
            return 'BLOB';
            
        // 日期时间类型
        case 'date':
        case 'time':
        case 'datetime':
        case 'timestamp':
        case 'year':
            return 'TEXT';
            
        // 几何类型
        case 'geometry':
        case 'point':
        case 'linestring':
        case 'polygon':
        case 'multipoint':
        case 'multilinestring':
        case 'multipolygon':
        case 'geometrycollection':
            return 'TEXT';
            
        default:
            return 'TEXT';
    }
}
/**
 * 表事件
 */
class DBEvent
{
    // public static function geted($response, $Builder, $event, $isMulti)
    // public static function created($response, $Builder, $event, $isMulti)
    // public static function updated($response, $Builder, $event, $isMulti)
    //删除前
    // public static function delete($response, $Builder, $event, $isMulti)
    //删除后
    // public static function deleted($response, $Builder, $event, $isMulti)
}
