<?php
/*
 * ConsuTrade - Reset Password Endpoint
 */

require_once dirname(__DIR__, 3) . '/init.php';

rateLimit('reset_password', 3, 300);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid reset token.']);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

$result = $auth->resetPassword($token, $password);
echo json_encode($result);
