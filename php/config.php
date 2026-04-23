<?php
/*
 * ConsuTrade - Database Configuration
 * Author: Kamogelo Phale
 *
 */

// Base URL for the site
$baseUrl = "/www/consutrade/";

// Database credentials
$host = 'localhost';
$db_name = 'consutrade';
$username = 'root';
$password = '';

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

$conn = new mysqli($host, $username, $password, $db_name);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
?>