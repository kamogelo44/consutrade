<?php
/*
 * ConsuTrade - Get Seller Recent Orders (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns JSON data for seller's recent orders (for dashboard)
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'orders' => [], 'message' => ''];

// Check if seller is logged in using Auth class
if (!$auth->isSellerLoggedIn()) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$seller_id = $current_user_id;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

// Use OrderRepository to get recent orders
$orders = $orderRepo->getSellerRecentOrders($seller_id, $limit);

$response['success'] = true;
$response['orders'] = $orders;

echo json_encode($response);
?>