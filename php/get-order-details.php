<?php
/*
 * ConsuTrade - Get Order Details
 * Author: Kamogelo Phale
 */

session_start();
require_once 'config.php';
require_once 'helpers.php';

header('Content-Type: application/json');

$response = ['success' => false, 'order' => null];

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode($response);
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($order_id <= 0) {
    echo json_encode($response);
    exit;
}

// Get order basic info
if ($role === 'seller') {
    $sql = "SELECT o.order_id, o.total_price, o.status, o.created_at,
            u.full_name as buyer_name, u.email as buyer_email,
            u.phone as buyer_phone, u.location as buyer_location
            FROM orders o
            JOIN users u ON o.buyer_id = u.user_id
            WHERE o.order_id = ? AND o.seller_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $order_id, $user_id);
} else {
    // Buyer role
    $sql = "SELECT o.order_id, o.total_price, o.status, o.created_at,
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
    $conn->close();
    exit;
}

$order = $result->fetch_assoc();
$stmt->close();

// Get order items from order_items table
$items_sql = "SELECT oi.product_id, oi.quantity, oi.price,
              p.title as product_name, p.image_url
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
    $imagePath = $item['image_url'] ?? '';
    if (empty($imagePath)) {
        $imagePath = '/www/consutrade/images/default-product.png';
    } elseif (!str_starts_with($imagePath, 'http') && !str_starts_with($imagePath, '/')) {
        $imagePath = '/www/consutrade/' . $imagePath;
    }
    
    $item_total = $item['price'] * $item['quantity'];
    $subtotal += $item_total;
    
    $items[] = [
        'product_id' => $item['product_id'],
        'product_name' => $item['product_name'],
        'quantity' => $item['quantity'],
        'price' => (float)$item['price'],
        'total' => $item_total,
        'image_url' => $imagePath
    ];
}
$items_stmt->close();

// Calculate delivery fee (sample logic - adjust as needed)
$delivery_fee = ($subtotal > 0 && $subtotal < 500) ? 50 : 0;
$total = $subtotal + $delivery_fee;

$response['success'] = true;
$response['order'] = [
    'order_id' => $order['order_id'],
    'total_price' => (float)$order['total_price'],
    'status' => $order['status'],
    'created_at' => date('d M Y, h:i A', strtotime($order['created_at'])),
    'subtotal' => $subtotal,
    'delivery_fee' => $delivery_fee,
    'total' => $total,
    'items' => $items
];

// Add role-specific fields
if ($role === 'seller') {
    $response['order']['buyer_name'] = $order['buyer_name'];
    $response['order']['buyer_email'] = $order['buyer_email'];
    $response['order']['buyer_phone'] = $order['buyer_phone'] ?? '';
    $response['order']['shipping_address'] = $order['buyer_location'] ?? '';
} else {
    $response['order']['seller_name'] = $order['seller_name'];
    $response['order']['seller_email'] = $order['seller_email'];
    $response['order']['seller_phone'] = $order['seller_phone'] ?? '';
    $response['order']['shipping_address'] = $order['seller_location'] ?? '';
}

$conn->close();

echo json_encode($response);
?>