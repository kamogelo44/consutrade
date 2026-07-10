<?php
/*
 * ConsuTrade - Get Order Status (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

rateLimit('order_status_check', 30, 60);

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'status' => null, 'message' => ''];

if (!$isLoggedIn) {
    $response['message'] = 'Unauthorized. Please login.';
    echo json_encode($response);
    exit;
}

$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

if ($orderId <= 0) {
    $response['message'] = 'Invalid order ID';
    echo json_encode($response);
    exit;
}

$userId = $currentUser->getUserId();
$isBuyer = $currentUser->hasRole('buyer');
$isSeller = $currentUser->hasRole('seller');
$isAdmin = $currentUser->hasRole('admin');

$order = null;

if ($isBuyer) {
    $order = $orderService->findByIdForBuyer($orderId, $userId);
}

if (!$order && $isSeller) {
    $order = $orderService->findByIdForSeller($orderId, $userId);
}

if (!$order && $isAdmin) {
    $order = $orderService->findByIdForAdmin($orderId);
}

if (!$order) {
    $response['message'] = 'Order not found or you do not have permission to view it';
    echo json_encode($response);
    exit;
}

$statusDescriptions = [
    'pending' => 'Your order is pending confirmation',
    'processing' => 'Your order is being processed',
    'shipped' => 'Your order has been shipped',
    'completed' => 'Your order has been completed',
    'cancelled' => 'Your order has been cancelled'
];

$response['success'] = true;
$response['status'] = $order['status'];
$response['created_at'] = date('d M Y, h:i A', strtotime($order['created_at']));
$response['other_party_name'] = $order['other_party_name'];
$response['status_description'] = $statusDescriptions[$order['status']] ?? 'Status unknown';

echo json_encode($response);
