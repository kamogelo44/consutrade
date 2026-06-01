<?php
/*
 * ConsuTrade - Add to Cart (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'cart_count' => 0];

if (!$isLoggedIn || !$currentUser instanceof Buyer) {
    $response['message'] = 'Please login as a buyer to add items to cart';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$productId = (int) ($input['product_id'] ?? 0);

if ($productId <= 0) {
    $response['message'] = 'Invalid product';
    echo json_encode($response);
    exit;
}

$product = $productRepo->getProductObject($productId);

if (!$product) {
    $response['message'] = 'Product not found';
    echo json_encode($response);
    exit;
}

if (!$product->isAvailable()) {
    $response['message'] = 'This product is not available for purchase.';
    echo json_encode($response);
    exit;
}

$availableStock = $product->getStockQuantity();
$userId = $currentUser->getUserId();

$cartItems = $cartRepo->getCartItems($userId);
$existingItem = null;

foreach ($cartItems as $item) {
    if ($item['product_id'] == $productId) {
        $existingItem = $item;
        break;
    }
}

if ($existingItem) {
    $newQuantity = $existingItem['quantity'] + 1;

    if ($newQuantity > $availableStock) {
        $response['message'] = 'Cannot add more. Only ' . $availableStock
            . ' available. You have ' . $existingItem['quantity'] . ' in cart.';
        echo json_encode($response);
        exit;
    }

    $success = $cartRepo->updateCartQuantity($existingItem['cart_id'], $userId, $newQuantity);
    $response['message'] = 'Quantity updated!';
} else {
    if ($availableStock < 1) {
        $response['message'] = 'Out of stock.';
        echo json_encode($response);
        exit;
    }
    $success = $cartRepo->addItem($userId, $productId, 1);
    $response['message'] = 'Item added to cart';
}

if ($success) {
    $auth->refreshCartCount();
    $response['success'] = true;
    $response['cart_count'] = $_SESSION['cart_count'] ?? 0;
} else {
    $response['message'] = 'Failed to add item to cart';
}

echo json_encode($response);
