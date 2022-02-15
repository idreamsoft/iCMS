<?php
require_once __DIR__ . '/database/autoload.php';

function runQuery($sql, $prefix, $DROP_TABLE_IF_EXISTS = false)
{
    if (empty($sql)) {
        return;
    }
    $sql      = str_replace("\r", "\n", $sql);
    $resource = array();
    $num      = 0;
    $sql_array = explode(";\n", trim($sql));
    foreach ($sql_array as $query) {
        $queries = explode("\n", trim($query));
        foreach ($queries as $query) {
            $resource[$num] .= $query[0] == '#' ? '' : $query;
        }
        $num++;
    }
    unset($sql);

    foreach ($resource as $key => $query) {
        $query = trim($query);

        $query = preg_replace([
            '/CREATE\s*TABLE\s*`icms_/i',
            '/insert\s*into\s*`icms_/i'
        ], [
            sprintf('CREATE TABLE `%s', $prefix),
            sprintf('INSERT INTO `%s', $prefix)
        ], $query);
        $query = str_replace('`icms_', '`' . $prefix, $query);
        if (strripos($query, 'CREATE TABLE') !== false && $DROP_TABLE_IF_EXISTS) {
            preg_match('/CREATE\s*TABLE\s*`(.*?)`\s*\(/is', $query, $match);
            if ($match[1]) {
                $DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `' . $match[1] . '`;';
                // var_dump($DROP_TABLE_SQL);
                DB::query($DROP_TABLE_SQL);
            }
        }
        $query && DB::query($query);
    }
}
