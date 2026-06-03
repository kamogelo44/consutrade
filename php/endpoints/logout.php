<?php
/*
 * ConsuTrade - Logout Handler
 * Author: Kamogelo Phale
 */

// Detect which session is active before initializing
$possibleSessions = ['CONSUTRADE_ADMIN_SESSION', 'CONSUTRADE_SELLER_SESSION', 'CONSUTRADE_USER_SESSION'];
foreach ($possibleSessions as $sessionName) {
    session_name($sessionName);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        // Found active session, break out
        break;
    }
}

require_once dirname(__DIR__, 2) . '/init.php';

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$role = $auth->getCurrentUserRole();

$auth->logout();

// Clear cart count from session
unset($_SESSION['cart_count']);

$cookie_names = ['CONSUTRADE_ADMIN_SESSION', 'CONSUTRADE_SELLER_SESSION', 'CONSUTRADE_USER_SESSION'];
foreach ($cookie_names as $name) {
    if (isset($_COOKIE[$name])) {
        setcookie($name, '', time() - 3600, '/');
    }
}

if ($role === 'admin' || $role === 'seller') {
    header('Location: ' . $baseUrl . 'admin/login.php');
} else {
    header('Location: ' . $baseUrl . 'index.php');
}
exit;
