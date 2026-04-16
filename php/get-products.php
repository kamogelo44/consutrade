<?php
/*
 * ConsuTrade - Get All Products
 * Author: Kamogelo Phale
 * 
 * Returns all active products for the listings page
 * 
 */

require_once 'config.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'products' => [], 'total_pages' => 1, 'current_page' => 1];

// Get filter parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Simple query without filters first to test
$sql = "SELECT p.product_id, p.title as product_name, p.price, p.image_url, p.location, p.condition,
        u.full_name as seller_name, u.user_id as seller_id,
        u.id_verified as is_verified
        FROM products p 
        JOIN users u ON p.seller_id = u.user_id 
        WHERE p.status = 'active'
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode($response);
    $conn->close();
    exit;
}

$stmt->bind_param('ii', $limit, $offset);
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

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
$count_result = $conn->query($count_sql);
$total_rows = 0;
if ($count_result) {
    $total_rows = $count_result->fetch_assoc()['total'];
}
$total_pages = ceil($total_rows / $limit);

$response['success'] = true;
$response['total_pages'] = $total_pages;
$response['current_page'] = $page;

$stmt->close();
$conn->close();

echo json_encode($response);
exit;
?>