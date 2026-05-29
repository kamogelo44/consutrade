<?php
/*
 * ConsuTrade - Unified Login Endpoint
 * Author: Kamogelo Phale
 * 
 * Handles ALL user authentication (buyer, seller, admin)
 * Single endpoint for both main website and admin portal
 */

require_once dirname(__DIR__, 2) . '/init.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . getBaseUrl() . 'index.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role_type = $_POST['role_type'] ?? 'buyer';
$redirect = $_POST['redirect'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = 'Please enter both email and password.';
    $_SESSION['login_email'] = $email;
    
    $backUrl = !empty($redirect) ? $redirect : getBaseUrl() . 'index.php';
    header('Location: ' . $backUrl);
    exit;
}

// Use Auth class to handle login
$result = $auth->login($email, $password);

if (!$result['success']) {
    $_SESSION['login_error'] = $result['message'];
    $_SESSION['login_email'] = $email;
    
    if ($role_type === 'admin' || $role_type === 'seller') {
        header('Location: ' . getBaseUrl() . 'admin/login.php');
    } else {
        $backUrl = !empty($redirect) ? $redirect : getBaseUrl() . 'index.php';
        header('Location: ' . $backUrl);
    }
    exit;
}

// Check role matches the requested login type
if ($role_type === 'buyer' && $result['role'] !== 'buyer') {
    $auth->logout();
    $_SESSION['login_error'] = 'Invalid email or password.';
    header('Location: ' . getBaseUrl() . 'index.php');
    exit;
}

if (($role_type === 'admin' || $role_type === 'seller') && $result['role'] !== $role_type) {
    $auth->logout();
    $_SESSION['login_error'] = 'Invalid email or password.';
    header('Location: ' . getBaseUrl() . 'admin/login.php');
    exit;
}

// Successful login - redirect based on role
$_SESSION['flash'] = 'Welcome back, ' . ($_SESSION['full_name'] ?? 'User') . '!';

if ($result['role'] === 'admin') {
    header('Location: ' . getBaseUrl() . 'admin/admin-dashboard.php');
} elseif ($result['role'] === 'seller') {
    header('Location: ' . getBaseUrl() . 'admin/seller-dashboard.php');
} else {
    header('Location: ' . getBaseUrl() . 'index.php');
}
exit;
?>