<?php
/*
 * ConsuTrade - Get Seller Recent Orders
 * Author: Kamogelo Phale
 * 
 * Returns JSON data for seller's recent orders (for dashboard)
 */

require_once dirname(__DIR__) . '/../php/config.php';
require_once dirname(__DIR__) . '/../php/helpers.php';

// Check if seller is logged in
if (!isSellerLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

startSession('seller');

$response = ['success' => true, 'orders' => []];

$sql = "SELECT o.order_id as id, o.total_price as total, o.status,
        DATE_FORMAT(o.created_at, '%d %b %Y') as created_at
        FROM orders o
        WHERE o.seller_id = ?
        ORDER BY o.created_at DESC 
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $response['orders'][] = $row;
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
?>