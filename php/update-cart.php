<?php
/*
 * ConsuTrade - Update Cart API
 * Author: Kamogelo Phale
 * 
 * Updates how many of a product someone wants to buy
 * I had to test this multiple times because the math kept breaking
 */

session_start();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
$quantity = isset($data['quantity']) ? (int)$data['quantity'] : 0;

if ($product_id <= 0 || !isset($_SESSION['cart'][$product_id])) {
    echo json_encode(['success' => false, 'message' => 'Product not found in cart']);
    exit;
}

// if someone sets quantity to 0 just remove the item completely
if ($quantity <= 0) {
    unset($_SESSION['cart'][$product_id]);
} else {
    $_SESSION['cart'][$product_id]['quantity'] = $quantity;
}

// recalculating everything again because numbers changed
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
    'cart_count' => $total_items,
    'item_total' => isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['price'] * $_SESSION['cart'][$product_id]['quantity'] : 0
]);
?>