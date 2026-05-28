<?php
/*
 * ConsuTrade - Add to Cart (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'cart_count' => 0];

if (!$is_logged_in) {
    $response['message'] = 'Please login to add items to cart';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$user_id = $current_user_id;

if ($product_id <= 0) {
    $response['message'] = 'Invalid product';
    echo json_encode($response);
    exit;
}

// Get product stock using ProductRepository
$available_stock = $productRepo->getProductStock($product_id);

if ($available_stock <= 0) {
    $response['message'] = 'This product is out of stock.';
    echo json_encode($response);
    exit;
}

// Check if product already in cart
$cart_items = $cartRepo->getCartItems($user_id);
$existing_item = null;

foreach ($cart_items as $item) {
    if ($item['product_id'] == $product_id) {
        $existing_item = $item;
        break;
    }
}

if ($existing_item) {
    $new_qty = $existing_item['quantity'] + 1;
    
    if ($new_qty > $available_stock) {
        $response['message'] = 'Cannot add more. Only ' . $available_stock 
            . ' available. You have ' . $existing_item['quantity'] . ' in cart.';
        echo json_encode($response);
        exit;
    }
    
    $success = $cartRepo->updateCartQuantity($existing_item['cart_id'], $user_id, $new_qty);
    $response['message'] = 'Quantity updated!';
} else {
    $success = $cartRepo->addItem($user_id, $product_id, 1);
    $response['message'] = 'Item added to cart';
}

if ($success) {
    // Update session cart count
    $auth->updateCartCount();
    
    // Get fresh cart count from session (not another DB query)
    $response['success'] = true;
    $response['cart_count'] = $_SESSION['cart_count'] ?? $cartRepo->getCartCount($user_id);
} else {
    $response['message'] = 'Failed to add item';
}

echo json_encode($response);
?>