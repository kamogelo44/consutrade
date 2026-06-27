<?php
/*
 * ConsuTrade - Update Cart Quantity (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$isLoggedIn || !$currentUser instanceof Buyer) {
    $response['message'] = 'Please login to update cart';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cartId = (int) ($input['cart_id'] ?? 0);
$quantity = (int) ($input['quantity'] ?? 1);
$userId = $currentUser->getUserId();

if ($cartId <= 0) {
    $response['message'] = 'Invalid cart item';
    echo json_encode($response);
    exit;
}

$quantity = max(1, min(99, $quantity));

// Get product ID from cart
$productStmt = $conn->prepare("SELECT product_id FROM cart WHERE cart_id = ? AND user_id = ?");
$productStmt->bind_param('ii', $cartId, $userId);
$productStmt->execute();
$productResult = $productStmt->get_result();
$productRow = $productResult->fetch_assoc();
$productStmt->close();

if (!$productRow) {
    $response['message'] = 'Cart item not found';
    echo json_encode($response);
    exit;
}

// Use ProductService for product lookup
$product = $productService->findById($productRow['product_id']);

if (!$product) {
    $response['message'] = 'Product not found';
    echo json_encode($response);
    exit;
}

// Use domain model for stock validation
if (!$product->canDecreaseStock($quantity)) {
    $response['message'] = 'Only ' . $product->getStockQuantity() . ' available in stock.';
    echo json_encode($response);
    exit;
}

// CartRepository for data operations
$result = $cartRepo->updateQuantity($cartId, $userId, $quantity);

if ($result) {
    // Get fresh cart data and return it
    $freshCartItems = $cartRepo->findByUser($userId);

    // Use CartService for totals
    $freshTotals = $cartService->calculateTotals($freshCartItems);

    $itemCount = 0;
    foreach ($freshCartItems as $item) {
        $itemCount += $item['quantity'];
        // Add image URL using ProductService
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
