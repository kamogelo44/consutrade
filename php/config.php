<?php
/*
 * ConsuTrade - Database Configuration
 * Author: Kamogelo Phale
 */

// Base URL for the site
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/www/consutrade/';
}

// Load environment variables
$env = parse_ini_file(__DIR__ . '/../.env');
// Database credentials
define('DB_HOST', $env['DB_HOST']);
define('DB_NAME', $env['DB_NAME']);
define('DB_USER', $env['DB_USER']);
define('DB_PASS', $env['DB_PASS']);
// PayFast Credentials
define('PAYFAST_MERCHANT_ID', 'PAYFAST_MERCHANT_ID');
define('PAYFAST_MERCHANT_KEY', 'PAYFAST_MERCHANT_KEY');
define('PAYFAST_SANDBOX', 'PAYFAST_SANDBOX'); 

// PayFast URLs
if (PAYFAST_SANDBOX) {
    define('PAYFAST_PROCESS_URL', 'https://sandbox.payfast.co.za/eng/process');
    define('PAYFAST_VALIDATE_URL', 'https://sandbox.payfast.co.za/eng/query/validate');
} else {
    define('PAYFAST_PROCESS_URL', 'https://www.payfast.co.za/eng/process');
    define('PAYFAST_VALIDATE_URL', 'https://www.payfast.co.za/eng/query/validate');
}

// Load the Database class
require_once __DIR__ . '/classes/Database.php';

// Create global connection for backward compatibility
// The Database class handles connecting and setting charset internally
$conn = Database::getInstance()->getConnection();
?>