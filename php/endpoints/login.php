<?php
/*
 * ConsuTrade - Unified Login Endpoint
 * Author: Kamogelo Phale
 * 
 * Handles ALL user authentication (buyer, seller, admin)
 * Single endpoint for both main website and admin portal
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once dirname(__DIR__, 2) . '/init.php';

// Detect AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
    header('Location: ' . getBaseUrl() . 'index.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role_type = $_POST['role_type'] ?? 'buyer';
$redirect = $_POST['redirect'] ?? '';

if (empty($email) || empty($password)) {
    $error_msg = 'Please enter both email and password.';
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_msg]);
        exit;
    }
    $_SESSION['login_error'] = $error_msg;
    $_SESSION['login_email'] = $email;
    
    $backUrl = !empty($redirect) ? $redirect : getBaseUrl() . 'index.php';
    header('Location: ' . $backUrl);
    exit;
}

// Use Auth class to handle login
$result = $auth->login($email, $password);

if (!$result['success']) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $result['message']]);
        exit;
    }
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
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit;
    }
    $_SESSION['login_error'] = 'Invalid email or password.';
    header('Location: ' . getBaseUrl() . 'index.php');
    exit;
}

if (($role_type === 'admin' || $role_type === 'seller') && $result['role'] !== $role_type) {
    $auth->logout();
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit;
    }
    $_SESSION['login_error'] = 'Invalid email or password.';
    header('Location: ' . getBaseUrl() . 'admin/login.php');
    exit;
}

// Successful login - determine redirect URL
if ($result['role'] === 'admin') {
    $redirect_url = getBaseUrl() . 'admin/admin-dashboard.php';
} elseif ($result['role'] === 'seller') {
    $redirect_url = getBaseUrl() . 'admin/seller-dashboard.php';
} else {
    $redirect_url = getBaseUrl() . 'index.php';
}

$_SESSION['flash'] = 'Welcome back, ' . ($_SESSION['full_name'] ?? 'User') . '!';

if ($is_ajax) {
    // Ensure no extra whitespace before this output
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect' => $redirect_url]);
    exit;
}

header('Location: ' . $redirect_url);
exit;
?>