<?php
/*
 * ConsuTrade - Get Recent Orders
 * Author: Kamogelo Phale
 * 
 * Returns JSON data for recent orders
 */

session_start();
require_once '../php/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$response = ['success' => true, 'orders' => []];

$sql = "SELECT o.order_id, o.total_price, o.status, 
        DATE_FORMAT(o.created_at, '%d %b %Y') as created_at,
        u.full_name as buyer_name
        FROM orders o
        JOIN users u ON o.buyer_id = u.user_id
        ORDER BY o.created_at DESC 
        LIMIT 5";

$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $response['orders'][] = $row;
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
?>