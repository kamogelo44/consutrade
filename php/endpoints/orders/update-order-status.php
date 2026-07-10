<?php
/*
 * ConsuTrade - Update Order Status (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

rateLimit('order_status', 20, 60);

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$isLoggedIn) {
    $response['message'] = 'Unauthorized. Please log in.';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = (int) ($input['order_id'] ?? 0);
$newStatus = $input['status'] ?? '';

if ($orderId <= 0 || empty($newStatus)) {
    $response['message'] = 'Invalid request.';
    echo json_encode($response);
    exit;
}

$userId = $currentUser->getUserId();

// Buyer cancellation
if ($currentUser->hasRole('buyer') && $newStatus === 'cancelled') {
    $result = $orderService->cancelByBuyer($orderId, $userId);
    $response['success'] = $result['success'];
    $response['message'] = $result['message'];
    echo json_encode($response);
    exit;
}

// Seller update (state machine enforced in service)
if ($currentUser->hasRole('seller')) {
    $result = $orderService->updateStatus($orderId, $userId, $newStatus);
    $response['success'] = $result['success'];
    $response['message'] = $result['message'];
    echo json_encode($response);
    exit;
}

// Admin update
if ($currentUser->hasRole('admin')) {
    $result = $orderService->updateStatus($orderId, $userId, $newStatus);
    $response['success'] = $result['success'];
    $response['message'] = $result['message'];
    echo json_encode($response);
    exit;
}

$response['message'] = 'Unauthorized.';
echo json_encode($response);
