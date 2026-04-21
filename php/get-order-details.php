<?php
/*
 * ConsuTrade - Get Order Details
 * Author: Kamogelo Phale
 */

session_start();
require_once 'config.php';

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

// Get order details - works for both buyers and sellers
if ($role === 'seller') {
    $sql = "SELECT o.order_id, o.total_price, o.status, o.created_at, o.product_id, o.quantity,
            u.full_name as buyer_name, u.email as buyer_email,
            p.title as product_name, p.image_url
            FROM orders o
            JOIN users u ON o.buyer_id = u.user_id
            JOIN products p ON o.product_id = p.product_id
            WHERE o.order_id = ? AND o.seller_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $order_id, $user_id);
} else {
    // Buyer role
    $sql = "SELECT o.order_id, o.total_price, o.status, o.created_at, o.product_id, o.quantity,
            u.full_name as seller_name, u.email as seller_email,
            p.title as product_name, p.image_url
            FROM orders o
            JOIN users u ON o.seller_id = u.user_id
            JOIN products p ON o.product_id = p.product_id
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

// Fix image path
$imagePath = $order['image_url'] ?? '';
if (empty($imagePath)) {
    $imagePath = '/www/consutrade/images/default-product.png';
} elseif (!str_starts_with($imagePath, 'http') && !str_starts_with($imagePath, '/')) {
    $imagePath = '/www/consutrade/' . $imagePath;
}

$response['success'] = true;
$response['order'] = [
    'order_id' => $order['order_id'],
    'total_price' => (float)$order['total_price'],
    'status' => $order['status'],
    'created_at' => date('d M Y, h:i A', strtotime($order['created_at'])),
    'product_id' => $order['product_id'],
    'product_name' => $order['product_name'],
    'quantity' => $order['quantity'],
    'image' => $imagePath,
    'seller_name' => $role === 'buyer' ? ($order['seller_name'] ?? '') : ($_SESSION['full_name'] ?? ''),
    'buyer_name' => $role === 'seller' ? ($order['buyer_name'] ?? '') : ($_SESSION['full_name'] ?? '')
];

$conn->close();

echo json_encode($response);
?>