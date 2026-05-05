<?php
/*
 * ConsuTrade - Get Order Details (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns detailed order information for the logged-in user
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'order' => null];

// Check if user is logged in using centralized auth
if (!$is_logged_in) {
    echo json_encode($response);
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$user_id = $current_user_id;
$role = $current_user['role'];

if ($order_id <= 0) {
    echo json_encode($response);
    exit;
}

// Get order basic info based on role
if ($role === 'seller') {
    $sql = "SELECT o.order_id, o.total_price, o.status, o.created_at, o.payment_id,
            u.full_name as buyer_name, u.email as buyer_email,
            u.phone as buyer_phone, u.location as buyer_location
            FROM orders o
            JOIN users u ON o.buyer_id = u.user_id
            WHERE o.order_id = ? AND o.seller_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $order_id, $user_id);
} else {
    // Buyer role
    $sql = "SELECT o.order_id, o.total_price, o.status, o.created_at, o.payment_id,
            u.full_name as seller_name, u.email as seller_email,
            u.phone as seller_phone, u.location as seller_location
            FROM orders o
            JOIN users u ON o.seller_id = u.user_id
            WHERE o.order_id = ? AND o.buyer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $order_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode($response);
    $stmt->close();
    exit;
}

$order = $result->fetch_assoc();
$stmt->close();

// Get order items from order_items table
$items_sql = "SELECT oi.product_id, oi.quantity, oi.price,
              p.title as product_name, p.image_url, p.stock_quantity
              FROM order_items oi
              JOIN products p ON oi.product_id = p.product_id
              WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$items = [];
$subtotal = 0;

while ($item = $items_result->fetch_assoc()) {
    // Use getProductImageUrl helper for consistent image paths
    $imagePath = getProductImageUrl($item['image_url'] ?? null);
    
    $item_total = $item['price'] * $item['quantity'];
    $subtotal += $item_total;
    
    $items[] = [
        'product_id' => (int)$item['product_id'],
        'product_name' => $item['product_name'],
        'quantity' => (int)$item['quantity'],
        'price' => (float)$item['price'],
        'total' => $item_total,
        'image_url' => $imagePath
    ];
}
$items_stmt->close();

// Calculate delivery fee (free over R500, otherwise R50)
$delivery_fee = ($subtotal > 0 && $subtotal < 500) ? 50 : 0;
$total = $subtotal + $delivery_fee;

// Build response
$response['success'] = true;
$response['order'] = [
    'order_id' => (int)$order['order_id'],
    'total_price' => (float)$order['total_price'],
    'status' => $order['status'],
    'payment_id' => $order['payment_id'] ?? null,
    'created_at' => date('d M Y, h:i A', strtotime($order['created_at'])),
    'subtotal' => round($subtotal, 2),
    'delivery_fee' => round($delivery_fee, 2),
    'total' => round($total, 2),
    'items' => $items
];

// Add role-specific fields
if ($role === 'seller') {
    $response['order']['other_party_name'] = $order['buyer_name'];
    $response['order']['other_party_email'] = $order['buyer_email'];
    $response['order']['other_party_phone'] = $order['buyer_phone'] ?? '';
    $response['order']['shipping_address'] = $order['buyer_location'] ?? 'Not provided';
} else {
    $response['order']['other_party_name'] = $order['seller_name'];
    $response['order']['other_party_email'] = $order['seller_email'];
    $response['order']['other_party_phone'] = $order['seller_phone'] ?? '';
    $response['order']['shipping_address'] = $order['seller_location'] ?? 'Not provided';
}

echo json_encode($response);
?>