<?php
/*
 * ConsuTrade - Resend Verification Email
 */

require_once dirname(__DIR__, 3) . '/init.php';

rateLimit('resend_verification', 2, 300);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

$result = $auth->resendVerificationEmail($email);
echo json_encode($result);
