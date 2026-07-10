<?php
/*
 * ConsuTrade - Get All Orders (Admin AJAX)
 */

require_once dirname(__DIR__, 3) . '/init.php';

rateLimit('admin_all_orders', 30, 60);

header('Content-Type: application/json');

$response = ['success' => false, 'orders' => [], 'total_pages' => 1, 'current_page' => 1];

if (!$currentUser->hasRole('admin')) {
    echo json_encode($response);
    exit;
}

$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = $_GET['status'] ?? 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit  = 10;
$offset = ($page - 1) * $limit;

$orders = $orderService->findAll($status, $search, $limit, $offset);
$totalOrders = $orderService->countAll($status, $search);

$response['success'] = true;
$response['orders'] = $orders;
$response['total_pages'] = ceil($totalOrders / $limit);
$response['current_page'] = $page;

echo json_encode($response);
