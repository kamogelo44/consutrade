<?php
/*
 * ConsuTrade - Delete Product (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$isLoggedIn) {
    $response['message'] = 'Unauthorized.';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$productId = (int) ($input['product_id'] ?? 0);

if ($productId <= 0) {
    $response['message'] = 'Invalid product.';
    echo json_encode($response);
    exit;
}

if ($currentUser instanceof Seller) {
    $result = $productRepo->deleteSellerProduct($productId, $currentUser->getUserId());
    $response['success'] = $result['success'];
    $response['message'] = $result['message'];
} elseif ($currentUser instanceof Admin) {
    $product = $productRepo->getProductObject($productId);

    if (!$product) {
        $response['message'] = 'Product not found.';
        echo json_encode($response);
        exit;
    }

    $result = $productRepo->deleteSellerProduct($productId, $product->getSellerId());
    $response['success'] = $result['success'];
    $response['message'] = $result['message'];
} else {
    $response['message'] = 'Unauthorized. Only sellers and admins can delete products.';
}

echo json_encode($response);
