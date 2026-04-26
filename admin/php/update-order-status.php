<?php
/*
 * ConsuTrade - Update Order Status
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__) . '/../php/config.php';
require_once dirname(__DIR__) . '/../php/helpers.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if seller is logged in using helper
if (!isSellerLoggedIn()) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

// Start seller session
startSession('seller');

$input = json_decode(file_get_contents('php://input'), true);
$order_id = (int)($input['order_id'] ?? 0);
$new_status = $input['status'] ?? '';

$allowed_statuses = ['processing', 'shipped', 'completed', 'cancelled'];
if (!in_array($new_status, $allowed_statuses)) {
    $response['message'] = 'Invalid status';
    echo json_encode($response);
    exit;
}

$seller_id = $_SESSION['user_id'];

// Verify order belongs to this seller and get current status
$check_sql = "SELECT order_id, status FROM orders WHERE order_id = ? AND seller_id = ?";
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

$order = $check_result->fetch_assoc();
$current_status = $order['status'];
$check_stmt->close();

// Validate status transition
$valid_transitions = [
    'pending' => ['processing', 'cancelled'],
    'processing' => ['shipped', 'cancelled'],
    'shipped' => ['completed'],
    'completed' => [],
    'cancelled' => []
];

if (!in_array($new_status, $valid_transitions[$current_status] ?? [])) {
    $response['message'] = "Cannot change from '$current_status' to '$new_status'";
    echo json_encode($response);
    $conn->close();
    exit;
}

// Update status
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