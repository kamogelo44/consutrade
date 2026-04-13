<?php
/*
 * ConsuTrade - Get Cart API
 * Author: Kamogelo Phale
 * 
 * Returns everything in the cart as JSON so my javascript can use it
 * I learned how to do this from a youtube tutorial
 */

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart_items = [];
$subtotal = 0;

// looping through each item to calculate totals - learned this from w3schools
foreach ($_SESSION['cart'] as $item) {
    $item_total = $item['price'] * $item['quantity'];
    $subtotal += $item_total;
    
    $cart_items[] = [
        'id' => $item['id'],
        'name' => $item['name'],
        'price' => $item['price'],
        'quantity' => $item['quantity'],
        'total' => $item_total
    ];
}

// delivery is free if you spend over R500 - my mom said this is how most shops do it
if ($subtotal > 500) {
    $delivery_fee = 0;
} else {
    $delivery_fee = 50;
}
$total = $subtotal + $delivery_fee;

echo json_encode([
    'success' => true,
    'items' => $cart_items,
    'subtotal' => $subtotal,
    'delivery_fee' => $delivery_fee,
    'total' => $total,
    'item_count' => count($cart_items)
]);
?>