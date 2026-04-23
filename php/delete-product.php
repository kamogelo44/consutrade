<?php
/*
 * ConsuTrade - Delete Product
 * Author: Kamogelo Phale
 * 
 * Deletes a product (soft delete by setting status to 'deleted')
 */

session_start();
require_once 'config.php';
require_once 'helpers.php';  // Add this line

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'seller') {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = (int)($input['product_id'] ?? 0);

if ($product_id <= 0) {
    $response['message'] = 'Invalid product ID';
    echo json_encode($response);
    exit;
}

$seller_id = $_SESSION['user_id'];

// First, get the product image path before deleting (to clean up the file)
$get_image_sql = "SELECT image_url FROM products WHERE product_id = ? AND seller_id = ?";
$get_image_stmt = $conn->prepare($get_image_sql);
$get_image_stmt->bind_param('ii', $product_id, $seller_id);
$get_image_stmt->execute();
$get_image_result = $get_image_stmt->get_result();
$product_data = $get_image_result->fetch_assoc();
$get_image_stmt->close();

// Soft delete - update status to 'deleted'
$sql = "UPDATE products SET status = 'deleted' WHERE product_id = ? AND seller_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $product_id, $seller_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    // Delete the product image file using helper
    if (!empty($product_data['image_url'])) {
        deleteProductImage($product_data['image_url']);
    }
    
    $response['success'] = true;
    $response['message'] = 'Product deleted successfully';
} else {
    $response['message'] = 'Failed to delete product';
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>