<?php
/*
 * ConsuTrade - Delete Product (AJAX)
 * Author: Kamogelo Phale
 * 
 * Soft deletes a product (sets status to 'deleted') and removes image files
 * Supports both sellers (their own products) and admins (any product)
 */

require_once dirname(__DIR__) . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in
if (!$is_logged_in) {
    $response['message'] = 'Unauthorized. Please login.';
    echo json_encode($response);
    exit;
}

$role = $current_user['role'];
$user_id = $current_user_id;

$input = json_decode(file_get_contents('php://input'), true);
$product_id = (int)($input['product_id'] ?? 0);

if ($product_id <= 0) {
    $response['message'] = 'Invalid product ID';
    echo json_encode($response);
    exit;
}

// Determine if user has permission to delete this product
if ($role === 'seller') {
    // Seller can only delete their own products
    $result = deleteSellerProduct($conn, $product_id, $user_id);
} elseif ($role === 'admin') {
    // Admin can delete any product
    // First get the seller_id to verify product exists
    $check_sql = "SELECT seller_id, image_url FROM products WHERE product_id = ? AND status != 'deleted'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        $response['message'] = 'Product not found';
        echo json_encode($response);
        $check_stmt->close();
        exit;
    }
    
    $product = $check_result->fetch_assoc();
    $check_stmt->close();
    
    // Admin soft delete
    $delete_sql = "UPDATE products SET status = 'deleted' WHERE product_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param('i', $product_id);
    
    if ($delete_stmt->execute() && $delete_stmt->affected_rows > 0) {
        if (!empty($product['image_url'])) {
            deleteProductImage($product['image_url']);
        }
        $response['success'] = true;
        $response['message'] = 'Product deleted successfully';
    } else {
        $response['message'] = 'Failed to delete product';
    }
    $delete_stmt->close();
    
    echo json_encode($response);
    exit;
} else {
    $response['message'] = 'Unauthorized. You do not have permission to delete products.';
    echo json_encode($response);
    exit;
}

// Return result for seller
if ($result['success']) {
    $response['success'] = true;
    $response['message'] = $result['message'];
} else {
    $response['message'] = $result['message'];
}

echo json_encode($response);
?>