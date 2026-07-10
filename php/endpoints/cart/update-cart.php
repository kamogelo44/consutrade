<?php
/*
 * ConsuTrade - Update Cart Quantity (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$isLoggedIn) {
    $response['message'] = 'Please login to update cart';
    echo json_encode($response);
    exit;
}

if (!$currentUser->hasRole('buyer')) {
    $response['message'] = 'You need a buyer account to update cart';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cartId = (int) ($input['cart_id'] ?? 0);
$quantity = max(1, min(99, (int) ($input['quantity'] ?? 1)));
$userId = $currentUser->getUserId();

if ($cartId <= 0) {
    $response['message'] = 'Invalid cart item';
    echo json_encode($response);
    exit;
}

$productId = $cartRepo->findProductIdByCartId($cartId, $userId);

if (!$productId) {
    $response['message'] = 'Cart item not found';
    echo json_encode($response);
    exit;
}

$product = $productService->findById($productId);

if (!$product) {
    $response['message'] = 'Product not found';
    echo json_encode($response);
    exit;
}

if (!$product->canDecreaseStock($quantity)) {
    $response['message'] = 'Only ' . $product->getStockQuantity() . ' available in stock.';
    echo json_encode($response);
    exit;
}

$result = $cartRepo->updateQuantity($cartId, $userId, $quantity);

if ($result) {
    $freshCartItems = $cartRepo->findByUser($userId);
    $freshTotals = $cartService->calculateTotals($freshCartItems);

    $itemCount = 0;
    foreach ($freshCartItems as $item) {
        $itemCount += $item['quantity'];
        $item['image'] = $productService->getImageUrl($item['image_url']);
    }

    $response['success'] = true;
    $response['message'] = 'Cart updated';
    $response['cart'] = [
        'items' => $freshCartItems,
        'item_count' => $itemCount,
        'subtotal' => (float) $freshTotals['subtotal'],
        'delivery_fee' => (float) $freshTotals['delivery_fee'],
        'total' => (float) $freshTotals['total']
    ];
} else {
    $response['message'] = 'Failed to update cart';
}

echo json_encode($response);
