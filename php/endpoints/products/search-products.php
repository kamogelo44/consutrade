<?php
/*
 * ConsuTrade - Search Products API (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns search results for the search page with filters and pagination
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = [
    'success' => false,
    'products' => [],
    'total_pages' => 0,
    'current_page' => 1,
    'total_results' => 0
];

try {
    // Get and sanitize search parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(48, max(1, (int)$_GET['limit'])) : 12;
    $sort = $_GET['sort'] ?? 'newest';

    // If no search term, return empty result
    if (empty($search)) {
        echo json_encode($response);
        exit;
    }

    // Filter parameters
    $categories = isset($_GET['categories']) ? array_filter(explode(',', $_GET['categories'])) : [];
    $price_range = $_GET['price_range'] ?? '';
    $location = isset($_GET['location']) ? trim($_GET['location']) : '';

    // Build filters array for ProductService
    $filters = [
        'categories' => $categories,
        'price_range' => $price_range,
        'location' => $location,
        'sort' => $sort,
        'limit' => $limit,
        'offset' => ($page - 1) * $limit,
    ];

    // Use ProductService for search
    $result = $productService->search($search, $filters);

    $formattedProducts = [];
    foreach ($result['products'] as $product) {
        $formattedProducts[] = [
            'id' => (int) $product['id'] ?? 0,
            'name' => $product['product_name'] ?? $product['name'] ?? 'Product',
            'product_name' => $product['product_name'] ?? $product['name'] ?? 'Product',
            'price' => (float) $product['price'] ?? 0,
            'image' => $productService->getImageUrl($product['image'] ?? ''),
            'image_url' => $productService->getImageUrl($product['image'] ?? ''),
            'display_image' => $productService->getImageUrl($product['image'] ?? ''),
            'seller_name' => $product['seller_name'] ?? 'Unknown Seller',
            'seller_id' => (int) $product['seller_id'] ?? 0,
            'location' => $product['location'] ?? 'South Africa',
            'condition' => $product['condition'] ?? 'Good',
            'stock_quantity' => (int) ($product['stock_quantity'] ?? 0),
            'is_verified' => (bool) ($product['is_verified'] ?? false),
            'profile_image' => $product['profile_image'] ?? null,
            'created_at' => $product['created_at'] ?? '',
            'rating' => $product['rating'] ?? null,
            'review_count' => $product['review_count'] ?? 0
        ];
    }

    // Build response
    $response['success'] = true;
    $response['products'] = $formattedProducts;
    $response['total_pages'] = ceil($result['total'] / $limit);
    $response['current_page'] = $page;
    $response['total_results'] = (int) $result['total'];
} catch (Exception $e) {
    error_log("Search Products Error: " . $e->getMessage());
    $response['message'] = 'An error occurred while searching.';
}

echo json_encode($response);
exit;
