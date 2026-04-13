<?php
/*
 * ConsuTrade - Add to Cart API
 * Author: Kamogelo Phale
 * 
 * This file adds products to the shopping cart session
 * I spent way too long figuring out how sessions actually work
 */

session_start();

// had to add this check because first time users were getting errors
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// trying to get the data from my javascript fetch request
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    // incase someone submits the form the old fashioned way
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
} else {
    $product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
    $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
    $product_name = isset($data['product_name']) ? $data['product_name'] : '';
    $product_price = isset($data['product_price']) ? (float)$data['product_price'] : 0;
}

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

// if the product is already sitting in the cart just add more to the quantity
if (!isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id] = [
        'id' => $product_id,
        'name' => $product_name,
        'price' => $product_price,
        'quantity' => $quantity
    ];
} else {
    $_SESSION['cart'][$product_id]['quantity'] += $quantity;
}

// counting total items for the little red badge on the cart icon
$total_items = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_items += $item['quantity'];
}

echo json_encode([
    'success' => true,
    'cart_count' => $total_items,
    'message' => 'Product added to cart'
]);
?>