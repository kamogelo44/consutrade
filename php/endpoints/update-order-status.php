<?php
/*
 * ConsuTrade - Update Order Status (AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows sellers and admins to update order status
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check authentication
if (isAdminLoggedIn()) {
    $role = 'admin';
    $user_id = $current_user_id;
} elseif (isSellerLoggedIn()) {
    $role = 'seller';
    $user_id = $current_user_id;
} else {
    $response['message'] = 'Unauthorized. Please login as admin or seller.';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = (int)($input['order_id'] ?? 0);
$new_status = $input['status'] ?? '';

$allowed_statuses = ['processing', 'shipped', 'completed', 'cancelled'];
if (!in_array($new_status, $allowed_statuses)) {
    $response['message'] = 'Invalid status';
    echo json_encode($response);
    exit;
}

// Verify order exists and user has permission
if ($role === 'seller') {
    $check_sql = "SELECT order_id, status FROM orders WHERE order_id = ? AND seller_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ii', $order_id, $user_id);
} else {
    $check_sql = "SELECT order_id, status FROM orders WHERE order_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $order_id);
}

$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $response['message'] = 'Order not found';
    echo json_encode($response);
    $check_stmt->close();
    exit;
}

$order = $check_result->fetch_assoc();
$current_status = $order['status'];
$check_stmt->close();

// Validate status transition
$valid_transitions = [
    'pending' => ['processing', 'cancelled'],
    'processing' => ['shipped', 'cancelled'],
    'shipped' => ['completed', 'cancelled'],
    'completed' => [],
    'cancelled' => []
];

if (!in_array($new_status, $valid_transitions[$current_status] ?? [])) {
    $response['message'] = "Cannot change status from '$current_status' to '$new_status'";
    echo json_encode($response);
    exit;
}

$conn->begin_transaction();

try {
    if ($role === 'seller') {
        $update_sql = "UPDATE orders SET status = ? WHERE order_id = ? AND seller_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('sii', $new_status, $order_id, $user_id);
    } else {
        $update_sql = "UPDATE orders SET status = ? WHERE order_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('si', $new_status, $order_id);
    }

    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update order status');
    }
    $update_stmt->close();

    // If cancelling, restore stock
    if ($new_status === 'cancelled') {
        restoreOrderStock($conn, $order_id);
    }

    $conn->commit();
    
    $response['success'] = true;
    $response['message'] = 'Order status updated to ' . ucfirst($new_status);
    
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>