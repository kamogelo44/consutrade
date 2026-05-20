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

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    echo json_encode($response);
    exit;
}

// Get parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$sql = "SELECT o.order_id, o.total_price, o.status, o.created_at,
        u.full_name as buyer_name,
        s.full_name as seller_name,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
        FROM orders o
        JOIN users u ON o.buyer_id = u.user_id
        JOIN users s ON o.seller_id = s.user_id
        WHERE 1=1";

$params = [];
$types = "";

// Status filter
if ($status !== 'all') {
    $sql .= " AND o.status = ?";
    $params[] = $status;
    $types .= "s";
}

// Search filter
if (!empty($search)) {
    $sql .= " AND (o.order_id LIKE ? OR u.full_name LIKE ? OR s.full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Count total
$count_sql = str_replace("SELECT o.order_id, o.total_price, o.status, o.created_at, u.full_name as buyer_name, s.full_name as seller_name, (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count", "SELECT COUNT(*) as total", $sql);
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$count_stmt->close();

$total_pages = ceil($total_rows / $limit);

// Get orders with pagination
$sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = [
        'order_id' => (int)$row['order_id'],
        'total_price' => (float)$row['total_price'],
        'status' => $row['status'],
        'created_at' => date('d M Y', strtotime($row['created_at'])),
        'buyer_name' => $row['buyer_name'],
        'seller_name' => $row['seller_name'],
        'item_count' => (int)($row['item_count'] ?? 0)
    ];
}
$stmt->close();

$response['success'] = true;
$response['orders'] = $orders;
$response['total_pages'] = $total_pages;
$response['current_page'] = $page;

echo json_encode($response);
?>