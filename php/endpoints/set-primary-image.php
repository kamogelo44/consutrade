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

if (!$auth->isSeller()) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$image_id = isset($data['image_id']) ? (int)$data['image_id'] : 0;
$product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
$seller_id = $currentUser->getUserId();

if (!$image_id || !$product_id) {
    $response['message'] = 'Missing required fields';
    echo json_encode($response);
    exit;
}

$product = $productRepo->getProductObject($product_id);

if (!$product || $product->getSellerId() !== $seller_id) {
    $response['message'] = 'Product not found';
    echo json_encode($response);
    exit;
}

$image = $productImageRepo->getById($image_id);

if (!$image || $image['product_id'] !== $product_id) {
    $response['message'] = 'Image not found';
    echo json_encode($response);
    exit;
}

$result = $productImageRepo->setPrimary($product_id, $image_id);

if ($result) {
    $response['success'] = true;
    $response['message'] = 'Primary image updated successfully';
} else {
    $response['message'] = 'Failed to update primary image';
}

echo json_encode($response);
