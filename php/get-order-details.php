<?php
/*
 * ConsuTrade - Get Order Details
 * Author: Kamogelo Phale
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'order' => null];

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'seller') {
    echo json_encode($response);
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$seller_id = $_SESSION['user_id'];

if ($order_id <= 0) {
    echo json_encode($response);
    exit;
}

// Get order details
$sql = "SELECT o.order_id, o.total_price, o.delivery_fee, o.status, o.created_at, o.shipping_address,
        u.full_name as buyer_name, u.email as buyer_email
        FROM orders o
        JOIN users u ON o.buyer_id = u.user_id
        WHERE o.order_id = ? AND o.seller_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $order_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode($response);
    $stmt->close();
    $conn->close();
    exit;
}

$order = $result->fetch_assoc();

// Get order items
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
while ($row = $items_result->fetch_assoc()) {
    $imagePath = $row['image_url'];
    if (empty($imagePath)) {
        $imagePath = 'images/default-product.jpg';
    }
    
    $items[] = [
        'product_id' => $row['product_id'],
        'product_name' => $row['product_name'],
        'quantity' => $row['quantity'],
        'price' => (float)$row['price'],
        'image' => $imagePath
    ];
}

$response['success'] = true;
$response['order'] = [
    'order_id' => $order['order_id'],
    'total' => (float)$order['total_price'],
    'delivery_fee' => (float)($order['delivery_fee'] ?? 0),
    'subtotal' => (float)($order['total_price'] - ($order['delivery_fee'] ?? 0)),
    'status' => $order['status'],
    'created_at' => date('d M Y, h:i A', strtotime($order['created_at'])),
    'shipping_address' => $order['shipping_address'],
    'buyer_name' => $order['buyer_name'],
    'buyer_email' => $order['buyer_email'],
    'items' => $items
];

$stmt->close();
$items_stmt->close();
$conn->close();

echo json_encode($response);
?>