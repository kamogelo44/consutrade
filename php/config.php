<?php
/*
 * ConsuTrade - Configuration
 * Author: Kamogelo Phale
 */

$env = parse_ini_file(__DIR__ . '/../.env');

function getBaseUrl(): string
{
    global $env;
    if (isset($env['BASE_URL']) && !empty($env['BASE_URL'])) {
        return rtrim($env['BASE_URL'], '/') . '/';
    }

    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/';
}

define('DB_HOST', $env['DB_HOST']);
define('DB_NAME', $env['DB_NAME']);
define('DB_USER', $env['DB_USER']);
define('DB_PASS', $env['DB_PASS']);

define('PAYFAST_MERCHANT_ID', $env['PAYFAST_MERCHANT_ID']);
define('PAYFAST_MERCHANT_KEY', $env['PAYFAST_MERCHANT_KEY']);
define('PAYFAST_SANDBOX', $env['PAYFAST_SANDBOX']);

if (PAYFAST_SANDBOX) {
    define('PAYFAST_PROCESS_URL', 'https://sandbox.payfast.co.za/eng/process');
    define('PAYFAST_VALIDATE_URL', 'https://sandbox.payfast.co.za/eng/query/validate');
} else {
    define('PAYFAST_PROCESS_URL', 'https://www.payfast.co.za/eng/process');
    define('PAYFAST_VALIDATE_URL', 'https://www.payfast.co.za/eng/query/validate');
}
