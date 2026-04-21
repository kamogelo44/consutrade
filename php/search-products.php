<?php
/*
 * ConsuTrade - Search Products API
 * Author: Kamogelo Phale
 * 
 * Returns search results for the search page
 */

require_once 'config.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'products' => [], 'total_pages' => 1, 'current_page' => 1];

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

if (empty($search)) {
    echo json_encode($response);
    $conn->close();
    exit;
}

// Build search query
$sql = "SELECT p.product_id, p.title as product_name, p.price, p.image_url, p.location, p.condition,
        u.full_name as seller_name, u.user_id as seller_id,
        u.id_verified as is_verified
        FROM products p 
        JOIN users u ON p.seller_id = u.user_id 
        WHERE p.status = 'active'
        AND (p.title LIKE ? OR p.description LIKE ?)";

$search_param = "%$search%";

// Add sorting
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

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode($response);
    $conn->close();
    exit;
}

$stmt->bind_param('ssii', $search_param, $search_param, $limit, $offset);
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
$count_sql = "SELECT COUNT(*) as total
              FROM products p 
              JOIN users u ON p.seller_id = u.user_id 
              WHERE p.status = 'active'
              AND (p.title LIKE ? OR p.description LIKE ?)";

$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param('ss', $search_param, $search_param);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$response['success'] = true;
$response['total_pages'] = $total_pages;
$response['current_page'] = $page;

$stmt->close();
$count_stmt->close();
$conn->close();

echo json_encode($response);
exit;
?>