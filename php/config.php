<?php
/*
 * ConsuTrade - Database Configuration
 * Author: Kamogelo Phale
 */

// Load environment variables
$env = parse_ini_file(__DIR__ . '/../.env');

// Base URL for the site - get from .env file
function getBaseUrl()
{
    global $env;
    // If BASE_URL is set in .env, use it
    if (isset($env['BASE_URL']) && !empty($env['BASE_URL'])) {
        return rtrim($env['BASE_URL'], '/') . '/';
    }

    // Fallback for when .env isn't loaded
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/';
}

// Database credentials from .env
define('DB_HOST', $env['DB_HOST']);
define('DB_NAME', $env['DB_NAME']);
define('DB_USER', $env['DB_USER']);
define('DB_PASS', $env['DB_PASS']);

// PayFast Credentials
define('PAYFAST_MERCHANT_ID', '10047996');
define('PAYFAST_MERCHANT_KEY', 'f6r9pv9pnq6so');
define('PAYFAST_SANDBOX', true);

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

// Create global connection
$conn = Database::getInstance()->getConnection();
