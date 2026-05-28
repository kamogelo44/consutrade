<?php
/*
 * ConsuTrade - Remove from Cart (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'cart_count' => 0];

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
    $response['cart_count'] = $_SESSION['cart_count'] ?? $cartRepo->getCartCount($user_id);
} else {
    $response['message'] = 'Failed to remove item';
}

echo json_encode($response);
?>