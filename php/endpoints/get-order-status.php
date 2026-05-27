<?php
/*
 * ConsuTrade - Get Order Status (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns the current status of an order
 */

require_once dirname(__DIR__, 2) . '/init.php';

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
    $sql = "SELECT o.status, o.created_at, u.full_name as other_party_name 
            FROM orders o 
            JOIN users u ON o.buyer_id = u.user_id
            WHERE o.order_id = ? AND o.seller_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $order_id, $user_id);
} else {
    $sql = "SELECT o.status, o.created_at, u.full_name as other_party_name 
            FROM orders o 
            JOIN users u ON o.seller_id = u.user_id
            WHERE o.order_id = ? AND o.buyer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $order_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $response['success'] = true;
    $response['status'] = $row['status'];
    $response['created_at'] = date('d M Y, h:i A', strtotime($row['created_at']));
    $response['other_party_name'] = $row['other_party_name'];
    
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