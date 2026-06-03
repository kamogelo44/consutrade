<?php
/*
 * ConsuTrade - Get Order Details (AJAX)
 * Author: Kamogelo Phale
 */

// Try to find existing session before init.php runs
$sessionNames = ['CONSUTRADE_ADMIN_SESSION', 'CONSUTRADE_SELLER_SESSION', 'CONSUTRADE_USER_SESSION'];
foreach ($sessionNames as $sName) {
    session_name($sName);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        break;
    }
    session_write_close();
}

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'order' => null, 'debug' => []];

if (!$isLoggedIn) {
    $response['debug']['error'] = 'Not logged in';
    echo json_encode($response);
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    $response['debug']['error'] = 'Invalid order ID';
    echo json_encode($response);
    exit;
}

$user_id = $currentUser->getUserId();
$role = $currentUser->getRole();

$response['debug']['order_id'] = $order_id;
$response['debug']['user_id'] = $user_id;
$response['debug']['role'] = $role;

// Direct query to check if order exists
$checkSql = "SELECT order_id, buyer_id, seller_id, status FROM orders WHERE order_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param('i', $order_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$orderExists = $checkResult->fetch_assoc();
$checkStmt->close();

if (!$orderExists) {
    $response['debug']['error'] = 'Order does not exist in database';
    echo json_encode($response);
    exit;
}

$response['debug']['order_data'] = $orderExists;

// Check permission
// Check permission
$hasPermission = false;
if ($role === 'admin') {
    $hasPermission = true;  // Admin can view any order
} elseif ($role === 'buyer' && $orderExists['buyer_id'] == $user_id) {
    $hasPermission = true;
} elseif ($role === 'seller' && $orderExists['seller_id'] == $user_id) {
    $hasPermission = true;
}
$response['debug']['has_permission'] = $hasPermission;

if (!$hasPermission) {
    $response['debug']['error'] = 'User does not have permission to view this order';
    echo json_encode($response);
    exit;
}

// Now get full order details
$order = $orderRepo->getOrderDetails($order_id, $user_id, $role);

if (!$order) {
    $response['debug']['error'] = 'getOrderDetails returned null';
    echo json_encode($response);
    exit;
}

// Calculate subtotal from items and format image URLs
$subtotal = 0;
foreach ($order['items'] as &$item) {
    $item['image_url'] = $productRepo->getProductImageUrl($item['image_url'] ?? '');
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
];

echo json_encode($response);
