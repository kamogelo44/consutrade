<?php
/*
 * ConsuTrade - Get All Products (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns all active products for the listings page
 * Uses helper functions from helpers.php
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'products' => [], 'total_pages' => 1];

// Get filter parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
$offset = ($page - 1) * $limit;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$categories = isset($_GET['categories']) ? explode(',', $_GET['categories']) : [];
$price_range = isset($_GET['price_range']) ? $_GET['price_range'] : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';

// Build query with primary image from product_images
$sql = "SELECT p.product_id, p.title as product_name, p.price, p.image_url, 
        p.location, p.condition, p.stock_quantity, p.created_at,
        COALESCE(pi.image_url, p.image_url) AS display_image,
        u.full_name as seller_name, u.user_id as seller_id,
        u.profile_image, u.id_verified as is_verified
        FROM products p 
        JOIN users u ON p.seller_id = u.user_id 
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
        WHERE p.status = 'active'";

$params = [];
$types = "";

// Apply filters
if (!empty($categories) && $categories[0] !== '') {
    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $sql .= " AND p.category_id IN ($placeholders)";
    foreach ($categories as $cat) {
        $params[] = (int)$cat;
        $types .= "i";
    }
}

if (!empty($price_range)) {
    switch ($price_range) {
        case 'under100': $sql .= " AND p.price < 100"; break;
        case '100-500': $sql .= " AND p.price BETWEEN 100 AND 500"; break;
        case '500-1000': $sql .= " AND p.price BETWEEN 500 AND 1000"; break;
        case 'over1000': $sql .= " AND p.price > 1000"; break;
    }
}

if (!empty($location)) {
    $sql .= " AND p.location LIKE ?";
    $params[] = "%$location%";
    $types .= "s";
}

// Apply sorting
switch ($sort) {
    case 'price_low': $sql .= " ORDER BY p.price ASC"; break;
    case 'price_high': $sql .= " ORDER BY p.price DESC"; break;
    default: $sql .= " ORDER BY p.created_at DESC";
}

// Add pagination
$sql .= " LIMIT ? OFFSET ?";
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
        'id' => (int)$row['product_id'],
        'name' => $row['product_name'],
        'price' => (float)$row['price'],
        'image' => $row['display_image'] ?? $row['image_url'],
        'seller_name' => $row['seller_name'],
        'seller_id' => (int)$row['seller_id'],
        'location' => $row['location'] ?? 'South Africa',
        'condition' => $row['condition'] ?? 'Good',
        'stock_quantity' => (int)$row['stock_quantity'],
        'is_verified' => (bool)$row['is_verified'],
        'profile_image' => $row['profile_image'],
        'created_at' => $row['created_at']
    ];
}
$stmt->close();

// Get total count (simplified - same filters without pagination)
$count_sql = "SELECT COUNT(*) as total FROM products p WHERE p.status = 'active'";
$count_params = [];
$count_types = "";

if (!empty($categories) && $categories[0] !== '') {
    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $count_sql .= " AND p.category_id IN ($placeholders)";
    foreach ($categories as $cat) {
        $count_params[] = (int)$cat;
        $count_types .= "i";
    }
}
if (!empty($price_range)) {
    switch ($price_range) {
        case 'under100': $count_sql .= " AND p.price < 100"; break;
        case '100-500': $count_sql .= " AND p.price BETWEEN 100 AND 500"; break;
        case '500-1000': $count_sql .= " AND p.price BETWEEN 500 AND 1000"; break;
        case 'over1000': $count_sql .= " AND p.price > 1000"; break;
    }
}
if (!empty($location)) {
    $count_sql .= " AND p.location LIKE ?";
    $count_params[] = "%$location%";
    $count_types .= "s";
}

$count_stmt = $conn->prepare($count_sql);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$count_stmt->close();

$response['success'] = true;
$response['products'] = $products;
$response['total_pages'] = ceil($total_rows / $limit);
$response['current_page'] = $page;

echo json_encode($response);
?>