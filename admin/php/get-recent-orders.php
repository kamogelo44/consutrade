<?php
/*
 * ConsuTrade - Get Recent Orders (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns recent orders for admin dashboard
 */

require_once dirname(__DIR__) . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'orders' => [], 'message' => ''];

// Check if admin is logged in using centralized auth
if (!$is_logged_in || $current_user['role'] !== 'admin') {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

// Get recent orders with buyer and seller information
$sql = "SELECT o.order_id, o.total_price, o.status, 
        DATE_FORMAT(o.created_at, '%d %b %Y') as created_at,
        DATE_FORMAT(o.created_at, '%d %b %Y, %h:%i %p') as full_created_at,
        u.full_name as buyer_name,
        s.full_name as seller_name,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
        FROM orders o
        JOIN users u ON o.buyer_id = u.user_id
        JOIN users s ON o.seller_id = s.user_id
        ORDER BY o.created_at DESC 
        LIMIT ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $limit);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = [
        'order_id' => (int)$row['order_id'],
        'total_price' => (float)$row['total_price'],
        'status' => $row['status'],
        'created_at' => $row['created_at'],
        'full_created_at' => $row['full_created_at'],
        'buyer_name' => $row['buyer_name'],
        'seller_name' => $row['seller_name'],
        'item_count' => (int)($row['item_count'] ?? 0)
    ];
}
$stmt->close();

$response['success'] = true;
$response['orders'] = $orders;

echo json_encode($response);
?>