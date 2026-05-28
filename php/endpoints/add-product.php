<?php
/*
 * ConsuTrade - Add Product Endpoint
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

if (!$auth->isSellerLoggedIn()) {
    $_SESSION['error'] = 'Unauthorized. Please login as a seller.';
    header('Location: ' . getBaseUrl() . 'admin/add-product.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . getBaseUrl() . 'admin/add-product.php');
    exit;
}

$seller_id = $current_user_id;
$title = trim($_POST['title'] ?? '');
$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
$stock_quantity = isset($_POST['stock_quantity']) ? (int)$_POST['stock_quantity'] : 1;
$condition = $_POST['condition'] ?? '';
$location = trim($_POST['location'] ?? '');
$description = trim($_POST['description'] ?? '');

// Validation
$errors = [];
if (empty($title)) $errors[] = 'Product title is required';
if ($category_id <= 0) $errors[] = 'Please select a category';
if ($price <= 0) $errors[] = 'Valid price is required';
if ($stock_quantity < 1) $errors[] = 'Stock quantity must be at least 1';
if (empty($description)) $errors[] = 'Description is required';

// Handle main image
if (!isset($_FILES['main_image']) || $_FILES['main_image']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'Main product image is required';
}

if (!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);
    header('Location: ' . getBaseUrl() . 'admin/add-product.php');
    exit;
}

// Convert main image to WebP
$main_image_path = $productRepo->convertToWebP($_FILES['main_image'], $seller_id, $title, 'main');
if (!$main_image_path) {
    $_SESSION['error'] = 'Failed to upload main image. Please try again.';
    header('Location: ' . getBaseUrl() . 'admin/add-product.php');
    exit;
}

// Insert product
$sql = "INSERT INTO products (seller_id, category_id, title, description, price, stock_quantity, `condition`, location, image_url, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iissdiss', $seller_id, $category_id, $title, $description, $price, $stock_quantity, $condition, $location, $main_image_path);
$stmt->execute();
$product_id = $stmt->insert_id;
$stmt->close();

if (!$product_id) {
    $_SESSION['error'] = 'Failed to create product. Please try again.';
    header('Location: ' . getBaseUrl() . 'admin/add-product.php');
    exit;
}

// Handle gallery images
if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
    $gallery_urls = [];
    $files = $_FILES['gallery_images'];
    $max_images = min(count($files['name']), 4);
    
    for ($i = 0; $i < $max_images; $i++) {
        $file = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i]
        ];
        
        $image_path = $productRepo->convertToWebP($file, $seller_id, $title, 'thumb_' . $i);
        if ($image_path) {
            $gallery_urls[] = $image_path;
        }
    }
    
    if (!empty($gallery_urls)) {
        $productRepo->addProductGalleryImages($product_id, $gallery_urls);
    }
}

$_SESSION['success'] = 'Product added successfully!';
header('Location: ' . getBaseUrl() . 'admin/my-products.php');
exit;
?>