<?php
/*
 * ConsuTrade - Remove from Cart (AJAX)
 * Author: Kamogelo Phale
 * 
 * Removes a product from the user's cart
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$is_logged_in) {
    $response['message'] = 'Please login to remove items';
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

$result = $cartRepo->removeCartItemByProductId($product_id, $user_id);

if ($result) {
    $auth->updateCartCount();
    $response['success'] = true;
    $response['message'] = 'Item removed from cart';
} else {
    $response['message'] = 'Failed to remove item';
}

echo json_encode($response);