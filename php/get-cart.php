<?php
/*
 * ConsuTrade - Get Cart Contents
 * Author: Kamogelo Phale
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'items' => [], 'item_count' => 0, 'subtotal' => 0, 'delivery_fee' => 0, 'total' => 0];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];

// Add id_verified to the query
$sql = "SELECT c.cart_id, c.product_id, c.quantity, 
        p.title as product_name, 
        p.price, 
        p.image_url,
        u.full_name as seller_name,
        u.id_verified as is_verified
        FROM cart c
        LEFT JOIN products p ON c.product_id = p.product_id
        LEFT JOIN users u ON p.seller_id = u.user_id
        WHERE c.user_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode($response);
    exit;
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$subtotal = 0;
$item_count = 0;

while ($row = $result->fetch_assoc()) {
    if (!$row['product_name']) {
        continue;
    }
    
    $imagePath = $row['image_url'];
    if (empty($imagePath)) {
        $imagePath = 'images/default-product.png';
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
        'image' => $imagePath,
        'seller_name' => $row['seller_name'] ?? 'Unknown Seller',
        'is_verified' => $row['is_verified'] == 1  // Add this line
    ];
}

$delivery_fee = ($subtotal > 0 && $subtotal < 500) ? 50 : 0;
$total = $subtotal + $delivery_fee;

$response['success'] = true;
$response['item_count'] = $item_count;
$response['subtotal'] = number_format($subtotal, 2);
$response['delivery_fee'] = number_format($delivery_fee, 2);
$response['total'] = number_format($total, 2);

$stmt->close();
$conn->close();

echo json_encode($response);
exit;
?>