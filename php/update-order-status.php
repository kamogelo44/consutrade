<?php
/*
 * ConsuTrade - Update Order Status (AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows sellers to update order status (processing, shipped, completed, cancelled)
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in and is a seller using centralized auth
if (!$is_logged_in || $current_user['role'] !== 'seller') {
    $response['message'] = 'Unauthorized. Seller access required.';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$new_status = isset($input['status']) ? $input['status'] : '';
$seller_id = $current_user_id;

// Allowed status transitions
$allowed_statuses = ['processing', 'shipped', 'completed', 'cancelled'];

if ($order_id <= 0) {
    $response['message'] = 'Invalid order ID';
    echo json_encode($response);
    exit;
}

if (!in_array($new_status, $allowed_statuses)) {
    $response['message'] = 'Invalid status. Allowed: processing, shipped, completed, cancelled';
    echo json_encode($response);
    exit;
}

// Verify order belongs to this seller and get current status
$check_sql = "SELECT order_id, status FROM orders WHERE order_id = ? AND seller_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('ii', $order_id, $seller_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $response['message'] = 'Order not found or does not belong to you';
    echo json_encode($response);
    $check_stmt->close();
    exit;
}

$current_order = $check_result->fetch_assoc();
$current_status = $current_order['status'];
$check_stmt->close();

// Prevent invalid status transitions
if ($current_status === 'cancelled' && $new_status !== 'cancelled') {
    $response['message'] = 'Cannot update a cancelled order';
    echo json_encode($response);
    exit;
}

if ($current_status === 'completed' && $new_status !== 'completed') {
    $response['message'] = 'Cannot update a completed order';
    echo json_encode($response);
    exit;
}

// Update order status using helper if available, or direct query
$update_sql = "UPDATE orders SET status = ? WHERE order_id = ? AND seller_id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param('sii', $new_status, $order_id, $seller_id);

if ($update_stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Order status updated to ' . ucfirst($new_status);
    
    // Log the status change for debugging
    error_log("Order #{$order_id} status changed from {$current_status} to {$new_status} by seller #{$seller_id}");
} else {
    $response['message'] = 'Failed to update order status';
}

$update_stmt->close();
echo json_encode($response);
?>