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

    // Format product images with full URLs
    foreach ($result['products'] as &$product) {
        $product['image'] = $productService->getImageUrl($product['image'] ?? '');
    }

    // Build response
    $response['success'] = true;
    $response['products'] = $result['products'];
    $response['total_pages'] = ceil($result['total'] / $limit);
    $response['current_page'] = $page;
    $response['total_results'] = (int) $result['total'];
} catch (Exception $e) {
    error_log("Search Products Error: " . $e->getMessage());
    $response['message'] = 'An error occurred while searching.';
}

echo json_encode($response);
exit;
