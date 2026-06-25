<?php
/*
 * ConsuTrade - Delete Product Image (AJAX)
 * Author: Kamogelo Phale
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
$image_id = (int)($data['image_id'] ?? 0);
$product_id = (int)($data['product_id'] ?? 0);
$seller_id = $currentUser->getUserId();

$product = $productRepo->findById($product_id);

if (!$product || $product->getSellerId() !== $seller_id) {
    $response['message'] = 'Product not found';
    echo json_encode($response);
    exit;
}

$result = $productImageRepo->delete($image_id, $product_id);

if ($result) {
    $response['success'] = true;
    $response['message'] = 'Image removed';
} else {
    $response['message'] = 'Could not remove image';
}

echo json_encode($response);
