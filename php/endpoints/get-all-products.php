<?php
/*
 * ConsuTrade - Get All Products (Admin AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns paginated list of all products for admin management using ProductRepository
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'products' => [], 'total_pages' => 1, 'current_page' => 1];

if (!$auth->isAdmin()) {
    echo json_encode($response);
    exit;
}

$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = $_GET['status'] ?? 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit  = 12;
$offset = ($page - 1) * $limit;

$products = $productRepo->getAllProductsForAdmin($status, $search, $limit, $offset);
$totalProducts = $productRepo->getProductsCountForAdmin($status, $search);
$totalPages = ceil($totalProducts / $limit);

$response['success'] = true;
$response['products'] = $products;
$response['total_pages'] = $totalPages;
$response['current_page'] = $page;

echo json_encode($response);
