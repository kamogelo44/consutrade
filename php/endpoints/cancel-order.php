<?php
/*
 * ConsuTrade - Cancel Order (AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows buyers to cancel pending orders
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'message' => ''];

if (!$is_logged_in) {
    $response['message'] = 'Please login to cancel orders';
    echo json_encode($response);
    exit;
}

if ($current_user['role'] !== 'buyer') {
    $response['message'] = 'Only buyers can cancel orders';
    echo json_encode($response);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$buyer_id = $current_user_id;

if ($order_id <= 0) {
    $response['message'] = 'Invalid order ID';
    echo json_encode($response);
    exit;
}

// Check ownership and status
$check_sql = "SELECT order_id, status FROM orders WHERE order_id = ? AND buyer_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('ii', $order_id, $buyer_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $response['message'] = 'Order not found.';
    echo json_encode($response);
    $check_stmt->close();
    exit;
}

$order = $check_result->fetch_assoc();
$check_stmt->close();

if ($order['status'] !== 'pending') {
    $response['message'] = 'Only pending orders can be cancelled. Current status: ' . ucfirst($order['status']);
    echo json_encode($response);
    exit;
}

// Cancel order and restore stock
$conn->begin_transaction();

try {
    $result = $orderRepo->cancelBuyerOrder($order_id, $buyer_id);

    if (!$result) {
        throw new Exception('Failed to cancel order');
    }

    // Restore stock
    $productRepo->restoreOrderStock($order_id);

    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Order cancelled.';

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Could not cancel order.';
}

echo json_encode($response);