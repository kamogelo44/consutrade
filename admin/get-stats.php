<?php
/*
 * ConsuTrade - Get Statistics
 * Author: Kamogelo Phale
 * 
 * Returns JSON data for dashboard stats (works for both admin and seller)
 */

session_start();
require_once '../php/config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'total_users' => 0, 'total_products' => 0, 'total_orders' => 0, 'pending_orders' => 0, 'total_earnings' => 0];

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role === 'admin') {
    // Admin stats - get all data
    $users_sql = "SELECT COUNT(*) as count FROM users";
    $users_result = $conn->query($users_sql);
    $response['total_users'] = (int)$users_result->fetch_assoc()['count'];
    
    $products_sql = "SELECT COUNT(*) as count FROM products";
    $products_result = $conn->query($products_sql);
    $response['total_products'] = (int)$products_result->fetch_assoc()['count'];
    
    $orders_sql = "SELECT COUNT(*) as count FROM orders";
    $orders_result = $conn->query($orders_sql);
    $response['total_orders'] = (int)$orders_result->fetch_assoc()['count'];
    
    $pending_sql = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
    $pending_result = $conn->query($pending_sql);
    $response['pending_orders'] = (int)$pending_result->fetch_assoc()['count'];
    
} elseif ($role === 'seller') {
    // Seller stats - only seller's own data
    $products_sql = "SELECT COUNT(*) as count FROM products WHERE seller_id = ?";
    $products_stmt = $conn->prepare($products_sql);
    $products_stmt->bind_param('i', $user_id);
    $products_stmt->execute();
    $products_result = $products_stmt->get_result();
    $response['total_products'] = (int)$products_result->fetch_assoc()['count'];
    $products_stmt->close();
    
    $orders_sql = "SELECT COUNT(*) as count, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending FROM orders WHERE seller_id = ?";
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param('i', $user_id);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();
    $order_row = $orders_result->fetch_assoc();
    $response['total_orders'] = (int)$order_row['count'];
    $response['pending_orders'] = (int)($order_row['pending'] ?? 0);
    $orders_stmt->close();
    
    $earnings_sql = "SELECT SUM(total_price) as total FROM orders WHERE seller_id = ? AND status = 'completed'";
    $earnings_stmt = $conn->prepare($earnings_sql);
    $earnings_stmt->bind_param('i', $user_id);
    $earnings_stmt->execute();
    $earnings_result = $earnings_stmt->get_result();
    $earnings_row = $earnings_result->fetch_assoc();
    $response['total_earnings'] = (float)($earnings_row['total'] ?? 0);
    $earnings_stmt->close();
}

$response['success'] = true;
$conn->close();

echo json_encode($response);
exit;
?>