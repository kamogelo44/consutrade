<?php
/*
 * ConsuTrade - Get Recent Orders (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'orders' => [], 'message' => ''];

if (!$auth->isAdmin()) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$orders = $orderRepo->getRecentOrders($limit);

$response['success'] = true;
$response['orders'] = $orders;

echo json_encode($response);
