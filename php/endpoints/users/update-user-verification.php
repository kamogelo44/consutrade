<?php
/*
 * ConsuTrade - Update User Verification (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$auth->isAdmin()) {
    $response['message'] = 'Unauthorized.';
    echo json_encode($response);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
$verify = isset($data['verify']) ? (bool)$data['verify'] : false;

if ($user_id <= 0) {
    $response['message'] = 'Invalid user.';
    echo json_encode($response);
    exit;
}

// Use UserService for verification update
$result = $userService->updateVerification($user_id, $verify);

$response['success'] = $result['success'];
$response['message'] = $result['message'];

echo json_encode($response);
