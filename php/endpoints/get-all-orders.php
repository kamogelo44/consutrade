<?php
/*
 * ConsuTrade - Get All Orders (Admin AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns paginated list of all orders for admin management
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'orders' => [], 'total_pages' => 1, 'current_page' => 1];

if (!$auth->isAdminLoggedIn()) {
    echo json_encode($response);
    exit;
}

// Since getAllOrders() doesn't support pagination/filtering yet,
// we keep the inline query for now — but swap auth and $conn usage
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = $_GET['status'] ?? 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit  = 10;
$offset = ($page - 1) * $limit;

$sql = "SELECT o.order_id, o.total_price, o.status, o.created_at,
        u.full_name as buyer_name,
        s.full_name as seller_name,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
        FROM orders o
        JOIN users u ON o.buyer_id = u.user_id
        JOIN users s ON o.seller_id = s.user_id
        WHERE 1=1";

$params = [];
$types  = "";

if ($status !== 'all') {
    $sql    .= " AND o.status = ?";
    $params[] = $status;
    $types  .= "s";
}

if (!empty($search)) {
    $sql       .= " AND (o.order_id LIKE ? OR u.full_name LIKE ? OR s.full_name LIKE ?)";
    $searchParam = "%$search%";
    $params[]  = $searchParam;
    $params[]  = $searchParam;
    $params[]  = $searchParam;
    $types    .= "sss";
}

// Count total
$countSql = "SELECT COUNT(*) as total FROM orders o
             JOIN users u ON o.buyer_id = u.user_id
             JOIN users s ON o.seller_id = s.user_id
             WHERE 1=1";

$countParams = $params;
$countTypes  = $types;

if ($status !== 'all') {
    $countSql .= " AND o.status = ?";
}
if (!empty($search)) {
    $countSql .= " AND (o.order_id LIKE ? OR u.full_name LIKE ? OR s.full_name LIKE ?)";
}

$countStmt = $conn->prepare($countSql);
if (!empty($countParams)) {
    $countStmt->bind_param($countTypes, ...$countParams);
}
$countStmt->execute();
$total_rows = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;
$countStmt->close();

$total_pages = ceil($total_rows / $limit);

// Pagination
$sql    .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types  .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = [
        'order_id'    => (int) $row['order_id'],
        'total_price' => (float) $row['total_price'],
        'status'      => $row['status'],
        'created_at'  => date('d M Y', strtotime($row['created_at'])),
        'buyer_name'  => $row['buyer_name'],
        'seller_name' => $row['seller_name'],
        'item_count'  => (int) ($row['item_count'] ?? 0)
    ];
}
$stmt->close();

$response['success']      = true;
$response['orders']       = $orders;
$response['total_pages']  = $total_pages;
$response['current_page'] = $page;

echo json_encode($response);