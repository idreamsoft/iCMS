<?php

// namespace DataBase\Connection;

// use DataBase\Connection\Connection;

class MySqlConnection extends Connection
{
    public function ident()
    {
        return '`';
    }
    public function fullFields($table)
    {
        try {
            $query = sprintf('SHOW FULL FIELDS FROM %s', $table);
            return $this->select($query);
        } catch (PDOException $ex) {
            throw $ex;
        }
    }
    public function ddl($table, $flag = false)
    {
        try {
            $query = sprintf('SHOW CREATE TABLE %s', $table);
            $result = $this->row($query);
            return $flag ? $result : $result['Create Table'];
        } catch (PDOException $ex) {
            throw $ex;
        }
    }
    public function tables()
    {
        if ($this->version(0) >= 5) {
            $result = $this->select("
                SELECT TABLE_NAME, TABLE_TYPE 
                FROM `information_schema`.`TABLES` 
                WHERE TABLE_SCHEMA = DATABASE() 
                ORDER BY TABLE_NAME
            ");
            return array_column($result, 'TABLE_NAME');
        } else {
            $result = $this->select("SHOW TABLES");
            $key = 'Tables_in_' . $this->getDatabaseName();
            return array_column($result, $key);
        }
    }
    public function status($name = "", $fast = false)
    {
        if ($fast && $this->version(0) >= 5) {
            $prepare = '';
            $params = null;
            if ($name) {
                $prepare = 'AND TABLE_NAME = ?';
                $params  = [$name];
            }
            $result = $this->select(
                "SELECT TABLE_NAME AS Name, Engine, TABLE_COMMENT AS Comment 
                FROM `information_schema`.`TABLES` 
                WHERE TABLE_SCHEMA = DATABASE() {$prepare} ORDER BY Name",
                $params
            );
        } else {
            $query = $name ? " LIKE " . $this->quote(addcslashes($name, "%_\\")) : '';
            $result = $this->select("SHOW TABLE STATUS {$query}");
        }
        $return = array();
        foreach ($result as $row) {
            if ($row["Engine"] == "InnoDB") {
                // ignore internal comment, unnecessary since MySQL 5.1.21
                $row["Comment"] = preg_replace('~(?:(.+); )?InnoDB free: .*~', '\\1', $row["Comment"]);
            }
            if (!isset($row["Engine"])) {
                $row["Comment"] = "";
            }
            if ($name != "") {
                return $row;
            }
            $return[$row["Name"]] = $row;
        }
        return $return;
    }
}
