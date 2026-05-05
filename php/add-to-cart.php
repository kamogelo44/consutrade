<?php
/*
 * ConsuTrade - Add to Cart (AJAX)
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$is_logged_in) {
    $response['message'] = 'Please login to add items to cart';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;

if ($product_id <= 0) {
    $response['message'] = 'Invalid product';
    echo json_encode($response);
    exit;
}

$user_id = $current_user_id;

// FIRST: Get product stock quantity
$stock_sql = "SELECT stock_quantity FROM products WHERE product_id = ? AND status = 'active'";
$stock_stmt = $conn->prepare($stock_sql);
$stock_stmt->bind_param('i', $product_id);
$stock_stmt->execute();
$stock_result = $stock_stmt->get_result();
$stock_row = $stock_result->fetch_assoc();
$available_stock = (int)($stock_row['stock_quantity'] ?? 0);
$stock_stmt->close();

if ($available_stock <= 0) {
    $response['message'] = 'This product is out of stock.';
    echo json_encode($response);
    exit;
}

// Check if product already in cart
$stmt = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->bind_param('ii', $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Update quantity (+1)
    $new_qty = $row['quantity'] + 1;
    
    // Check if new quantity exceeds available stock
    if ($new_qty > $available_stock) {
        $response['message'] = 'Cannot add more. Only ' . $available_stock . ' available in stock. You already have ' . $row['quantity'] . ' in cart.';
        echo json_encode($response);
        exit;
    }
    
    $updateStmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
    $updateStmt->bind_param('ii', $new_qty, $row['cart_id']);
    $success = $updateStmt->execute();
    $updateStmt->close();
    $response['message'] = 'Quantity updated!';
} else {
    // Add new item with quantity 1
    if (1 > $available_stock) {
        $response['message'] = 'This product is out of stock.';
        echo json_encode($response);
        exit;
    }
    
    $insertStmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
    $insertStmt->bind_param('ii', $user_id, $product_id);
    $success = $insertStmt->execute();
    $insertStmt->close();
    $response['message'] = 'Item added to cart';
}

$stmt->close();

if ($success) {
    updateCartCount();
    $response['success'] = true;
} else {
    $response['message'] = 'Failed to add item';
}

echo json_encode($response);
?>