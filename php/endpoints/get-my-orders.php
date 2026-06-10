<?php
/*
 * ConsuTrade - Get My Orders (AJAX)
 * Works for both buyers and sellers
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'orders' => [], 'total_pages' => 1, 'current_page' => 1];

if (!$isLoggedIn) {
    $response['error'] = 'Please log in';
    echo json_encode($response);
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = $_GET['status'] ?? 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 10;
$offset = ($page - 1) * $limit;

$userId = $currentUser->getUserId();

if ($currentUserRole === 'seller') {
    $orders = $orderRepo->getSellerOrders($userId, $status, $search, $limit, $offset);
    $totalOrders = $orderRepo->countSellerOrders($userId, $status, $search);
} else {
    // buyer
    $orders = $orderRepo->getBuyerOrders($userId, $status, $search, $limit, $offset);
    $totalOrders = $orderRepo->countBuyerOrders($userId, $status, $search);
}

$response['success'] = true;
$response['orders'] = $orders;
$response['total_pages'] = ceil($totalOrders / $limit);
$response['current_page'] = $page;

echo json_encode($response);
