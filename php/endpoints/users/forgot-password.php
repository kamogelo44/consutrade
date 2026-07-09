<?php
/*
 * ConsuTrade - Forgot Password Endpoint
 */

require_once dirname(__DIR__, 3) . '/init.php';

rateLimit('forgot_password', 3, 300);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

$result = $auth->sendPasswordReset($email);
echo json_encode($result);
