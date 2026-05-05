<?php
/*
 * ConsuTrade - Database Configuration
 * Author: Kamogelo Phale
 */

// Base URL for the site
function getBaseUrl() {
    return "/www/consutrade/";
}

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'consutrade');
define('DB_USER', 'root');
define('DB_PASS', '');

// PayFast Credentials
define('PAYFAST_MERCHANT_ID', '10047996');
define('PAYFAST_MERCHANT_KEY', 'f6r9pv9pnq6so');
define('PAYFAST_SANDBOX', true);  // Set to false for production

// PayFast URLs
if (PAYFAST_SANDBOX) {
    define('PAYFAST_PROCESS_URL', 'https://sandbox.payfast.co.za/eng/process');
    define('PAYFAST_VALIDATE_URL', 'https://sandbox.payfast.co.za/eng/query/validate');
} else {
    define('PAYFAST_PROCESS_URL', 'https://www.payfast.co.za/eng/process');
    define('PAYFAST_VALIDATE_URL', 'https://www.payfast.co.za/eng/query/validate');
}

/**
 * Get database connection (Singleton pattern)
 * 
 * @return mysqli Database connection object
 */
function getDBConnection() {
    static $connection = null;
    
    if ($connection === null) {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($connection->connect_error) {
            die('Database connection failed: ' . $connection->connect_error);
        }
        
        $connection->set_charset('utf8mb4');
    }
    
    return $connection;
}

// Create global connection for backward compatibility
$conn = getDBConnection();
?>