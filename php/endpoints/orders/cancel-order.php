<?php
/*
 * ConsuTrade - Cancel Order (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

rateLimit('order_cancel', 5, 300);

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'message' => ''];

if (!$isLoggedIn || !$currentUser->hasRole('buyer')) {
    $response['message'] = 'Unauthorized. Only buyers can cancel orders.';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = (int) ($input['order_id'] ?? 0);

if ($orderId <= 0) {
    $response['message'] = 'Invalid order ID';
    echo json_encode($response);
    exit;
}

// OrderService handles state validation, cancellation, stock restoration, and refunds
$result = $orderService->cancelByBuyer($orderId, $currentUser->getUserId());

$response['success'] = $result['success'];
$response['message'] = $result['message'];

echo json_encode($response);
