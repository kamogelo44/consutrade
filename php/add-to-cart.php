<?php
/*
 * ConsuTrade - Add to Cart
 * Author: Kamogelo Phale
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $response['message'] = 'Please login to add items to cart';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

if ($product_id <= 0) {
    $response['message'] = 'Invalid product';
    echo json_encode($response);
    exit;
}

// Check if product exists and get seller info
$product_sql = "SELECT seller_id, title FROM products WHERE product_id = ? AND status = 'active'";
$product_stmt = $conn->prepare($product_sql);
$product_stmt->bind_param('i', $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();

if ($product_result->num_rows === 0) {
    $response['message'] = 'Product not found';
    echo json_encode($response);
    $product_stmt->close();
    $conn->close();
    exit;
}

$product = $product_result->fetch_assoc();
$product_stmt->close();

// Prevent sellers from buying their own products
if ($user_role === 'seller' && $product['seller_id'] == $user_id) {
    $response['message'] = 'You cannot purchase your own products';
    echo json_encode($response);
    $conn->close();
    exit;
}

// Check if item already in cart
$check_sql = "SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('ii', $user_id, $product_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // Update existing cart item
    $cart_item = $check_result->fetch_assoc();
    $new_quantity = $cart_item['quantity'] + $quantity;
    $update_sql = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('ii', $new_quantity, $cart_item['cart_id']);
    
    if ($update_stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Cart updated successfully';
    } else {
        $response['message'] = 'Failed to update cart';
    }
    $update_stmt->close();
} else {
    // Add new item to cart
    $insert_sql = "INSERT INTO cart (user_id, product_id, quantity, added_at) VALUES (?, ?, ?, NOW())";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param('iii', $user_id, $product_id, $quantity);
    
    if ($insert_stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Item added to cart';
    } else {
        $response['message'] = 'Failed to add item to cart';
    }
    $insert_stmt->close();
}

$check_stmt->close();
$conn->close();

echo json_encode($response);
exit;
?>