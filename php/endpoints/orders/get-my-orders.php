<?php
/*
 * ConsuTrade - Get My Orders (AJAX)
 * Works for both buyers and sellers based on context
 */

require_once dirname(__DIR__, 3) . '/init.php';

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

// Check context - what type of orders to load
$orderType = $_GET['type'] ?? 'buyer'; // 'buyer' or 'seller'

if ($orderType === 'seller') {
    // Load seller orders (orders where user is the seller)
    // Only if user has seller role
    if (!$currentUser->hasRole('seller')) {
        $response['error'] = 'You do not have seller access';
        echo json_encode($response);
        exit;
    }

    $orders = $orderService->findBySeller($userId, $status, $search, $limit, $offset);
    $totalOrders = $orderService->countBySeller($userId, $status, $search);
} else {
    // Load buyer orders (orders where user is the buyer)
    // Only if user has buyer role
    if (!$currentUser->hasRole('buyer')) {
        $response['error'] = 'You do not have buyer access';
        echo json_encode($response);
        exit;
    }

    $orders = $orderService->findByBuyer($userId, $status, $search, $limit, $offset);
    $totalOrders = $orderService->countByBuyer($userId, $status, $search);

    foreach ($orders as &$order) {
        $review = $reviewRepo->findByOrderAndBuyer($order['order_id'], $userId);
        if ($review) {
            $order['has_review'] = true;
            $order['review_rating'] = $review['rating'];
            $order['review_comment'] = $review['comment'];
        } else {
            $order['has_review'] = false;
            $order['review_rating'] = null;
            $order['review_comment'] = null;
        }
    }
}

$response['success'] = true;
$response['orders'] = $orders;
$response['total_pages'] = ceil($totalOrders / $limit);
$response['current_page'] = $page;

echo json_encode($response);
