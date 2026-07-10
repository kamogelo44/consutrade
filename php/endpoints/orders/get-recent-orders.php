<?php
/*
 * ConsuTrade - Get Recent Orders (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

rateLimit('admin_recent_orders', 20, 60);

header('Content-Type: application/json');

$response = ['success' => false, 'orders' => [], 'message' => ''];

if (!$currentUser->hasRole('admin')) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

$orders = $orderService->findRecent($limit);

$response['success'] = true;
$response['orders'] = $orders;

echo json_encode($response);
