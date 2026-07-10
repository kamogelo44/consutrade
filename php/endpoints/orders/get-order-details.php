<?php
/*
 * ConsuTrade - Get Order Details (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

rateLimit('order_details', 30, 60);

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'order' => null];

if (!$isLoggedIn) {
    $response['error'] = 'Not logged in';
    echo json_encode($response);
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    $response['error'] = 'Invalid order ID';
    echo json_encode($response);
    exit;
}

$user_id = $currentUser->getUserId();
$order = null;

// Try each role — OrderService enriches with transaction data automatically
if ($currentUser->hasRole('buyer')) {
    $order = $orderService->findByIdForBuyer($order_id, $user_id);
}

if (!$order && $currentUser->hasRole('seller')) {
    $order = $orderService->findByIdForSeller($order_id, $user_id);
}

if (!$order && $currentUser->hasRole('admin')) {
    $order = $orderService->findByIdForAdmin($order_id);
}

if (!$order) {
    $response['error'] = 'Order not found or you do not have permission to view it';
    echo json_encode($response);
    exit;
}

// Enrich items with image URLs
foreach ($order['items'] as &$item) {
    $item['image_url'] = $productService->getImageUrl($item['image_url'] ?? '');
}
unset($item);

$response['success'] = true;
$response['order'] = $order;

echo json_encode($response);
