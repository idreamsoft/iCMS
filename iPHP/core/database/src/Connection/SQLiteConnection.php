<?php
// namespace DataBase\Connection;

// use DataBase\Connection\Connection;

class SQLiteConnection extends Connection
{
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
