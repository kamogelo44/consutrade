<?php
/*
 * ConsuTrade - Add Product Handler
 * Author: Kamogelo Phale
 * 
 * Handles product creation with simplified gallery system.
 * Client-side compression handles image processing.
 */

require_once dirname(__DIR__, 3) . '/init.php';

if (!$isLoggedIn || !$currentUser->hasRole('seller')) {
    $_SESSION['error'] = 'You must be logged in as a seller to add products.';
    header('Location: ' . $baseUrl . 'admin/login.php');
    exit;
}

if (!$auth->isSeller()) {
    $auth->switchRole('seller');
}

$sellerId = $currentUser->getUserId();

$title = trim($_POST['title'] ?? '');
$categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
$price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
$stockQuantity = isset($_POST['stock_quantity']) ? (int) $_POST['stock_quantity'] : 1;
$description = trim($_POST['description'] ?? '');
$condition = $_POST['condition'] ?? '';
$location = trim($_POST['location'] ?? '');

$errors = [];
if (empty($title)) $errors[] = 'Product title is required';
if ($categoryId <= 0) $errors[] = 'Please select a category';
if ($price <= 0) $errors[] = 'Please enter a valid price';
if ($stockQuantity < 1) $errors[] = 'Stock quantity must be at least 1';
if (empty($description)) $errors[] = 'Description is required';

if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    header('Location: ' . $baseUrl . 'admin/add-product.php');
    exit;
}

// Create product using Product object
$product = new Product([
    'title' => $title,
    'category_id' => $categoryId,
    'price' => $price,
    'stock_quantity' => $stockQuantity,
    'description' => $description,
    'condition' => $condition,
    'location' => $location,
    'seller_id' => $sellerId,
    'status' => 'active'
]);

$productId = $productRepo->create($product);

if (!$productId) {
    $_SESSION['error'] = 'Failed to create product. Please try again.';
    header('Location: ' . $baseUrl . 'admin/add-product.php');
    exit;
}

$newProduct = $productService->findById($productId);

// Handle images
$newImagePaths = [];
$imageService = new ProductImageService();

if (isset($_FILES['product_images']) && !empty($_FILES['product_images']['name'][0])) {
    $files = $_FILES['product_images'];
    $totalImages = count($files['name']);
    $maxImages = min($totalImages, 4);

    for ($i = 0; $i < $maxImages; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $singleFile = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];

            $imagePath = $imageService->uploadImage($singleFile, $sellerId, $title, ($i === 0) ? 'main' : 'gallery_' . $i);
            if ($imagePath) {
                $newImagePaths[] = $imagePath;
                if ($i === 0) {
                    $newProduct->setImageUrl($imagePath);
                }
            }
        }
    }

    if (!empty($newImagePaths)) {
        $productImageRepo->createMultiple($productId, $newImagePaths);
    }
}

// Update product with main image if available
if (!empty($newImagePaths)) {
    $newProduct->setImageUrl($newImagePaths[0]);
    $productService->update($newProduct);
}

$_SESSION['success'] = 'Product created successfully!';
header('Location: ' . $baseUrl . 'admin/my-products.php');
exit;
