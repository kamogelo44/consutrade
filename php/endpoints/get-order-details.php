<?php
/*
 * ConsuTrade - Get Order Details (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns detailed order information for the logged-in user
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'order' => null];

if (!$is_logged_in) {
    echo json_encode($response);
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$user_id  = $current_user_id;
$role     = $current_user['role'];

if ($order_id <= 0) {
    echo json_encode($response);
    exit;
}

$order = $orderRepo->getOrderDetails($order_id, $user_id, $role);

if (!$order) {
    echo json_encode($response);
    exit;
}

// Calculate subtotal from items and format image URLs
$subtotal = 0;
foreach ($order['items'] as &$item) {
    $item['image_url'] = $productRepo->getProductImageUrl($item['image_url'] ?? '');
    $item['total']     = $item['price'] * $item['quantity'];
    $subtotal         += $item['total'];
}
unset($item);

$delivery_fee = ($subtotal > 0 && $subtotal < 500) ? 50 : 0;
$total        = $subtotal + $delivery_fee;

// Get shipping address from order if available
$shipping_address = isset($order['shipping_address']) && !empty($order['shipping_address']) 
    ? $order['shipping_address'] 
    : 'Not provided';

$response['success'] = true;
$response['order']   = [
    'order_id'         => (int) $order['order_id'],
    'total_price'      => (float) $order['total_price'],
    'status'           => $order['status'],
    'payment_id'       => $order['payment_id'] ?? null,
    'created_at'       => date('d M Y, h:i A', strtotime($order['created_at'])),
    'subtotal'         => round($subtotal, 2),
    'delivery_fee'     => round($delivery_fee, 2),
    'total'            => round($total, 2),
    'other_party_name' => $order['other_party_name'],
    'shipping_address' => $shipping_address,
    'items'            => $order['items'],
];

echo json_encode($response);
?>