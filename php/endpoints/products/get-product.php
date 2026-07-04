<?php
/*
 * ConsuTrade - Get Single Product (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns product details including gallery images and seller info.
 * Products are shown even if out of stock - buyer just can't add to cart.
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'product' => null];

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($productId <= 0) {
    $response['error'] = 'Invalid product';
    echo json_encode($response);
    exit;
}

// Use ProductService for product lookup
$product = $productService->findById($productId);

if (!$product || $product->getStatus() === 'deleted') {
    $response['error'] = 'Product not found';
    echo json_encode($response);
    exit;
}

$gallery = $productImageRepo->findByProductId($productId);
$galleryUrls = [];
$baseUrl = getBaseUrl();
$defaultImageUrl = $baseUrl . 'images/default-product.png';
$documentRoot = $_SERVER['DOCUMENT_ROOT'];

foreach ($gallery as $img) {
    $imageUrl = $img['image_url'];

    // Skip empty or default images
    if (empty($imageUrl) || strpos($imageUrl, 'default-product.png') !== false) {
        continue;
    }

    // Check if file exists
    $cleanPath = ltrim($imageUrl, '/');
    $fullPath = $documentRoot . '/' . $cleanPath;

    if (file_exists($fullPath)) {
        $galleryUrls[] = $baseUrl . $cleanPath;
    } else {
        error_log("[get-product] Gallery image missing: $fullPath");
    }
}

$rating = $reviewRepo->getSellerRating($product->getSellerId());
$categoryName = $categoryRepo->findNameById($product->getCategoryId()) ?? 'General';

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
    'image_url' => $productService->getImageUrl($product->getImageUrl()),
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
