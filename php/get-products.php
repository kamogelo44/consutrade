<?php
/*
 * ConsuTrade - Get Cart Contents
 * Author: Kamogelo Phale
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'items' => [], 'item_count' => 0, 'subtotal' => 0, 'delivery_fee' => 0, 'total' => 0];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];

// REMOVED p.quantity since it doesn't exist in your products table
$sql = "SELECT c.cart_id, c.product_id, c.quantity, 
        p.title as product_name, 
        p.price, 
        p.image_url,
        u.full_name as seller_name
        FROM cart c
        LEFT JOIN products p ON c.product_id = p.product_id
        LEFT JOIN users u ON p.seller_id = u.user_id
        WHERE c.user_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    $response['message'] = 'SQL Error: ' . $conn->error;
    echo json_encode($response);
    exit;
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$subtotal = 0;
$item_count = 0;

while ($row = $result->fetch_assoc()) {
    // Skip if product doesn't exist anymore
    if (!$row['product_name']) {
        continue;
    }
    
    // Fix image path
    $imagePath = $row['image_url'];
    if ($imagePath && strpos($imagePath, '/www/consutrade/') === 0) {
        $imagePath = substr($imagePath, strlen('/www/consutrade/'));
    }
    
    if (empty($imagePath)) {
        $imagePath = 'images/default-product.jpg';
    }
    
    $item_total = $row['price'] * $row['quantity'];
    $subtotal += $item_total;
    $item_count += $row['quantity'];
    
    $response['items'][] = [
        'cart_id' => $row['cart_id'],
        'product_id' => $row['product_id'],
        'product_name' => $row['product_name'],
        'price' => (float)$row['price'],
        'quantity' => (int)$row['quantity'],
        'stock' => 999, // Default large number since no stock column
        'image' => $imagePath,
        'seller_name' => $row['seller_name'] ?? 'Unknown Seller'
    ];
}

// Delivery fee: Free for orders over R500, otherwise R50
$delivery_fee = ($subtotal > 0 && $subtotal < 500) ? 50 : 0;
$total = $subtotal + $delivery_fee;

$response['success'] = true;
$response['item_count'] = $item_count;
$response['subtotal'] = number_format($subtotal, 2);
$response['delivery_fee'] = number_format($delivery_fee, 2);
$response['total'] = number_format($total, 2);

$stmt->close();
$conn->close();

// Clean any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

echo json_encode($response);
exit;
?>