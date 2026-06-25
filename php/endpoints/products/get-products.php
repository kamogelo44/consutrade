<?php
/*
 * ConsuTrade - Get All Products (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns paginated, filtered, and sorted products for listings page.
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'products' => [], 'total_pages' => 1];

// Get and sanitize parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
$sort = $_GET['sort'] ?? 'newest';
$categories = isset($_GET['categories']) ? explode(',', $_GET['categories']) : [];
$price_range = $_GET['price_range'] ?? '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$sellerId = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;

// Remove empty category values
$categories = array_filter($categories, function ($cat) {
    return !empty($cat);
});

// Use ProductService for product lookup
$result = $productService->findPublic([
    'categories'  => $categories,
    'price_range' => $price_range,
    'location'    => $location,
    'sort'        => $sort,
    'limit'       => $limit,
    'offset'      => ($page - 1) * $limit,
    'seller_id'   => $sellerId
]);

// Convert image URLs to full paths
foreach ($result['products'] as &$product) {
    $product['image'] = $productService->getImageUrl($product['image']);
}

$response['success'] = true;
$response['products'] = $result['products'];
$response['total_pages'] = ceil($result['total'] / $limit);
$response['current_page'] = $page;
$response['total'] = $result['total'];

echo json_encode($response);
exit;
