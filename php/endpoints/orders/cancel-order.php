<?php
/*
 * ConsuTrade - Cancel Order (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'message' => ''];

// Check if user has buyer role (not just active role)
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

$userId = $currentUser->getUserId();

// Use OrderService for order lookup - use buyer-specific method
$orderData = $orderService->findByIdForBuyer($orderId, $userId);

if (!$orderData) {
    $response['message'] = 'Order not found or you do not have permission.';
    echo json_encode($response);
    exit;
}

$order = new Order($orderData);

if (!$order->canBeCancelledByBuyer()) {
    $response['message'] = 'Only pending orders can be cancelled. Current status: ' . ucfirst($order->getStatus());
    echo json_encode($response);
    exit;
}

$conn->begin_transaction();

try {
    // Use OrderService for cancellation with stock restoration
    $result = $orderService->cancelByBuyer($orderId, $userId);

    if (!$result) {
        throw new Exception('Failed to cancel order');
    }

    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Order cancelled successfully.';
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Could not cancel order. Please try again.';
}

echo json_encode($response);
