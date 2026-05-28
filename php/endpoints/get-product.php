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
    $response['error'] = 'Invalid product';
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
    // Get gallery images using ProductImageRepository
    $gallery = $productImageRepo->getByProductId($product_id);
    $gallery_urls = [];
    foreach ($gallery as $img) {
        $gallery_urls[] = $productRepo->getProductImageUrl($img['image_url']);
    }
    
    // Get seller rating using ReviewRepository
    $rating = $reviewRepo->getSellerRating($row['seller_id']);
    
    // Get category name using CategoryRepository
    $category_name = $categoryRepo->getCategoryName($row['category_id']) ?? 'General';
    
    // Build seller profile image URL
    $seller_profile_image = getBaseUrl() . 'images/icons/profile-svgrepo-com.svg';
    if (!empty($row['profile_image'])) {
        $seller_profile_image = getBaseUrl() . $row['profile_image'];
    }
    
    $response['product'] = [
        'id'                    => (int) $row['product_id'],
        'name'                  => $row['title'],
        'description'           => $row['description'],
        'price'                 => (float) $row['price'],
        'condition'             => $row['condition'],
        'location'              => $row['location'],
        'category_id'           => (int) $row['category_id'],
        'category_name'         => $category_name,
        'image_url'             => $productRepo->getProductImageUrl($row['image_url']),
        'gallery_images'        => $gallery_urls,
        'seller_id'             => (int) $row['seller_id'],
        'seller_name'           => $row['seller_name'],
        'seller_profile_image'  => $seller_profile_image,
        'is_verified'           => (bool) $row['is_verified'],
        'stock_quantity'        => (int) $row['stock_quantity'],
        'avg_rating'            => $rating['avg_rating'],
        'review_count'          => $rating['review_count']
    ];
    $response['success'] = true;
} else {
    $response['error'] = 'Product not found.';
}

$stmt->close();
echo json_encode($response);
?>