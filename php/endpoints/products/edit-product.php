<?php
/*
 * ConsuTrade - Edit Product Handler
 * Author: Kamogelo Phale
 * 
 * Handles product updates using unified gallery system.
 * Supports: updating product info, adding new images, deleting images, setting primary image.
 */

require_once dirname(__DIR__, 3) . '/init.php';

if (!$isLoggedIn || !$currentUser instanceof Seller) {
    header('Location: ' . $baseUrl . 'admin/login.php');
    exit;
}

$sellerId = $currentUser->getUserId();
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

if ($productId <= 0) {
    $_SESSION['error'] = 'Invalid product.';
    header('Location: ' . $baseUrl . 'admin/my-products.php');
    exit;
}

// Use ProductService for product lookup
$product = $productService->findById($productId);

if (!$product || $product->getSellerId() !== $sellerId) {
    $_SESSION['error'] = 'Product not found.';
    header('Location: ' . $baseUrl . 'admin/my-products.php');
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
    header('Location: ' . $baseUrl . 'admin/edit-product.php?id=' . $productId);
    exit;
}

$product->setTitle($title);
$product->setCategoryId($categoryId);
$product->setPrice($price);
$product->setStockQuantity($stockQuantity);
$product->setDescription($description);
$product->setCondition($condition);
$product->setLocation($location);

// Use ProductImageService for image operations
$imageService = new ProductImageService();

$deleteImages = isset($_POST['delete_images']) ? json_decode($_POST['delete_images'], true) : [];
if (!empty($deleteImages)) {
    foreach ($deleteImages as $imageId) {
        $productImageRepo->delete($imageId, $productId);
    }
}

$newImagePaths = [];
if (isset($_FILES['new_product_images']) && !empty($_FILES['new_product_images']['name'][0])) {
    $files = $_FILES['new_product_images'];
    $totalNew = count($files['name']);

    $currentGallery = $productImageRepo->findByProductId($productId);
    $currentCount = count($currentGallery);
    $maxAllowed = 4;
    $remainingSlots = $maxAllowed - $currentCount;
    $uploadCount = min($totalNew, $remainingSlots);

    for ($i = 0; $i < $uploadCount; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $singleFile = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];

            // Use ProductImageService to upload
            $imagePath = $imageService->uploadImage($singleFile, $sellerId, $title, 'gallery_' . ($i + time()));
            if ($imagePath) {
                $newImagePaths[] = $imagePath;
            }
        }
    }

    if (!empty($newImagePaths)) {
        $productImageRepo->createMultiple($productId, $newImagePaths);
    }
}

$imageOrder = isset($_POST['image_order']) ? json_decode($_POST['image_order'], true) : [];

if (!empty($imageOrder)) {
    $currentGallery = $productImageRepo->findByProductId($productId);

    $galleryUrlToId = [];
    foreach ($currentGallery as $galleryImg) {
        $fullUrl = $productService->getImageUrl($galleryImg['image_url']);
        $galleryUrlToId[$fullUrl] = $galleryImg['image_id'];
    }

    $primaryImageId = null;
    $primaryImageUrl = null;

    foreach ($imageOrder as $imgData) {
        if ($imgData['is_primary']) {
            if (isset($imgData['image_id']) && $imgData['image_id'] > 0) {
                $primaryImageId = $imgData['image_id'];
                foreach ($currentGallery as $galleryImg) {
                    if ($galleryImg['image_id'] == $primaryImageId) {
                        $primaryImageUrl = $galleryImg['image_url'];
                        break;
                    }
                }
            } else if (isset($imgData['is_new']) && $imgData['is_new'] && isset($imgData['file_index'])) {
                $fileIndex = $imgData['file_index'];
                if (isset($newImagePaths[$fileIndex])) {
                    $primaryImageUrl = $newImagePaths[$fileIndex];
                    $fullUrl = $productService->getImageUrl($primaryImageUrl);
                    if (isset($galleryUrlToId[$fullUrl])) {
                        $primaryImageId = $galleryUrlToId[$fullUrl];
                    }
                }
            }
            break;
        }
    }

    if ($primaryImageId) {
        $productImageRepo->setPrimary($productId, $primaryImageId);
    }

    if ($primaryImageUrl) {
        $product->setImageUrl($primaryImageUrl);
    }
}

// Use ProductService for update
$result = $productService->update($product);

if ($result) {
    $_SESSION['success'] = 'Product updated successfully.';
} else {
    $_SESSION['error'] = 'Could not update product.';
}

header('Location: ' . $baseUrl . 'admin/my-products.php?id=' . $productId);
exit;
