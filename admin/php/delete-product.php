<?php
/*
 * ConsuTrade - Delete Product (AJAX)
 * Author: Kamogelo Phale
 * 
 * Soft deletes a product (sets status to 'deleted') and removes image files
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in
if (!isLoggedIn()) {
    $response['message'] = 'Unauthorized. Please login.';
    echo json_encode($response);
    exit;
}

$role = $_SESSION['role'] ?? '';
$user_id = $current_user_id;

$input = json_decode(file_get_contents('php://input'), true);
$product_id = (int)($input['product_id'] ?? 0);

if ($product_id <= 0) {
    $response['message'] = 'Invalid product ID';
    echo json_encode($response);
    exit;
}

if ($role === 'seller') {
    // Seller can only delete their own products
    $result = deleteSellerProduct($conn, $product_id, $user_id);
    $response['success'] = $result['success'];
    $response['message'] = $result['message'];
} elseif ($role === 'admin') {
    // Admin can delete any product
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
    
    // Delete gallery images first
    $gallery_sql = "SELECT image_url FROM product_images WHERE product_id = ?";
    $gallery_stmt = $conn->prepare($gallery_sql);
    $gallery_stmt->bind_param('i', $product_id);
    $gallery_stmt->execute();
    $gallery_result = $gallery_stmt->get_result();
    while ($img = $gallery_result->fetch_assoc()) {
        deleteProductImage($img['image_url']);
    }
    $gallery_stmt->close();
    
    // Delete gallery records
    $del_gallery = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
    $del_gallery->bind_param('i', $product_id);
    $del_gallery->execute();
    $del_gallery->close();
    
    // Soft delete product
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
} else {
    $response['message'] = 'Unauthorized. You do not have permission to delete products.';
}

echo json_encode($response);
?>