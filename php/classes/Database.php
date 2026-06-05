<?php

/**
 * ConsuTrade - Database
 *
 * Manages the MySQL database connection using the Singleton pattern.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */

class Database
{
    /** @var Database|null The single instance */
    private static $instance = null;

    /** @var string Database host */
    private $host;

    /** @var string Database name */
    private $dbName;

    /** @var string Database username */
    private $userName;

    /** @var string Database password */
    private $password;

    /** @var mysqli|null The MySQL connection */
    private $connection;

    /**
     * Private constructor — stores credentials from config constants.
     * Does NOT connect yet; connect() must be called separately.
     */
    private function __construct()
    {
        $this->host     = DB_HOST;
        $this->dbName   = DB_NAME;
        $this->userName = DB_USER;
        $this->password = DB_PASS;
        $this->connection = null;
    }

    /**
     * Returns the single Database instance.
     *
     * @return Database
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }

        return self::$instance;
    }

    /**
     * Opens the mysqli connection if not already open.
     * Safe to call multiple times — only connects once.
     *
     * @return void
     */
    public function connect()
    {
        if ($this->connection !== null) {
            return; // Already connected
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
    }

    /**
     * Sets the connection character set.
     *
     * @param string $charset The character set (e.g., 'utf8mb4')
     * @return void
     */
    public function setCharset($charset)
    {
        if ($this->connection !== null) {
            $this->connection->set_charset($charset);
        }
    }

    /**
     * Returns the mysqli connection.
     * Connects first if not already connected.
     *
     * @return mysqli
     */
    public function getConnection()
    {
        if ($this->connection === null) {
            $this->connect();
            $this->setCharset('utf8mb4');
        }

        return $this->connection;
    }

    /**
     * Prevents cloning.
     */
    private function __clone() {}

    /**
     * Prevents unserialization.
     */
    public function __wakeup()
    {
        throw new Exception('Cannot unserialize a singleton.');
    }
}
