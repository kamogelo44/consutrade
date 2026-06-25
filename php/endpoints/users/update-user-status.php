<?php
/*
 * ConsuTrade - Update User Status (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$auth->isAdmin()) {
    $response['message'] = 'Unauthorized. Admin only.';
    echo json_encode($response);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
$newStatus = isset($data['status']) ? $data['status'] : '';

if ($userId <= 0 || empty($newStatus)) {
    $response['message'] = 'Invalid request.';
    echo json_encode($response);
    exit;
}

// Prevent admin from changing their own status
if ($userId == $currentUser->getUserId()) {
    $response['message'] = 'You cannot change your own account status.';
    echo json_encode($response);
    exit;
}

$result = $userRepo->updateStatus($userId, $newStatus);

if ($result) {
    $response['success'] = true;
    $response['message'] = 'User status updated to ' . ucfirst($newStatus) . '.';
} else {
    $response['message'] = 'Could not update user status.';
}

echo json_encode($response);
