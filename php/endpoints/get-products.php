<?php
/*
 * ConsuTrade - Get All Products (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'products' => [], 'total_pages' => 1];

$page        = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit       = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
$sort        = $_GET['sort'] ?? 'newest';
$categories  = isset($_GET['categories']) ? explode(',', $_GET['categories']) : [];
$price_range = $_GET['price_range'] ?? '';
$location    = isset($_GET['location']) ? trim($_GET['location']) : '';

$result = $productRepo->getPublicProducts([
    'categories'  => $categories,
    'price_range' => $price_range,
    'location'    => $location,
    'sort'        => $sort,
    'limit'       => $limit,
    'offset'      => ($page - 1) * $limit,
]);

$response['success']      = true;
$response['products']     = $result['products'];
$response['total_pages']  = ceil($result['total'] / $limit);
$response['current_page'] = $page;

echo json_encode($response);