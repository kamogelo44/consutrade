<?php
/*
 * ConsuTrade - Get Single Product (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns product details for the product details page
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'product' => null];

if (!isset($_GET['id'])) {
    $response['error'] = 'No product ID provided';
    echo json_encode($response);
    exit;
}

$product_id = (int)$_GET['id'];

if ($product_id <= 0) {
    $response['error'] = 'Invalid product ID';
    echo json_encode($response);
    exit;
}

// Get product details with stock_quantity
$sql = "SELECT p.product_id, p.title, p.price, p.description, 
        p.image_url, p.gallery_images, p.location, p.condition, p.category_id,
        p.stock_quantity, p.status,
        u.full_name as seller_name, u.user_id as seller_id, u.profile_image as seller_profile_image,
        u.id_verified as is_verified
        FROM products p 
        LEFT JOIN users u ON p.seller_id = u.user_id 
        WHERE p.product_id = ? AND p.status = 'active'";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    $response['error'] = 'Database prepare error';
    echo json_encode($response);
    exit;
}

$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // Get category name
    $category_name = 'General';
    if ($row['category_id'] > 0) {
        $cat_sql = "SELECT category_name FROM categories WHERE category_id = ?";
        $cat_stmt = $conn->prepare($cat_sql);
        if ($cat_stmt) {
            $cat_stmt->bind_param('i', $row['category_id']);
            $cat_stmt->execute();
            $cat_result = $cat_stmt->get_result();
            if ($cat_result && $cat_result->num_rows > 0) {
                $cat_row = $cat_result->fetch_assoc();
                $category_name = $cat_row['category_name'];
            }
            $cat_stmt->close();
        }
    }
    
    // Get seller rating
    $avg_rating = 0;
    $review_count = 0;
    $rating_sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE seller_id = ?";
    $rating_stmt = $conn->prepare($rating_sql);
    if ($rating_stmt) {
        $rating_stmt->bind_param('i', $row['seller_id']);
        $rating_stmt->execute();
        $rating_result = $rating_stmt->get_result();
        if ($rating_row = $rating_result->fetch_assoc()) {
            $avg_rating = round($rating_row['avg_rating'] ?? 0, 1);
            $review_count = (int)($rating_row['review_count'] ?? 0);
        }
        $rating_stmt->close();
    }
    
    // Determine stock status
    $stock_quantity = (int)($row['stock_quantity'] ?? 1);
    $stock_status = 'in_stock';
    if ($stock_quantity <= 0) {
        $stock_status = 'out_of_stock';
    } elseif ($stock_quantity <= 5) {
        $stock_status = 'low_stock';
    }
    
    // Use helper for consistent image URL
    $image_url = getProductImageUrl($row['image_url'] ?? null);
    
    $response['product'] = [
        'id' => (int)$row['product_id'],
        'name' => $row['title'],
        'price' => (float)$row['price'],
        'description' => $row['description'] ?? '',
        'condition' => $row['condition'] ?? '',
        'location' => $row['location'] ?? '',
        'category_id' => (int)$row['category_id'],
        'category_name' => $category_name,
        'image_url' => $image_url,
        'image' => $image_url,
        'gallery_images' => $row['gallery_images'],
        'seller_name' => $row['seller_name'] ?? 'Unknown Seller',
        'seller_id' => (int)$row['seller_id'],
        'is_verified' => (bool)$row['is_verified'],
        'avg_rating' => $avg_rating,
        'review_count' => $review_count,
        'profile_image' => $row['seller_profile_image'] ?? null,
        'stock_quantity' => $stock_quantity,
        'stock_status' => $stock_status,
        'status' => $row['status']
    ];
    $response['success'] = true;
} else {
    $response['error'] = 'Product not found';
}

$stmt->close();
echo json_encode($response);
?>