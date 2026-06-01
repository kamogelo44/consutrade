<?php
/*
 * ConsuTrade - Logout Handler
 * Author: Kamogelo Phale
 */

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
