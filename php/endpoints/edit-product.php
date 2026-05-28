<?php
/*
 * ConsuTrade - Edit Product Handler
 * Author: Kamogelo Phale
 * 
 * Handles product editing including main image and gallery updates
 */

require_once dirname(__DIR__, 2) . '/init.php';

if (!$auth->isSellerLoggedIn()) {
    header('Location: ' . getBaseUrl() . 'admin/login.php');
    exit;
}

$seller_id = $current_user_id;
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($product_id <= 0) {
    $_SESSION['error'] = 'Invalid product.';
    header('Location: ' . getBaseUrl() . 'admin/my-products.php');
    exit;
}

// Get product as Product object
$product = $productRepo->getProductObject($product_id);

if (!$product || $product->getSellerId() != $seller_id) {
    $_SESSION['error'] = 'Product not found.';
    header('Location: ' . getBaseUrl() . 'admin/my-products.php');
    exit;
}

// Get form data
$title = trim($_POST['title'] ?? '');
$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
$stock_quantity = isset($_POST['stock_quantity']) ? (int)$_POST['stock_quantity'] : 1;
$description = trim($_POST['description'] ?? '');
$condition = !empty($_POST['condition']) ? trim($_POST['condition']) : '';
$location = trim($_POST['location'] ?? '');

// Validation
$errors = [];
if (empty($title)) $errors[] = 'Product title is required';
if ($category_id <= 0) $errors[] = 'Please select a category';
if ($price <= 0) $errors[] = 'Please enter a valid price';
if ($stock_quantity < 1) $errors[] = 'Stock quantity must be at least 1';
if (empty($description)) $errors[] = 'Description is required';

if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    header('Location: ' . getBaseUrl() . 'admin/edit-product.php?id=' . $product_id);
    exit;
}

// Update product object
$product->setTitle($title);
$product->setCategoryId($category_id);
$product->setPrice($price);
$product->setStockQuantity($stock_quantity);
$product->setDescription($description);
$product->setCondition($condition);
$product->setLocation($location);

// Handle main image replacement
if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
    $new_image = $productRepo->convertToWebP($_FILES['main_image'], $seller_id, $title, 'main');
    if ($new_image) {
        // Delete old main image file
        $productRepo->deleteProductImage($product->getImageUrl());
        $product->setImageUrl($new_image);
    }
}

// Save product using repository
$result = $productRepo->saveProduct($product);

if ($result) {
    // Handle new gallery images using ProductImageRepository
    if (isset($_FILES['new_gallery_images']) && !empty($_FILES['new_gallery_images']['name'][0])) {
        $uploaded_urls = [];
        $files = $_FILES['new_gallery_images'];
        $max_images = min(count($files['name']), 4);

        for ($i = 0; $i < $max_images; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $single_file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                $thumb = $productRepo->convertToWebP($single_file, $seller_id, $title, 'thumb_' . ($i + time()));
                if ($thumb) {
                    $uploaded_urls[] = $thumb;
                }
            }
        }

        if (!empty($uploaded_urls)) {
            $productImageRepo->addMultiple($product_id, $uploaded_urls);
        }
    }

    $_SESSION['success'] = 'Product updated successfully.';
} else {
    $_SESSION['error'] = 'Could not update product.';
}

header('Location: ' . getBaseUrl() . 'admin/edit-product.php?id=' . $product_id);
exit;
?>