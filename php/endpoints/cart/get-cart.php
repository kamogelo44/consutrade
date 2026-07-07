<?php
/*
 * ConsuTrade - Get Cart Contents (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = [
    'success' => false,
    'items' => [],
    'item_count' => 0,
    'subtotal' => 0,
    'delivery_fee' => 0,
    'total' => 0
];

if (!$isLoggedIn) {
    echo json_encode($response);
    exit;
}

if (!$currentUser->hasRole('buyer')) {
    echo json_encode($response);
    exit;
}

$userId = $currentUser->getUserId();

// CartRepository for data retrieval
$cartItems = $cartRepo->findByUser($userId);

// Use CartService for totals calculation
$totals = $cartService->calculateTotals($cartItems);

$items = [];
$itemCount = 0;

foreach ($cartItems as $item) {
    $itemCount += $item['quantity'];

    // Use ProductService for image URL
    $imageUrl = $productService->getImageUrl($item['image_url']);

    $items[] = [
        'cart_id' => (int) $item['cart_id'],
        'product_id' => (int) $item['product_id'],
        'product_name' => $item['title'],
        'price' => (float) $item['price'],
        'quantity' => (int) $item['quantity'],
        'image' => $imageUrl,
        'seller_name' => $item['seller_name'] ?? 'Unknown Seller',
        'stock_quantity' => (int) ($item['stock_quantity'] ?? 1),
        'is_verified' => (bool) ($item['is_verified'] ?? false)
    ];
}

$response['success'] = true;
$response['items'] = $items;
$response['item_count'] = $itemCount;
$response['subtotal'] = number_format($totals['subtotal'], 2);
$response['delivery_fee'] = number_format($totals['delivery_fee'], 2);
$response['total'] = number_format($totals['total'], 2);

echo json_encode($response);
