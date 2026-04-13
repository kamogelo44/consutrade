<?php
/*
 * ConsuTrade - Remove from Cart API
 * Author: Kamogelo Phale
 * 
 * Just removes one item from the cart - nothing fancy
 * I made a separate file for this because it felt cleaner
 */

session_start();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;

if ($product_id <= 0 || !isset($_SESSION['cart'][$product_id])) {
    echo json_encode(['success' => false, 'message' => 'Product not found in cart']);
    exit;
}

unset($_SESSION['cart'][$product_id]);

// recalc totals after removal
$subtotal = 0;
$total_items = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}

if ($subtotal > 500) {
    $delivery_fee = 0;
} else {
    $delivery_fee = 50;
}
$total = $subtotal + $delivery_fee;

echo json_encode([
    'success' => true,
    'subtotal' => $subtotal,
    'delivery_fee' => $delivery_fee,
    'total' => $total,
    'cart_count' => $total_items
]);
?>