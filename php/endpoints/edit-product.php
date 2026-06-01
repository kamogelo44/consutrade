<?php
/*
 * ConsuTrade - Edit Product Handler
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

if (!$isLoggedIn || !$currentUser instanceof Seller) {
    header('Location: ' . getBaseUrl() . 'admin/login.php');
    exit;
}

$sellerId = $currentUser->getUserId();
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

if ($productId <= 0) {
    $_SESSION['error'] = 'Invalid product.';
    header('Location: ' . getBaseUrl() . 'admin/my-products.php');
    exit;
}

$product = $productRepo->getProductObject($productId);

if (!$product || $product->getSellerId() !== $sellerId) {
    $_SESSION['error'] = 'Product not found.';
    header('Location: ' . getBaseUrl() . 'admin/my-products.php');
    exit;
}

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
    header('Location: ' . getBaseUrl() . 'admin/edit-product.php?id=' . $productId);
    exit;
}

$product->setTitle($title);
$product->setCategoryId($categoryId);
$product->setPrice($price);
$product->setStockQuantity($stockQuantity);
$product->setDescription($description);
$product->setCondition($condition);
$product->setLocation($location);

if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
    $newImage = $productRepo->convertToWebP($_FILES['main_image'], $sellerId, $title, 'main');
    if ($newImage) {
        $productRepo->deleteProductImage($product->getImageUrl());
        $product->setImageUrl($newImage);
    }
}

$result = $productRepo->saveProduct($product);

if ($result) {
    if (isset($_FILES['new_gallery_images']) && !empty($_FILES['new_gallery_images']['name'][0])) {
        $uploadedUrls = [];
        $files = $_FILES['new_gallery_images'];
        $maxImages = min(count($files['name']), 4);

        for ($i = 0; $i < $maxImages; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $singleFile = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                $thumb = $productRepo->convertToWebP($singleFile, $sellerId, $title, 'thumb_' . ($i + time()));
                if ($thumb) {
                    $uploadedUrls[] = $thumb;
                }
            }
        }

        if (!empty($uploadedUrls)) {
            $productImageRepo->addMultiple($productId, $uploadedUrls);
        }
    }

    $_SESSION['success'] = 'Product updated successfully.';
} else {
    $_SESSION['error'] = 'Could not update product.';
}

header('Location: ' . getBaseUrl() . 'admin/edit-product.php?id=' . $productId);
exit;
