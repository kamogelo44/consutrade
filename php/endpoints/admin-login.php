<?php
/*
 * ConsuTrade - Admin/Seller Login Handler
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . getBaseUrl() . 'admin/login.php');
    exit;
}

$email = trim($_POST['email']);
$password = $_POST['password'];
$role_type = $_POST['role_type'] ?? 'admin';

if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = 'Please enter both email and password';
    header('Location: ' . getBaseUrl() . 'admin/login.php');
    exit;
}

// Use Auth class login method
$result = $auth->login($email, $password);

if ($result['success']) {
    // Check if role matches the selected type
    if ($role_type === 'admin' && $result['role'] === 'admin') {
        header('Location: ' . getBaseUrl() . 'admin/admin-dashboard.php');
        exit;
    } elseif ($role_type === 'seller' && $result['role'] === 'seller') {
        header('Location: ' . getBaseUrl() . 'admin/seller-dashboard.php');
        exit;
    } else {
        // Wrong role for this login page
        $auth->logout();
        $_SESSION['login_error'] = 'Invalid credentials for ' . ucfirst($role_type) . ' login.';
    }
} else {
    $_SESSION['login_error'] = $result['message'];
}

$_SESSION['login_email'] = $email;
header('Location: ' . getBaseUrl() . 'admin/login.php');
exit;
?>