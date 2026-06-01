<?php
/*
 * ConsuTrade - Add Product Endpoint
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

if (!$isLoggedIn || !$currentUser instanceof Seller) {
    $_SESSION['error'] = 'Unauthorized. Please login as a seller.';
    header('Location: ' . getBaseUrl() . 'admin/add-product.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . getBaseUrl() . 'admin/add-product.php');
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

if (!isset($_FILES['main_image']) || $_FILES['main_image']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'Main product image is required';
}

if (!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);
    header('Location: ' . getBaseUrl() . 'admin/add-product.php');
    exit;
}

$mainImagePath = $productRepo->convertToWebP($_FILES['main_image'], $sellerId, $title, 'main');
if (!$mainImagePath) {
    $_SESSION['error'] = 'Failed to upload main image. Please try again.';
    header('Location: ' . getBaseUrl() . 'admin/add-product.php');
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
$productId = $productRepo->createProduct($product);

if (!$productId) {
    $_SESSION['error'] = 'Failed to create product. Please try again.';
    header('Location: ' . getBaseUrl() . 'admin/add-product.php');
    exit;
}

if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
    $galleryUrls = [];
    $files = $_FILES['gallery_images'];
    $maxImages = min(count($files['name']), 4);

    for ($i = 0; $i < $maxImages; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            $imagePath = $productRepo->convertToWebP($file, $sellerId, $title, 'thumb_' . $i);
            if ($imagePath) {
                $galleryUrls[] = $imagePath;
            }
        }
    }

    if (!empty($galleryUrls)) {
        $productImageRepo->addMultiple($productId, $galleryUrls);
    }
}

$_SESSION['success'] = 'Product added successfully!';
header('Location: ' . getBaseUrl() . 'admin/my-products.php');
exit;
