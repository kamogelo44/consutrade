<?php
/*
 * ConsuTrade - Get Recent Orders (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'orders' => [], 'message' => ''];

// Check if user has admin role (not just active role)
if (!$currentUser->hasRole('admin')) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

// Use OrderService for recent orders
$orders = $orderService->findRecent($limit);

$response['success'] = true;
$response['orders'] = $orders;

echo json_encode($response);
