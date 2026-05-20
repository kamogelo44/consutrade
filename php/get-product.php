<?php
/*
 * ConsuTrade - Get Single Product (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns product details for the product details page
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'product' => null];

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    $response['error'] = 'Invalid product ID';
    echo json_encode($response);
    exit;
}

// Get product details
$sql = "SELECT p.product_id, p.title, p.description, p.price, p.image_url, 
        p.location, p.condition, p.category_id, p.stock_quantity,
        u.user_id as seller_id, u.full_name as seller_name, 
        u.profile_image, u.id_verified as is_verified
        FROM products p
        JOIN users u ON p.seller_id = u.user_id
        WHERE p.product_id = ? AND p.status = 'active'";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Use helper functions
    $gallery_images = getProductGallery($conn, $product_id);
    $gallery_urls = [];
    foreach ($gallery_images as $img) {
        $gallery_urls[] = getProductImageUrl($img['image_url']);
    }
    
    $rating = getSellerRating($conn, $row['seller_id']);
    
    // Get category name
    $category_name = 'General';
    $cat_sql = "SELECT category_name FROM categories WHERE category_id = ?";
    $cat_stmt = $conn->prepare($cat_sql);
    $cat_stmt->bind_param('i', $row['category_id']);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    if ($cat_row = $cat_result->fetch_assoc()) {
        $category_name = $cat_row['category_name'];
    }
    $cat_stmt->close();
    
    $response['product'] = [
        'id' => (int)$row['product_id'],
        'name' => $row['title'],
        'description' => $row['description'],
        'price' => (float)$row['price'],
        'condition' => $row['condition'],
        'location' => $row['location'],
        'category_id' => (int)$row['category_id'],
        'category_name' => $category_name,
        'image_url' => getProductImageUrl($row['image_url']),
        'gallery_images' => $gallery_urls,
        'seller_id' => (int)$row['seller_id'],
        'seller_name' => $row['seller_name'],
        'seller_profile_image' => getUserProfileImage($row['profile_image']),
        'is_verified' => (bool)$row['is_verified'],
        'stock_quantity' => (int)$row['stock_quantity'],
        'avg_rating' => $rating['avg_rating'],
        'review_count' => $rating['review_count']
    ];
    $response['success'] = true;
} else {
    $response['error'] = 'Product not found';
}

$stmt->close();
echo json_encode($response);
?>