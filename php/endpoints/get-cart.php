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

if (!$is_logged_in || !$current_user_id) {
    echo json_encode($response);
    exit;
}

// Get cart items from repository
$cartItems = $cartRepo->getCartItems($current_user_id);

// Calculate totals
$totals = $cartRepo->calculateCartTotals($cartItems);

// Format items for JSON response
$items = [];
$item_count = 0;

foreach ($cartItems as $item) {
    $item_count += $item['quantity'];

    $items[] = [
        'cart_id'       => (int) $item['cart_id'],
        'product_id'    => (int) $item['product_id'],
        'product_name'  => $item['title'],
        'price'         => (float) $item['price'],
        'quantity'      => (int) $item['quantity'],
        'image'         => $item['image_url'],
        'seller_name'   => $item['seller_name'] ?? 'Unknown Seller',
        'stock_quantity' => (int) ($item['stock_quantity'] ?? 1),
        'is_verified'   => (bool) ($item['id_verified'] ?? false)
    ];
}

$response['success']      = true;
$response['items']        = $items;
$response['item_count']   = $item_count;
$response['subtotal']     = number_format($totals['subtotal'], 2);
$response['delivery_fee'] = number_format($totals['delivery_fee'], 2);
$response['total']        = number_format($totals['total'], 2);

echo json_encode($response);