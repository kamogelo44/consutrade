<?php
/*
 * ConsuTrade - Get Order Status (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'status' => null, 'message' => ''];

if (!$isLoggedIn) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

if ($orderId <= 0) {
    $response['message'] = 'Invalid order ID';
    echo json_encode($response);
    exit;
}

$role = $currentUser->getRole();
$userId = $currentUser->getUserId();
$order = $orderRepo->getOrderDetails($orderId, $userId, $role);

if (!$order) {
    $response['message'] = 'Order not found';
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
