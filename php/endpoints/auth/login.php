<?php
/*
 * ConsuTrade - Unified Login Endpoint
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

// Detect AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$context = $_POST['context'] ?? 'main';

// Validate input
if (empty($email) || empty($password)) {
    $error_msg = 'Please enter both email and password.';
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_msg]);
        exit;
    }
    $_SESSION['login_error'] = $error_msg;
    $_SESSION['login_email'] = $email;
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Auth class handles everything with context
$result = $auth->login($email, $password, $context);

// Handle login failure
if (!$result['success']) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $result['message']]);
        exit;
    }
    $_SESSION['login_error'] = $result['message'];
    $_SESSION['login_email'] = $email;
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Handle successful login
if ($is_ajax) {
    echo json_encode(['success' => true, 'redirect' => $result['redirect']]);
    exit;
}

$_SESSION['flash'] = $result['message'];
header('Location: ' . $result['redirect']);
exit;
