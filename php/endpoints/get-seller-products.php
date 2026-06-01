<?php
/*
 * ConsuTrade - Get Seller Products (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns products for a seller
 * - For public access: shows only active products
 * - For logged-in seller: shows all their products (active, suspended)
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'products' => [], 'message' => ''];

$seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;

if ($seller_id <= 0) {
    $response['message'] = 'Invalid seller ID';
    echo json_encode($response);
    exit;
}

// Check if the request is from the seller viewing their own dashboard
$is_owner = ($auth->isLoggedIn() && $current_user_id == $seller_id && $auth->isSellerLoggedIn());

// Use ProductRepository
$products = $productRepo->getSellerProductsForDisplay($seller_id, $is_owner, $limit);

$response['success'] = true;
$response['products'] = $products;
$response['total'] = count($products);
$response['is_owner'] = $is_owner;

echo json_encode($response);
