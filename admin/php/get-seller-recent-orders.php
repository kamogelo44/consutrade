<?php
/*
 * ConsuTrade - Get Seller Recent Orders (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns JSON data for seller's recent orders (for dashboard)
 */

require_once dirname(__DIR__) . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'orders' => [], 'message' => ''];

// Check if seller is logged in using centralized auth
if (!$is_logged_in || $current_user['role'] !== 'seller') {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$seller_id = $current_user_id;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

// Get recent orders with buyer information and item count
$sql = "SELECT o.order_id as id, o.total_price as total, o.status,
        DATE_FORMAT(o.created_at, '%d %b %Y') as created_at,
        DATE_FORMAT(o.created_at, '%d %b %Y, %h:%i %p') as full_created_at,
        u.full_name as buyer_name,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
        FROM orders o
        JOIN users u ON o.buyer_id = u.user_id
        WHERE o.seller_id = ?
        ORDER BY o.created_at DESC 
        LIMIT ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $seller_id, $limit);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = [
        'id' => (int)$row['id'],
        'total' => (float)$row['total'],
        'status' => $row['status'],
        'created_at' => $row['created_at'],
        'full_created_at' => $row['full_created_at'],
        'buyer_name' => $row['buyer_name'],
        'item_count' => (int)$row['item_count']
    ];
}
$stmt->close();

$response['success'] = true;
$response['orders'] = $orders;

echo json_encode($response);
?>