<?php
/*
 * ConsuTrade - Get All Orders (Admin AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns paginated list of all orders for admin management using OrderRepository
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'orders' => [], 'total_pages' => 1, 'current_page' => 1];

if (!$auth->isAdmin()) {
    echo json_encode($response);
    exit;
}

$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = $_GET['status'] ?? 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit  = 10;
$offset = ($page - 1) * $limit;

$allOrders = $orderRepo->getAllOrders();

// Filter and paginate
$filtered = array_filter($allOrders, function ($order) use ($status, $search) {
    if ($status !== 'all' && $order['status'] !== $status) return false;
    if (!empty($search)) {
        $searchLower = strtolower($search);
        if (
            strpos(strtolower($order['buyer_name']), $searchLower) === false &&
            strpos(strtolower($order['seller_name']), $searchLower) === false &&
            strpos((string)$order['order_id'], $search) === false
        ) {
            return false;
        }
    }
    return true;
});

$total = count($filtered);
$totalPages = ceil($total / $limit);
$paginated = array_slice($filtered, $offset, $limit);

$response['success'] = true;
$response['orders'] = $paginated;
$response['total_pages'] = $totalPages;
$response['current_page'] = $page;

echo json_encode($response);
