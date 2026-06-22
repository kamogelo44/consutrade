<?php
/*
 * ConsuTrade - Get All Products (Admin AJAX)
 */

require_once dirname(__DIR__, 2) . '/init.php';

error_log("[DEBUG] get-all-products.php called");

header('Content-Type: application/json');

$response = ['success' => false, 'products' => [], 'total_pages' => 1, 'current_page' => 1];

if (!$auth->isAdmin()) {
    error_log("[DEBUG] Not admin, returning empty");
    echo json_encode($response);
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = $_GET['status'] ?? 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 12;
$offset = ($page - 1) * $limit;

error_log("[DEBUG] Getting products: page=$page, status=$status, search=$search");

try {
    $products = $productRepo->findAll($status, $search, $limit, $offset);
    $totalProducts = $productRepo->countForAdmin($status, $search);
    $totalPages = ceil($totalProducts / $limit);

    error_log("[DEBUG] Found " . count($products) . " products, total $totalProducts");

    $response['success'] = true;
    $response['products'] = $products;
    $response['total_pages'] = $totalPages;
    $response['current_page'] = $page;

    echo json_encode($response);
} catch (Exception $e) {
    error_log("[ERROR] " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

exit;
