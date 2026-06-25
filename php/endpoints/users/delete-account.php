<?php

/**
 * ConsuTrade - Delete Account Endpoint
 * 
 * Handles permanent account deletion requests from users.
 * Uses UserService for all business logic.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in
if (!$isLoggedIn) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

// Admin accounts cannot be deleted through this endpoint
if ($currentUser->getRole() === 'admin') {
    $response['message'] = 'Admin accounts cannot be deleted via this endpoint';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$password = $input['password'] ?? '';
$user_id = $currentUser->getUserId();

if (empty($password)) {
    $response['message'] = 'Password is required';
    echo json_encode($response);
    exit;
}

// Use UserService for account deletion
$result = $userService->deleteAccount($user_id, $password);

$response['success'] = $result['success'];
$response['message'] = $result['message'];

echo json_encode($response);
