<?php
/**
 * ConsuTrade - Database
 *
 * Manages the MySQL database connection using the Singleton pattern.
 *
 * @author     Kamogelo Phale
 * @module     ITECA3-12 Web Development and e-Commerce
 * @institution Eduvos
 * @version    2.0.0
 * @since      2026
 *
 * References:
 * - Pressman, R.S. and Maxim, B.R., 2015. Software Engineering:
 *   A Practitioner's Approach. 8th ed. McGraw-Hill.
 * - Dennis, A., Wixom, B.H. and Tegarden, D., 2015. Systems Analysis
 *   and Design: An Object-Oriented Approach with UML. 6th ed.
 *   John Wiley and Sons.
 * - PHP Group, 2025. Classes and Objects. Available at:
 *   https://www.php.net/manual/en/language.oop5.php
 * - PHP-FIG, 2023. PSR-12: Extended Coding Style. Available at:
 *   https://www.php.fig.org/psr/psr-12/
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
    public static function getInstance(): Database
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
    public function connect(): void
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
    public function setCharset(string $charset): void
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
    public function getConnection(): mysqli
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
    private function __clone()
    {
    }

    /**
     * Prevents unserialization.
     */
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize a singleton.');
    }
}