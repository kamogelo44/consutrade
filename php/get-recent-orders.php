<?php
/*
 * ConsuTrade - Get Recent Orders for Seller (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns the 5 most recent orders for a seller's dashboard
 */

require_once __DIR__ . '/../../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'orders' => []];

// Check if user is logged in and is a seller
if (!$is_logged_in || $current_user['role'] !== 'seller') {
    echo json_encode($response);
    exit;
}

$seller_id = $current_user_id;

// Get recent orders with buyer information and item count
$sql = "SELECT o.order_id, o.total_price, o.status, o.created_at,
        u.full_name as buyer_name,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
        FROM orders o
        JOIN users u ON o.buyer_id = u.user_id
        WHERE o.seller_id = ? 
        ORDER BY o.created_at DESC 
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $response['orders'][] = [
        'id' => (int)$row['order_id'],
        'total' => (float)$row['total_price'],
        'status' => $row['status'],
        'created_at' => date('d M Y, h:i A', strtotime($row['created_at'])),
        'buyer_name' => $row['buyer_name'],
        'item_count' => (int)$row['item_count']
    ];
}

$response['success'] = true;
$stmt->close();

echo json_encode($response);
?>