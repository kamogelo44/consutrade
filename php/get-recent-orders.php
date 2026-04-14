<?php
/*
 * ConsuTrade - Get Recent Orders for Seller
 * Author: Kamogelo Phale
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'orders' => []];

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'seller') {
    echo json_encode($response);
    exit;
}

$seller_id = $_SESSION['user_id'];

$sql = "SELECT order_id, total_price, status, created_at 
        FROM orders 
        WHERE seller_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $response['orders'][] = [
        'id' => $row['order_id'],
        'total' => $row['total_price'],
        'status' => $row['status'],
        'created_at' => $row['created_at']
    ];
}

$response['success'] = true;
$stmt->close();
$conn->close();

echo json_encode($response);
?>