<?php
/*
 * ConsuTrade - Add Product Endpoint
 * Author: Kamogelo Phale
 * 
 * Handles product creation with one unified image upload.
 * First image becomes main product photo, rest become gallery images.
 */

require_once dirname(__DIR__, 3) . '/init.php';

if (!$isLoggedIn || !$currentUser instanceof Seller) {
    $_SESSION['error'] = 'Unauthorized. Please login as a seller.';
    header('Location: ' . $baseUrl . 'admin/add-product.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $baseUrl . 'admin/add-product.php');
    exit;
}

$sellerId = $currentUser->getUserId();

$title = trim($_POST['title'] ?? '');
$categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
$price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
$stockQuantity = isset($_POST['stock_quantity']) ? (int) $_POST['stock_quantity'] : 1;
$condition = $_POST['condition'] ?? '';
$location = trim($_POST['location'] ?? '');
$description = trim($_POST['description'] ?? '');

$errors = [];
if (empty($title)) $errors[] = 'Product title is required';
if ($categoryId <= 0) $errors[] = 'Please select a category';
if ($price <= 0) $errors[] = 'Valid price is required';
if ($stockQuantity < 1) $errors[] = 'Stock quantity must be at least 1';
if (empty($description)) $errors[] = 'Description is required';

if (!isset($_FILES['product_images']) || empty($_FILES['product_images']['name'][0])) {
    $errors[] = 'At least one product image is required';
}

if (!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);
    header('Location: ' . $baseUrl . 'admin/add-product.php');
    exit;
}

$uploadedImages = $_FILES['product_images'];
$totalImages = count($uploadedImages['name']);
$maxImages = min($totalImages, 4);

$firstFile = [
    'name' => $uploadedImages['name'][0],
    'type' => $uploadedImages['type'][0],
    'tmp_name' => $uploadedImages['tmp_name'][0],
    'error' => $uploadedImages['error'][0],
    'size' => $uploadedImages['size'][0]
];

// Use ProductService for image upload
$mainImagePath = $productService->uploadImage($firstFile, $sellerId, $title, 'main');

if (!$mainImagePath) {
    $_SESSION['error'] = 'Failed to upload main image. Please try again.';
    header('Location: ' . $baseUrl . 'admin/add-product.php');
    exit;
}

$productData = [
    'seller_id' => $sellerId,
    'category_id' => $categoryId,
    'title' => $title,
    'description' => $description,
    'price' => $price,
    'stock_quantity' => $stockQuantity,
    'condition' => $condition,
    'location' => $location,
    'image_url' => $mainImagePath,
    'status' => 'active'
];

$product = new Product($productData);

// Use ProductService for creation
$productId = $productService->create($product);

if (!$productId) {
    $_SESSION['error'] = 'Failed to create product. Please try again.';
    header('Location: ' . $baseUrl . 'admin/add-product.php');
    exit;
}

$galleryUrls = [];
for ($i = 1; $i < $maxImages; $i++) {
    if ($uploadedImages['error'][$i] === UPLOAD_ERR_OK) {
        $file = [
            'name' => $uploadedImages['name'][$i],
            'type' => $uploadedImages['type'][$i],
            'tmp_name' => $uploadedImages['tmp_name'][$i],
            'error' => $uploadedImages['error'][$i],
            'size' => $uploadedImages['size'][$i]
        ];

        // Use ProductService for image upload
        $imagePath = $productService->uploadImage($file, $sellerId, $title, 'gallery_' . $i);
        if ($imagePath) {
            $galleryUrls[] = $imagePath;
        }
    }
}

if (!empty($galleryUrls)) {
    $productImageRepo->createMultiple($productId, $galleryUrls);
}

$_SESSION['success'] = 'Product added successfully!';
header('Location: ' . $baseUrl . 'admin/my-products.php');
exit;
