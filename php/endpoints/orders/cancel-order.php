<?php
/*
 * ConsuTrade - Cancel Order (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'message' => ''];

if (!$isLoggedIn || !$currentUser instanceof Buyer) {
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

$orderData = $orderRepo->findById($orderId, $currentUser->getUserId(), 'buyer');

if (!$orderData) {
    $response['message'] = 'Order not found';
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
    $result = $orderRepo->cancelByBuyer($orderId, $currentUser->getUserId());

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
