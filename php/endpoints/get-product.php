<?php
/*
 * ConsuTrade - Get Single Product (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns product details including gallery images and seller info.
 * Products are shown even if out of stock - buyer just can't add to cart.
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'product' => null];

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($productId <= 0) {
    $response['error'] = 'Invalid product';
    echo json_encode($response);
    exit;
}

$product = $productRepo->getProductObject($productId);

// Check if product exists and is not deleted
if (!$product || $product->getStatus() === 'deleted') {
    $response['error'] = 'Product not found';
    echo json_encode($response);
    exit;
}

// Product exists - show it even if out of stock or suspended
// Buyers will see the status via badges

// Get gallery images
$gallery = $productImageRepo->getByProductId($productId);
$galleryUrls = [];
foreach ($gallery as $img) {
    $galleryUrls[] = $productRepo->getImageUrl($img['image_url']);
}

// Get seller rating
$rating = $reviewRepo->getSellerRating($product->getSellerId());
$categoryName = $categoryRepo->getCategoryName($product->getCategoryId()) ?? 'General';

// Get seller info with phone and email
$seller = $userRepo->findById($product->getSellerId());
$sellerProfileImage = $seller ? $seller->getProfileImageUrl() : getBaseUrl() . 'images/icons/profile-svgrepo-com.svg';

$response['product'] = [
    'id' => $product->getProductId(),
    'name' => $product->getTitle(),
    'description' => $product->getDescription(),
    'price' => $product->getPrice(),
    'formatted_price' => $product->getFormattedPrice(),
    'condition' => $product->getCondition(),
    'location' => $product->getLocation(),
    'category_id' => $product->getCategoryId(),
    'category_name' => $categoryName,
    'image_url' => $productRepo->getImageUrl($product->getImageUrl()),
    'gallery_images' => $galleryUrls,
    'seller_id' => $product->getSellerId(),
    'seller_name' => $seller ? $seller->getFullName() : 'Unknown',
    'seller_profile_image' => $sellerProfileImage,
    'seller_phone' => $seller ? $seller->getPhone() : '',
    'seller_email' => $seller ? $seller->getEmail() : '',
    'is_verified' => $seller ? $seller->isVerified() : false,
    'stock_quantity' => $product->getStockQuantity(),
    'status' => $product->getStatus(),
    'avg_rating' => $rating['avg_rating'] ?? 0,
    'review_count' => $rating['review_count'] ?? 0
];
$response['success'] = true;

echo json_encode($response);
exit;
