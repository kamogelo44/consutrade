<?php
/*
 * ConsuTrade - Update Cart Quantity (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

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

$product = $productRepo->findById($productRow['product_id']);

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
    $freshTotals = $cartRepo->calculateTotals($freshCartItems);
    $items = [];
    $itemCount = 0;

    foreach ($freshCartItems as $item) {
        $itemCount += $item['quantity'];
        $imageUrl = $productRepo->getImageUrl($item['image_url']);

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
    $response['message'] = 'Cart updated';
    $response['cart'] = [
        'items' => $items,
        'item_count' => $itemCount,
        'subtotal' => number_format($freshTotals['subtotal'], 2),
        'delivery_fee' => number_format($freshTotals['delivery_fee'], 2),
        'total' => number_format($freshTotals['total'], 2)
    ];
} else {
    $response['message'] = 'Failed to update cart';
}

echo json_encode($response);
