<?php
/*
 * ConsuTrade - Get Seller Recent Orders (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'orders' => [], 'message' => ''];

if (!$auth->isSeller()) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$seller_id = $currentUser->getUserId();
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

$orders = $orderRepo->findRecentBySeller($seller_id, $limit);

$response['success'] = true;
$response['orders'] = $orders;

echo json_encode($response);
