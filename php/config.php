<?php
/*
 * ConsuTrade - Configuration
 * Author: Kamogelo Phale
 * 
 * Reads configuration from Azure environment variables (App Settings) first,
 * then falls back to .env file for local development.
 */

// ============================================================
// LOAD ENVIRONMENT - AZURE FIRST
// ============================================================

// Try Azure environment variables first (App Settings)
$env = [
    // Database
    'DB_HOST' => $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? null,
    'DB_NAME' => $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? null,
    'DB_USER' => $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? null,
    'DB_PASS' => $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? null,

    // PayFast
    'PAYFAST_MERCHANT_ID' => $_ENV['PAYFAST_MERCHANT_ID'] ?? $_SERVER['PAYFAST_MERCHANT_ID'] ?? null,
    'PAYFAST_MERCHANT_KEY' => $_ENV['PAYFAST_MERCHANT_KEY'] ?? $_SERVER['PAYFAST_MERCHANT_KEY'] ?? null,
    'PAYFAST_SANDBOX' => $_ENV['PAYFAST_SANDBOX'] ?? $_SERVER['PAYFAST_SANDBOX'] ?? null,

    // URL
    'BASE_URL' => $_ENV['BASE_URL'] ?? $_SERVER['BASE_URL'] ?? null,

    // Maintenance
    'MAINTENANCE_ALLOWED_IPS' => $_ENV['MAINTENANCE_ALLOWED_IPS'] ?? $_SERVER['MAINTENANCE_ALLOWED_IPS'] ?? null,
];

// Check if Azure variables are set (not null and not empty)
$azureVariablesSet = array_filter($env, function ($value) {
    return $value !== null && $value !== '';
});

// If no Azure variables are set, fallback to .env file
if (empty($azureVariablesSet)) {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $envFileData = parse_ini_file($envFile);
        if ($envFileData !== false) {
            foreach ($envFileData as $key => $value) {
                $env[$key] = $value;
            }
        }
    }
}

// For PHPUNIT testing
if (defined('PHPUNIT_TESTING') && PHPUNIT_TESTING === true) {
    $testEnvFile = __DIR__ . '/../.env.testing';
    if (file_exists($testEnvFile)) {
        $testData = parse_ini_file($testEnvFile);
        if ($testData !== false) {
            foreach ($testData as $key => $value) {
                $env[$key] = $value;
            }
        }
    }
}

// ============================================================
// ENSURE DEFAULTS
// ============================================================

// Ensure PAYFAST_SANDBOX has a default value
if (!isset($env['PAYFAST_SANDBOX']) || $env['PAYFAST_SANDBOX'] === '') {
    $env['PAYFAST_SANDBOX'] = 'true';
}

// ============================================================
// BASE URL FUNCTION
// ============================================================

function getBaseUrl(): string
{
    global $env;

    if (isset($env['BASE_URL']) && !empty($env['BASE_URL'])) {
        return rtrim($env['BASE_URL'], '/') . '/';
    }

    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = dirname($scriptName);

    if ($dir !== '/' && $dir !== '\\') {
        $dir = str_replace('\\', '/', $dir);
        return $protocol . '://' . $host . $dir . '/';
    }

    return $protocol . '://' . $host . '/';
}

// ============================================================
// DEFINE CONSTANTS
// ============================================================

define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
define('DB_NAME', $env['DB_NAME'] ?? 'consutrade');
define('DB_USER', $env['DB_USER'] ?? 'root');
define('DB_PASS', $env['DB_PASS'] ?? '');

define('PAYFAST_MERCHANT_ID', $env['PAYFAST_MERCHANT_ID'] ?? '');
define('PAYFAST_MERCHANT_KEY', $env['PAYFAST_MERCHANT_KEY'] ?? '');

// Convert PAYFAST_SANDBOX to boolean
$sandbox = isset($env['PAYFAST_SANDBOX']) ? filter_var($env['PAYFAST_SANDBOX'], FILTER_VALIDATE_BOOLEAN) : true;
define('PAYFAST_SANDBOX', $sandbox);

// PayFast URLs
if (PAYFAST_SANDBOX) {
    define('PAYFAST_PROCESS_URL', 'https://sandbox.payfast.co.za/eng/process');
    define('PAYFAST_VALIDATE_URL', 'https://sandbox.payfast.co.za/eng/query/validate');
} else {
    define('PAYFAST_PROCESS_URL', 'https://www.payfast.co.za/eng/process');
    define('PAYFAST_VALIDATE_URL', 'https://www.payfast.co.za/eng/query/validate');
}

// ============================================================
// MAINTENANCE MODE CHECK
// ============================================================

/**
 * Get the real client IP address (works behind proxies like Azure)
 */
function getRealClientIp(): ?string
{
    // Check Azure/Cloudflare forwarded headers first
    if (isset($_SERVER['HTTP_X_ORIGINAL_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_ORIGINAL_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    }

    // Handle comma-separated IPs (take the first one)
    if ($ip && strpos($ip, ',') !== false) {
        $ips = explode(',', $ip);
        $ip = trim($ips[0]);
    }

    // Strip port number if present (IPv4 with port)
    if ($ip && strpos($ip, ':') !== false) {
        $parts = explode(':', $ip);
        if (strpos($parts[0], '.') !== false && count($parts) === 2) {
            $ip = $parts[0];
        }
    }

    return $ip;
}

/**
 * Check if the current user is allowed during maintenance mode
 * Returns true if allowed, false if should be blocked
 */
function isMaintenanceAllowed(): bool
{
    global $env;

    // If maintenance IPs aren't set, allow everyone (maintenance mode disabled)
    if (empty($GLOBALS['env']['MAINTENANCE_ALLOWED_IPS'])) {
        return true;
    }

    $clientIp = getRealClientIp();
    $allowedIps = array_map('trim', explode(',', $GLOBALS['env']['MAINTENANCE_ALLOWED_IPS']));

    return in_array($clientIp, $allowedIps);
}

/**
 * Redirect to maintenance page if not allowed
 * Call this at the top of any page that should respect maintenance mode
 */
function checkMaintenanceMode(): void
{
    global $baseUrl;

    if (!isMaintenanceAllowed()) {
        header('Location: ' . $baseUrl . 'maintenance.php');
        exit;
    }
}

// ============================================================
// MAKE ENV AVAILABLE GLOBALLY
// ============================================================

$GLOBALS['env'] = $env;
