<?php
/*
 * ConsuTrade - Get Cart Contents (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns current user's cart items as JSON for frontend
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'items' => [], 'item_count' => 0, 'subtotal' => 0, 'delivery_fee' => 0, 'total' => 0];

// Check if user is logged in using centralized auth
if (!$is_logged_in || !$current_user_id) {
    echo json_encode($response);
    exit;
}

$user_id = $current_user_id;

// Get cart items with stock information
$sql = "SELECT c.cart_id, c.product_id, c.quantity, 
        p.title as product_name, 
        p.price, 
        p.image_url,
        p.stock_quantity,
        u.full_name as seller_name,
        u.id_verified as is_verified
        FROM cart c
        LEFT JOIN products p ON c.product_id = p.product_id
        LEFT JOIN users u ON p.seller_id = u.user_id
        WHERE c.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$subtotal = 0;
$item_count = 0;
$items = [];

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
    
    $items[] = [
        'cart_id' => (int)$row['cart_id'],
        'product_id' => (int)$row['product_id'],
        'product_name' => $row['product_name'],
        'price' => (float)$row['price'],
        'quantity' => (int)$row['quantity'],
        'image' => $imagePath,
        'seller_name' => $row['seller_name'] ?? 'Unknown Seller',
        'stock_quantity' => (int)($row['stock_quantity'] ?? 1),
        'is_verified' => (bool)$row['is_verified']
    ];
}

$stmt->close();

$delivery_fee = ($subtotal > 0 && $subtotal < 500) ? 50 : 0;
$total = $subtotal + $delivery_fee;

$response['success'] = true;
$response['items'] = $items;
$response['item_count'] = $item_count;
$response['subtotal'] = number_format($subtotal, 2);
$response['delivery_fee'] = number_format($delivery_fee, 2);
$response['total'] = number_format($total, 2);

echo json_encode($response);
?>