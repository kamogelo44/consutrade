<?php
/*
 * ConsuTrade - Set Primary Image (AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows sellers to set a gallery image as the primary image for a product
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if seller is logged in
if (!isSellerLoggedIn()) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$image_id = isset($data['image_id']) ? (int)$data['image_id'] : 0;
$product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
$seller_id = $current_user_id;

if (!$image_id || !$product_id) {
    $response['message'] = 'Missing required fields';
    echo json_encode($response);
    exit;
}

// Verify product belongs to seller
$check = $conn->prepare("SELECT product_id FROM products WHERE product_id = ? AND seller_id = ?");
$check->bind_param('ii', $product_id, $seller_id);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    $response['message'] = 'Product not found';
    echo json_encode($response);
    $check->close();
    exit;
}
$check->close();

// Verify the gallery image belongs to this product
$check_img = $conn->prepare("SELECT image_url FROM product_images WHERE image_id = ? AND product_id = ?");
$check_img->bind_param('ii', $image_id, $product_id);
$check_img->execute();
$img_result = $check_img->get_result();
if ($img_result->num_rows === 0) {
    $response['message'] = 'Image not found';
    echo json_encode($response);
    $check_img->close();
    exit;
}
$check_img->close();

// Set as primary using helper function
$result = setProductPrimaryImage($conn, $product_id, $image_id);

if ($result) {
    $response['success'] = true;
    $response['message'] = 'Primary image updated successfully';
} else {
    $response['message'] = 'Failed to update primary image';
}

echo json_encode($response);
?>