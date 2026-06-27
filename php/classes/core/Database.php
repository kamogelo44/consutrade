<?php

/**
 * ConsuTrade - Database
 *
 * Manages the MySQL database connection using the Singleton pattern.
 *
 * @author Kamogelo Phale
 * @version 2.5.0
 */

class Database
{
    private static $instance = null;
    private $host;
    private $dbName;
    private $userName;
    private $password;
    private $connection;

    private function __construct()
    {
        $this->host     = DB_HOST;
        $this->dbName   = DB_NAME;
        $this->userName = DB_USER;
        $this->password = DB_PASS;
        $this->connection = null;
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function connect()
    {
        if ($this->connection !== null) {
            return;
        }

        $this->connection = new mysqli(
            $this->host,
            $this->userName,
            $this->password,
            $this->dbName
        );

        if ($this->connection->connect_error) {
            die('Could not connect to the database.');
        }

        $this->connection->set_charset('utf8mb4');
    }

    public function getConnection()
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    public function beginTransaction()
    {
        $this->getConnection()->begin_transaction();
    }

    public function rollBack()
    {
        $this->getConnection()->rollback();
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new Exception('Cannot unserialize a singleton.');
    }
}
