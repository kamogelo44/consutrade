<?php
/*
 * ConsuTrade - Remove from Cart (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'cart_count' => 0];

// Check authentication using globals from init.php
if (!$isLoggedIn || !$currentUser instanceof Buyer) {
    $response['message'] = 'Please login to remove items from cart';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$productId = (int) ($input['product_id'] ?? 0);
$userId = $currentUser->getUserId();

if ($productId <= 0) {
    $response['message'] = 'Invalid product';
    echo json_encode($response);
    exit;
}

// Use cartRepo from init.php
$result = $cartRepo->removeCartItemByProductId($productId, $userId);

if ($result) {
    $cartCount = $cartRepo->getCartCount($userId);
    $auth->refreshCartCount();

    $response['success'] = true;
    $response['message'] = 'Item removed from cart';
    $response['cart_count'] = $cartCount;
} else {
    $response['message'] = 'Failed to remove item from cart';
}

echo json_encode($response);
