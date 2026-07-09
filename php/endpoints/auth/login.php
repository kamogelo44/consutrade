<?php
/*
 * ConsuTrade - Unified Login Endpoint
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

// Rate limit: 5 attempts per 60 seconds
rateLimit('login', 5, 60);

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

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

if (empty($email) || empty($password)) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Please enter both email and password.']);
        exit;
    }
    $_SESSION['login_error'] = 'Please enter both email and password.';
    $_SESSION['login_email'] = $email;
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$result = $auth->login($email, $password, $context);

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

if ($is_ajax) {
    echo json_encode(['success' => true, 'redirect' => $result['redirect']]);
    exit;
}

header('Location: ' . $result['redirect']);
exit;
