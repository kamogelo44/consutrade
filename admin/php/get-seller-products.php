<?php
/*
 * ConsuTrade - Get Seller Products (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns products for the logged-in seller (their own dashboard)
 */

require_once dirname(__DIR__) . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'products' => [], 'message' => ''];

// Check if seller is logged in using centralized auth
if (!$is_logged_in || $current_user['role'] !== 'seller') {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$seller_id = $current_user_id;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get seller products using helper function
$products = getSellerProducts($conn, $seller_id, $status_filter, $search_term, $limit);

// Get seller info for additional data
$user_info = getUserById($conn, $seller_id);

// Format products for response
$formatted_products = [];
foreach ($products as $product) {
    $formatted_products[] = [
        'id' => $product['id'],
        'name' => $product['title'],
        'price' => $product['price'],
        'image' => getProductImageUrl($product['image']),
        'status' => $product['status'],
        'stock_quantity' => $product['stock_quantity'],
        'created_at' => date('d M Y', strtotime($product['created_at'])),
        'seller_name' => $user_info['full_name'] ?? 'You',
        'profile_image' => $user_info['profile_image'] ?? null,
        'is_verified' => (bool)($user_info['id_verified'] ?? false),
        'category_name' => $product['category_name'] ?? 'General'
    ];
}

$response['success'] = true;
$response['products'] = $formatted_products;
$response['total'] = count($formatted_products);

echo json_encode($response);
?>