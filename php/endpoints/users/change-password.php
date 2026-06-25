<?php
/*
 * ConsuTrade - Change Password Endpoint
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$auth->isLoggedIn()) {
    $response['message'] = 'Please login.';
    echo json_encode($response);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$current_password = $data['current_password'] ?? '';
$new_password = $data['new_password'] ?? '';

if (empty($current_password) || empty($new_password)) {
    $response['message'] = 'All fields are required.';
    echo json_encode($response);
    exit;
}

if (strlen($new_password) < 6) {
    $response['message'] = 'New password must be at least 6 characters.';
    echo json_encode($response);
    exit;
}

$user_id = $currentUser->getUserId();
$user = $userRepo->findById($user_id);

if (!$user || !password_verify($current_password, $user->getPassword())) {
    $response['message'] = 'Current password is incorrect.';
    echo json_encode($response);
    exit;
}

$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$result = $userRepo->updatePassword($user_id, $hashed_password);

if ($result) {
    $response['success'] = true;
    $response['message'] = 'Password changed successfully.';
} else {
    $response['message'] = 'Failed to update password.';
}

echo json_encode($response);
