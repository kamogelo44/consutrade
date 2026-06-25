<?php
/*
 * ConsuTrade - Get Order Details (AJAX)
 * Author: Kamogelo Phale
 * 
 * Retrieves detailed order information for buyers, sellers, and admins.
 * Used by order details modal across the platform.
 */

require_once dirname(__DIR__, 3) . '/init.php';

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
$role = $currentUser->getRole();

// Use OrderService for order lookup
$orderExists = $orderService->findById($order_id, $user_id, $role);

if (!$orderExists) {
    $response['error'] = 'Order not found';
    echo json_encode($response);
    exit;
}

$order = $orderService->findById($order_id, $user_id, $role);

if (!$order) {
    $response['error'] = 'Order details not found';
    echo json_encode($response);
    exit;
}

// Get transaction for this order
$transaction = $transactionRepo->findByOrderId($order_id);

$subtotal = 0;
foreach ($order['items'] as &$item) {
    $item['image_url'] = $productService->getImageUrl($item['image_url'] ?? '');
    $item['total'] = $item['price'] * $item['quantity'];
    $subtotal += $item['total'];
}
unset($item);

$delivery_fee = ($subtotal > 0 && $subtotal < 500) ? 50 : 0;
$total = $subtotal + $delivery_fee;

$shipping_address = isset($order['shipping_address']) && !empty($order['shipping_address'])
    ? $order['shipping_address']
    : 'Not provided';

$response['success'] = true;
$response['order'] = [
    'order_id' => (int) $order['order_id'],
    'total_price' => (float) $order['total_price'],
    'status' => $order['status'],
    'payment_id' => $order['payment_id'] ?? null,
    'created_at' => date('d M Y, h:i A', strtotime($order['created_at'])),
    'subtotal' => round($subtotal, 2),
    'delivery_fee' => round($delivery_fee, 2),
    'total' => round($total, 2),
    'other_party_name' => $order['other_party_name'],
    'shipping_address' => $shipping_address,
    'items' => $order['items'],
    'transaction' => $transaction ? [
        'reference' => $transaction->getPayfastRef(),
        'status' => $transaction->getStatus(),
        'amount' => $transaction->getAmount(),
        'paid_at' => date('d M Y, h:i A', strtotime($transaction->getPaidAt()))
    ] : null
];

echo json_encode($response);
