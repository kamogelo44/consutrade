<?php
/*
 * ConsuTrade - Cancel Order
 * Author: Kamogelo Phale
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$buyer_id = $_SESSION['user_id'];

if ($order_id <= 0) {
    $response['message'] = 'Invalid order ID';
    echo json_encode($response);
    exit;
}

// Check if order belongs to this buyer and is pending
$check_sql = "SELECT order_id, status FROM orders WHERE order_id = ? AND buyer_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('ii', $order_id, $buyer_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $response['message'] = 'Order not found';
    echo json_encode($response);
    $check_stmt->close();
    $conn->close();
    exit;
}

$order = $check_result->fetch_assoc();
if ($order['status'] !== 'pending') {
    $response['message'] = 'Only pending orders can be cancelled';
    echo json_encode($response);
    $check_stmt->close();
    $conn->close();
    exit;
}
$check_stmt->close();

// Update order status to cancelled
$update_sql = "UPDATE orders SET status = 'cancelled' WHERE order_id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param('i', $order_id);

if ($update_stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Order cancelled successfully';
} else {
    $response['message'] = 'Failed to cancel order';
}

$update_stmt->close();
$conn->close();

echo json_encode($response);
?>