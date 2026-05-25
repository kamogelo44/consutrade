<?php
/*
 * ConsuTrade - Search Products API (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns search results for the search page with filters and pagination
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'products' => [], 'total_pages' => 1, 'current_page' => 1];

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
$sort   = $_GET['sort'] ?? 'newest';

// Filter parameters
$categories  = isset($_GET['categories']) ? explode(',', $_GET['categories']) : [];
$price_range = $_GET['price_range'] ?? '';
$location    = isset($_GET['location']) ? trim($_GET['location']) : '';

// If no search term, return empty
if (empty($search)) {
    echo json_encode($response);
    exit;
}

$filters = [
    'categories'  => $categories,
    'price_range' => $price_range,
    'location'    => $location,
    'sort'        => $sort,
    'limit'       => $limit,
    'offset'      => ($page - 1) * $limit,
];

$result = $productRepo->searchProducts($search, $filters);

$response['success']       = true;
$response['products']      = $result['products'];
$response['total_pages']   = ceil($result['total'] / $limit);
$response['current_page']  = $page;
$response['total_results'] = $result['total'];

echo json_encode($response);