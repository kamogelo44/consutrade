<?php
/*
 * ConsuTrade - Get All Products
 * Author: Kamogelo Phale
 */

// Don't start session if already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'products' => []];

// Get filter parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$categories = isset($_GET['categories']) ? explode(',', $_GET['categories']) : [];
$price_range = isset($_GET['price_range']) ? $_GET['price_range'] : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';

$sql = "SELECT p.product_id, p.title as product_name, p.price, p.image_url, p.location, p.status, p.condition,
        u.full_name as seller_name, u.user_id as seller_id,
        u.id_verified as is_verified
        FROM products p 
        JOIN users u ON p.seller_id = u.user_id 
        WHERE p.status = 'active'";

$params = [];
$types = "";

if (!empty($categories)) {
    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $sql .= " AND p.category_id IN ($placeholders)";
    $params = array_merge($params, $categories);
    $types .= str_repeat('s', count($categories));
}

if (!empty($price_range)) {
    switch ($price_range) {
        case 'under100':
            $sql .= " AND p.price < 100";
            break;
        case '100-500':
            $sql .= " AND p.price BETWEEN 100 AND 500";
            break;
        case '500-1000':
            $sql .= " AND p.price BETWEEN 500 AND 1000";
            break;
        case 'over1000':
            $sql .= " AND p.price > 1000";
            break;
    }
}

if (!empty($location)) {
    $sql .= " AND u.location LIKE ?";
    $params[] = "%$location%";
    $types .= "s";
}

switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY p.created_at DESC";
        break;
}

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

while ($row = $result->fetch_assoc()) {
    $imagePath = $row['image_url'];
    if (empty($imagePath)) {
        $imagePath = 'images/default-product.png';
    }
    
    $response['products'][] = [
        'id' => $row['product_id'],
        'name' => $row['product_name'],
        'price' => (float)$row['price'],
        'image' => $imagePath,
        'seller_name' => $row['seller_name'],
        'seller_id' => $row['seller_id'],
        'location' => $row['location'] ?? 'South Africa',
        'condition' => $row['condition'] ?? 'Good',
        'is_verified' => $row['is_verified'] == 1
    ];
}

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM products p JOIN users u ON p.seller_id = u.user_id WHERE p.status = 'active'";
$count_result = $conn->query($count_sql);
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$response['success'] = true;
$response['total_pages'] = $total_pages;
$response['current_page'] = $page;

$stmt->close();
$conn->close();

echo json_encode($response);
exit;
?>