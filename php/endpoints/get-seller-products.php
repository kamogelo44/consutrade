<?php
/*
 * ConsuTrade - Get Seller Products (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns products for a seller (public access - no login required)
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'products' => [], 'message' => ''];

$seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;

if ($seller_id <= 0) {
    $response['message'] = 'Invalid seller ID';
    echo json_encode($response);
    exit;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;

// Get seller products (active only for public view)
$sql = "SELECT p.product_id as id, p.title as name, p.price, p.image_url as image,
        p.condition, p.stock_quantity, p.created_at,
        COALESCE(pi.image_url, p.image_url) AS display_image
        FROM products p
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
        WHERE p.seller_id = ? AND p.status = 'active'
        ORDER BY p.created_at DESC";

if ($limit > 0) {
    $sql .= " LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $seller_id, $limit);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $seller_id);
}

$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $imagePath = $row['display_image'] ?? $row['image'];
    if ($imagePath && !preg_match('/^http/', $imagePath)) {
        $imagePath = getBaseUrl() . $imagePath;
    }
    
    $products[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'price' => (float)$row['price'],
        'image' => $imagePath ?: getBaseUrl() . 'images/default-product.png',
        'image_url' => $imagePath ?: getBaseUrl() . 'images/default-product.png',
        'display_image' => $imagePath ?: getBaseUrl() . 'images/default-product.png',
        'condition' => ucfirst($row['condition'] ?? 'Good'),
        'stock_quantity' => (int)($row['stock_quantity'] ?? 0),
        'created_at' => $row['created_at']
    ];
}
$stmt->close();

$response['success'] = true;
$response['products'] = $products;
$response['total'] = count($products);

echo json_encode($response);
?>