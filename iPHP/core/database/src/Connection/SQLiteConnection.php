<?php

// namespace DataBase\Connection;

// use DataBase\Connection\Connection;

class SQLiteConnection extends Connection
{
    public function ident()
    {
        return '"';
    }

    public function fullFields($table)
    {
        $query = sprintf('PRAGMA table_info(%s)', $table);
        $result = $this->select($query);

        $result = array_map(function ($item) {
            $data = [];
            $data['Key'] = $item['pk'] ? 'PRI' : '';
            $data['Extra'] = $item['pk'] ? 'auto_increment' : '';
            $data['Field'] = $item['name'];
            $data['Type'] = $item['type'];
            $data['Null'] = $item['notnull'] ? 'NO' : 'YES';
            $data['Default'] = $item['notnull'] ? '' : $item['dflt_value'];
            return $data;
        }, $result);
        return $result;
    }

    public function ddl($table, $flag = false)
    {
        $table = $this->getTableName($table);
        $query = sprintf("SELECT * FROM sqlite_master WHERE name='%s'", $table);
        $result = $this->row($query);
        return $flag ? $result : $result['sql'];
    }
    public function copy($source, $target)
    {
        $target = $this->getTableName($target);
        $source = $this->getTableName($source);
        $sql = sprintf('CREATE TABLE IF NOT EXISTS %s AS SELECT * FROM %s WHERE 0 ', $target, $source);
        return $this->statement($sql);
    }

    public function tables()
    {
        $result = $this->select("
            SELECT name FROM sqlite_master WHERE type='table';
        ");
        return array_column($result, 'name');
    }

    public function status($name = '', $fast = false)
    {
        $tables = $this->tables();
        $name = $this->getTableName($name);
        if ($name) {
            $key = array_search($name, $tables);
            $tables = [$tables[$key]];
        }
        $result = [];
        foreach ($tables as $key => $table) {
            $row = $this->row("SELECT count(*) as row_count FROM {$table}");
            $result[$table] = [
                'Data_length' => $row['Data_length'] ?: 0,
                'Index_length'=> $row['Index_length'] ?: 0,
                'Rows'        => $row['row_count'],
                'Name'        => $table,
            ];
        }

        if ($name) {
            return $result[$name];
        }
        return $result;
    }

    /**
     * Create a new database connection instance.
     *
     * @param  \PDO|\Closure  $pdo
     * @param  string  $database
     * @param  string  $tablePrefix
     * @param  array  $config
     * @return void
     */
    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        parent::__construct($pdo, $database, $tablePrefix, $config);

        $enableForeignKeyConstraints = $this->getForeignKeyConstraintsConfigurationValue();

        if ($enableForeignKeyConstraints === null) {
            return;
        }

        $enableForeignKeyConstraints
            ? $this->enableForeignKeyConstraints()
            : $this->disableForeignKeyConstraints();
    }

    /**
     * Enable foreign key constraints.
     *
     * @return bool
     */
    protected function enableForeignKeyConstraints()
    {
        return $this->connection->statement('PRAGMA foreign_keys = ON;');
    }

    /**
     * Disable foreign key constraints.
     *
     * @return bool
     */
    public function disableForeignKeyConstraints()
    {
        return $this->connection->statement('PRAGMA foreign_keys = OFF;');
    }

    /**
     * Get the database connection foreign key constraints configuration option.
     *
     * @return bool|null
     */
    protected function getForeignKeyConstraintsConfigurationValue()
    {
        return $this->getConfig('foreign_key_constraints');
    }
}
