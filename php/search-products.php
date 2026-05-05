<?php
/*
 * ConsuTrade - Search Products API (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns search results for the search page with filters and pagination
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'products' => [], 'total_pages' => 1, 'current_page' => 1];

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
$offset = ($page - 1) * $limit;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Filter parameters
$categories = isset($_GET['categories']) ? explode(',', $_GET['categories']) : [];
$price_range = isset($_GET['price_range']) ? $_GET['price_range'] : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';

// If no search term, return empty results
if (empty($search)) {
    echo json_encode($response);
    exit;
}

// Build search query
$sql = "SELECT p.product_id, p.title as product_name, p.price, p.image_url, p.location, p.condition,
        p.stock_quantity, p.created_at,
        u.full_name as seller_name, u.user_id as seller_id, u.profile_image as profile_image,
        u.id_verified as is_verified
        FROM products p 
        JOIN users u ON p.seller_id = u.user_id 
        WHERE p.status = 'active'
        AND (p.title LIKE ? OR p.description LIKE ?)";

$params = ["%$search%", "%$search%"];
$types = "ss";

// Category filter
if (!empty($categories) && $categories[0] !== '') {
    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $sql .= " AND p.category_id IN ($placeholders)";
    foreach ($categories as $cat) {
        $params[] = (int)$cat;
        $types .= "i";
    }
}

// Price range filter
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

// Location filter
if (!empty($location)) {
    $sql .= " AND p.location LIKE ?";
    $params[] = "%$location%";
    $types .= "s";
}

// Sorting
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

// Add limit and offset
$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode($response);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $imagePath = $row['image_url'];
    if (empty($imagePath)) {
        $imagePath = 'images/default-product.png';
    }
    
    $response['products'][] = [
        'id' => (int)$row['product_id'],
        'name' => $row['product_name'],
        'price' => (float)$row['price'],
        'image' => $imagePath,
        'seller_name' => $row['seller_name'],
        'seller_id' => (int)$row['seller_id'],
        'location' => $row['location'] ?? 'South Africa',
        'condition' => $row['condition'] ?? 'Good',
        'stock_quantity' => (int)($row['stock_quantity'] ?? 1),
        'is_verified' => (bool)$row['is_verified'],
        'profile_image' => $row['profile_image'] ?? null
    ];
}
$stmt->close();

// Get total count for pagination with same filters
$count_sql = "SELECT COUNT(*) as total
              FROM products p 
              JOIN users u ON p.seller_id = u.user_id 
              WHERE p.status = 'active'
              AND (p.title LIKE ? OR p.description LIKE ?)";

$count_params = ["%$search%", "%$search%"];
$count_types = "ss";

// Add same filters to count query
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
        case 'under100':
            $count_sql .= " AND p.price < 100";
            break;
        case '100-500':
            $count_sql .= " AND p.price BETWEEN 100 AND 500";
            break;
        case '500-1000':
            $count_sql .= " AND p.price BETWEEN 500 AND 1000";
            break;
        case 'over1000':
            $count_sql .= " AND p.price > 1000";
            break;
    }
}

if (!empty($location)) {
    $count_sql .= " AND p.location LIKE ?";
    $count_params[] = "%$location%";
    $count_types .= "s";
}

$count_stmt = $conn->prepare($count_sql);
if ($count_stmt) {
    $count_stmt->bind_param($count_types, ...$count_params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_rows = $count_result->fetch_assoc()['total'] ?? 0;
    $count_stmt->close();
} else {
    $total_rows = 0;
}

$total_pages = ceil($total_rows / $limit);

$response['success'] = true;
$response['total_pages'] = $total_pages;
$response['current_page'] = $page;
$response['total_results'] = $total_rows;

echo json_encode($response);
?>