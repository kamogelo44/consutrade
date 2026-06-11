<?php
/*
 * ConsuTrade - Edit Product Handler
 * Author: Kamogelo Phale
 * 
 * Handles product updates using unified gallery system.
 * Supports: updating product info, adding new images, deleting images, setting primary image.
 */

require_once dirname(__DIR__, 2) . '/init.php';

// Verify seller access
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

$product = $productRepo->getProductObject($productId);

if (!$product || $product->getSellerId() !== $sellerId) {
    $_SESSION['error'] = 'Product not found.';
    header('Location: ' . $baseUrl . 'admin/my-products.php');
    exit;
}

// Get form data
$title = trim($_POST['title'] ?? '');
$categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
$price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
$stockQuantity = isset($_POST['stock_quantity']) ? (int) $_POST['stock_quantity'] : 1;
$description = trim($_POST['description'] ?? '');
$condition = $_POST['condition'] ?? '';
$location = trim($_POST['location'] ?? '');

// Validate input
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

// Update product basic info
$product->setTitle($title);
$product->setCategoryId($categoryId);
$product->setPrice($price);
$product->setStockQuantity($stockQuantity);
$product->setDescription($description);
$product->setCondition($condition);
$product->setLocation($location);

// ========== HANDLE DELETIONS ==========
$deleteImages = isset($_POST['delete_images']) ? json_decode($_POST['delete_images'], true) : [];
if (!empty($deleteImages)) {
    foreach ($deleteImages as $imageId) {
        $productImageRepo->delete($imageId, $productId);
    }
}

// ========== HANDLE NEW UPLOADS ==========
$newImagePaths = [];
if (isset($_FILES['new_product_images']) && !empty($_FILES['new_product_images']['name'][0])) {
    $files = $_FILES['new_product_images'];
    $totalNew = count($files['name']);

    // Check remaining slots (max 4 total)
    $currentGallery = $productImageRepo->getByProductId($productId);
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

            $imagePath = $productRepo->uploadProductImage($singleFile, $sellerId, $title, 'gallery_' . ($i + time()));
            if ($imagePath) {
                $newImagePaths[] = $imagePath;
            }
        }
    }

    // Add new images to gallery
    if (!empty($newImagePaths)) {
        $productImageRepo->addMultiple($productId, $newImagePaths);
    }
}

// ========== HANDLE IMAGE ORDER AND PRIMARY ==========
$imageOrder = isset($_POST['image_order']) ? json_decode($_POST['image_order'], true) : [];

if (!empty($imageOrder)) {
    // Get current gallery after deletions and additions
    $currentGallery = $productImageRepo->getByProductId($productId);

    // Build a map of gallery image URLs to their IDs
    $galleryUrlToId = [];
    foreach ($currentGallery as $galleryImg) {
        $fullUrl = $productRepo->getImageUrl($galleryImg['image_url']);
        $galleryUrlToId[$fullUrl] = $galleryImg['image_id'];
    }

    $primaryImageId = null;
    $primaryImageUrl = null;
    $newImageIndex = 0;

    foreach ($imageOrder as $imgData) {
        if ($imgData['is_primary']) {
            if (isset($imgData['image_id']) && $imgData['image_id'] > 0) {
                // Existing image - use its ID
                $primaryImageId = $imgData['image_id'];
                // Get its URL from the gallery
                foreach ($currentGallery as $galleryImg) {
                    if ($galleryImg['image_id'] == $primaryImageId) {
                        $primaryImageUrl = $galleryImg['image_url'];
                        break;
                    }
                }
            } else if (isset($imgData['is_new']) && $imgData['is_new'] && isset($imgData['file_index'])) {
                // New image - use the file_index to get the path
                $fileIndex = $imgData['file_index'];
                if (isset($newImagePaths[$fileIndex])) {
                    $primaryImageUrl = $newImagePaths[$fileIndex];
                    // Find its ID in the gallery
                    $fullUrl = $productRepo->getImageUrl($primaryImageUrl);
                    if (isset($galleryUrlToId[$fullUrl])) {
                        $primaryImageId = $galleryUrlToId[$fullUrl];
                    }
                }
            }
            break;
        }
    }

    // Set primary image in gallery
    if ($primaryImageId) {
        $productImageRepo->setPrimary($productId, $primaryImageId);
    }

    // Update product's main image URL
    if ($primaryImageUrl) {
        $product->setImageUrl($primaryImageUrl);
    }
}

// Save product changes
$result = $productRepo->saveProduct($product);

if ($result) {
    $_SESSION['success'] = 'Product updated successfully.';
} else {
    $_SESSION['error'] = 'Could not update product.';
}

header('Location: ' . $baseUrl . 'admin/my-products.php?id=' . $productId);
exit;
