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

if (!$is_logged_in) {
    $response['message'] = 'Unauthorized.';
    echo json_encode($response);
    exit;
}

$role    = $current_user['role'] ?? '';
$user_id = $current_user_id;

$input      = json_decode(file_get_contents('php://input'), true);
$product_id = (int) ($input['product_id'] ?? 0);

if ($product_id <= 0) {
    $response['message'] = 'Invalid product.';
    echo json_encode($response);
    exit;
}

if ($role === 'seller') {
    $result = $productRepo->deleteSellerProduct($product_id, $user_id);
    $response['success'] = $result['success'];
    $response['message'] = $result['message'];

} elseif ($role === 'admin') {
    // Admin can delete any product — find the seller_id first
    $check_sql = "SELECT seller_id, image_url FROM products WHERE product_id = ? AND status != 'deleted'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        $response['message'] = 'Product not found.';
        echo json_encode($response);
        $check_stmt->close();
        exit;
    }

    $product = $check_result->fetch_assoc();
    $check_stmt->close();

    // Use repository with the product's actual seller_id
    $result = $productRepo->deleteSellerProduct($product_id, (int) $product['seller_id']);
    $response['success'] = $result['success'];
    $response['message'] = $result['message'];

} else {
    $response['message'] = 'Unauthorized.';
}

echo json_encode($response);