<?php
/*
 * ConsuTrade - Get Order Status (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns the current status of an order
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'status' => null, 'message' => ''];

// Check if user is logged in
if (!$is_logged_in) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$user_id = $current_user_id;
$role = $current_user['role'];

if ($order_id <= 0) {
    $response['message'] = 'Invalid order ID';
    echo json_encode($response);
    exit;
}

// Get order status based on role (buyer or seller can view)
if ($role === 'seller') {
    $sql = "SELECT status, created_at FROM orders WHERE order_id = ? AND seller_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $order_id, $user_id);
} else {
    $sql = "SELECT status, created_at FROM orders WHERE order_id = ? AND buyer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $order_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $response['success'] = true;
    $response['status'] = $row['status'];
    $response['created_at'] = date('d M Y, h:i A', strtotime($row['created_at']));
    
    // Add status description
    $status_descriptions = [
        'pending' => 'Your order is pending confirmation',
        'processing' => 'Your order is being processed',
        'shipped' => 'Your order has been shipped',
        'completed' => 'Your order has been completed',
        'cancelled' => 'Your order has been cancelled'
    ];
    $response['status_description'] = $status_descriptions[$row['status']] ?? 'Status unknown';
} else {
    $response['message'] = 'Order not found';
}

$stmt->close();
echo json_encode($response);
?>