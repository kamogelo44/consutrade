<?php
/*
 * ConsuTrade - Get All Products (Admin AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns paginated list of all products for admin management
 * Uses product_images table for primary image
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'products' => [], 'total_pages' => 1, 'current_page' => 1];

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    echo json_encode($response);
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 12;
$offset = ($page - 1) * $limit;

// Build query with primary image from product_images table
$sql = "SELECT p.product_id as id, p.title as name, p.price, p.status, p.stock_quantity, 
        DATE_FORMAT(p.created_at, '%d %b %Y') as created_at,
        COALESCE(pi.image_url, p.image_url) AS display_image,
        u.full_name as seller_name
        FROM products p
        LEFT JOIN users u ON p.seller_id = u.user_id
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
        WHERE p.status != 'deleted'";

$params = [];
$types = "";

if ($status !== 'all') {
    $sql .= " AND p.status = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (p.title LIKE ? OR u.full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

// Count total
$count_sql = str_replace(
    "SELECT p.product_id as id, p.title as name, p.price, p.status, p.stock_quantity, DATE_FORMAT(p.created_at, '%d %b %Y') as created_at, COALESCE(pi.image_url, p.image_url) AS display_image, u.full_name as seller_name",
    "SELECT COUNT(*) as total",
    $sql
);
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$count_stmt->close();

$total_pages = ceil($total_rows / $limit);

// Get products with pagination
$sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'price' => (float)$row['price'],
        'status' => $row['status'],
        'stock_quantity' => (int)$row['stock_quantity'],
        'created_at' => $row['created_at'],
        'display_image' => $row['display_image'],
        'image' => $row['display_image'],
        'seller_name' => $row['seller_name'] ?? 'Unknown'
    ];
}
$stmt->close();

$response['success'] = true;
$response['products'] = $products;
$response['total_pages'] = $total_pages;
$response['current_page'] = $page;

echo json_encode($response);
?>