<?php
/*
 * ConsuTrade - Get Seller Products (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'products' => [], 'message' => ''];

$sellerId = isset($_GET['seller_id']) ? (int) $_GET['seller_id'] : 0;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 0;

if ($sellerId <= 0) {
    $response['message'] = 'Invalid seller ID';
    echo json_encode($response);
    exit;
}

$isOwner = ($isLoggedIn && $currentUser instanceof Seller && $currentUser->getUserId() === $sellerId);

// Use ProductService for seller products
$products = $productService->findBySellerForDisplay($sellerId, $isOwner, $limit);

$response['success'] = true;
$response['products'] = $products;
$response['total'] = count($products);
$response['is_owner'] = $isOwner;

echo json_encode($response);
