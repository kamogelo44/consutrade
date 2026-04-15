<?php
/*
 * ConsuTrade - Update Order Status
 * Author: Kamogelo Phale
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'seller') {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$new_status = isset($input['status']) ? $input['status'] : '';
$seller_id = $_SESSION['user_id'];

$allowed_statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];

if ($order_id <= 0 || !in_array($new_status, $allowed_statuses)) {
    $response['message'] = 'Invalid request';
    echo json_encode($response);
    exit;
}

// Verify order belongs to this seller
$check_sql = "SELECT order_id FROM orders WHERE order_id = ? AND seller_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('ii', $order_id, $seller_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $response['message'] = 'Order not found';
    echo json_encode($response);
    $check_stmt->close();
    $conn->close();
    exit;
}
$check_stmt->close();

// Update order status
$update_sql = "UPDATE orders SET status = ? WHERE order_id = ? AND seller_id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param('sii', $new_status, $order_id, $seller_id);

if ($update_stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Order status updated successfully';
} else {
    $response['message'] = 'Failed to update order status';
}

$update_stmt->close();
$conn->close();

echo json_encode($response);
?>