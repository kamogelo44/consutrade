<?php
/*
 * ConsuTrade - Add to Cart (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'cart_count' => 0];

// Check authentication using globals from init.php
if (!$isLoggedIn || !$currentUser instanceof Buyer) {
    $response['message'] = 'Please login to add items to cart';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$productId = (int) ($input['product_id'] ?? 0);
$quantity = (int) ($input['quantity'] ?? 1);
$userId = $currentUser->getUserId();

if ($productId <= 0) {
    $response['message'] = 'Invalid product';
    echo json_encode($response);
    exit;
}

// Use productRepo from init.php
$product = $productRepo->getProductObject($productId);

if (!$product || $product->getStatus() !== 'active') {
    $response['message'] = 'Product not available';
    echo json_encode($response);
    exit;
}

if (!$product->canDecreaseStock($quantity)) {
    $response['message'] = 'Only ' . $product->getStockQuantity() . ' available in stock.';
    echo json_encode($response);
    exit;
}

// Check if item already in cart using cartRepo from init.php
$existingItem = $cartRepo->getCartItemByProduct($userId, $productId);

if ($existingItem) {
    $newQuantity = $existingItem['quantity'] + $quantity;
    $result = $cartRepo->updateCartQuantity($existingItem['cart_id'], $userId, $newQuantity);
} else {
    $result = $cartRepo->addItem($userId, $productId, $quantity);
}

if ($result) {
    $cartCount = $cartRepo->getCartCount($userId);
    $auth->refreshCartCount();

    $response['success'] = true;
    $response['message'] = 'Item added to cart';
    $response['cart_count'] = $cartCount;
} else {
    $response['message'] = 'Failed to add item to cart';
}

echo json_encode($response);
