<?php
/*
 * ConsuTrade - Update Cart Quantity (AJAX)
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$is_logged_in) {
    $response['message'] = 'Please login to update cart';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cart_id = isset($input['cart_id']) ? (int)$input['cart_id'] : 0;
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;
$user_id = $current_user_id;

if ($cart_id <= 0) {
    $response['message'] = 'Invalid cart item';
    echo json_encode($response);
    exit;
}

if ($quantity < 1) $quantity = 1;
if ($quantity > 99) $quantity = 99;

// Get product_id and current stock from cart
$product_stmt = $conn->prepare("SELECT c.product_id, p.stock_quantity 
                                FROM cart c 
                                JOIN products p ON c.product_id = p.product_id 
                                WHERE c.cart_id = ? AND c.user_id = ?");
$product_stmt->bind_param('ii', $cart_id, $user_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();
$product_row = $product_result->fetch_assoc();
$product_stmt->close();

if (!$product_row) {
    $response['message'] = 'Cart item not found';
    echo json_encode($response);
    exit;
}

$available_stock = (int)($product_row['stock_quantity'] ?? 0);

// Check if requested quantity exceeds available stock
if ($quantity > $available_stock) {
    $response['message'] = 'Only ' . $available_stock . ' available in stock.';
    echo json_encode($response);
    exit;
}

$result = updateCartQuantity($conn, $cart_id, $user_id, $quantity);

if ($result) {
    updateCartCount();
    $response['success'] = true;
    $response['message'] = 'Cart updated';
} else {
    $response['message'] = 'Failed to update cart';
}

echo json_encode($response);
?>