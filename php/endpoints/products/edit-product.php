<?php
/*
 * ConsuTrade - Edit Product Handler (FIXED)
 * Author: Kamogelo Phale
 * 
 * FIXED: Correct slot math after deletions
 * FIXED: Properly calculates remaining slots
 */

require_once dirname(__DIR__, 3) . '/init.php';

function getUploadErrorMessage($errorCode)
{
    return match ($errorCode) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File too large (max 20MB)',
        UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
        default => 'Unknown upload error',
    };
}

// Multi-role support
if (!$isLoggedIn || !$currentUser->hasRole('seller')) {
    $_SESSION['error'] = 'You must be logged in as a seller to edit products.';
    header('Location: ' . $baseUrl . 'admin/login.php');
    exit;
}

if (!$auth->isSeller()) {
    $auth->switchRole('seller');
}

$sellerId = $currentUser->getUserId();
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

if ($productId <= 0) {
    $_SESSION['error'] = 'Invalid product.';
    header('Location: ' . $baseUrl . 'admin/my-products.php');
    exit;
}

$product = $productService->findById($productId);

if (!$product || $product->getSellerId() !== $sellerId) {
    $_SESSION['error'] = 'Product not found or access denied.';
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

// Validate
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

// Update product object
$product->setTitle($title);
$product->setCategoryId($categoryId);
$product->setPrice($price);
$product->setStockQuantity($stockQuantity);
$product->setDescription($description);
$product->setCondition($condition);
$product->setLocation($location);

$imageService = new ProductImageService();

// ============================================================
// HANDLE IMAGE DELETIONS
// ============================================================
$deleteImages = isset($_POST['delete_images']) ? json_decode($_POST['delete_images'], true) : [];
$deletedCount = 0;

if (!empty($deleteImages)) {
    foreach ($deleteImages as $imageId) {
        $imageRecord = $productImageRepo->findById($imageId);
        if ($imageRecord) {
            if (method_exists($imageService, 'deleteImageFile')) {
                $imageService->deleteImageFile($imageRecord['image_url']);
            }
        }
        if ($productImageRepo->delete($imageId, $productId)) {
            $deletedCount++;
        }
    }
}

// ============================================================
// HANDLE NEW IMAGE UPLOADS - FIXED SLOT MATH
// ============================================================

// FIXED: Query fresh gallery data AFTER deletions are processed
$currentGallery = $productImageRepo->findByProductId($productId);
$realImageCount = 0;
foreach ($currentGallery as $img) {
    $imgUrl = $productService->getImageUrl($img['image_url']);
    if (strpos($imgUrl, 'default-product.png') === false) {
        $realImageCount++;
    }
}

$maxAllowed = 4;
//Direct calculation from current state (deletions already reflected)
$remainingSlots = max(0, $maxAllowed - $realImageCount);

$newImagePaths = [];
$failedUploads = 0;

if (isset($_FILES['new_product_images']) && !empty($_FILES['new_product_images']['name'][0])) {
    $files = $_FILES['new_product_images'];
    $totalNew = count($files['name']);
    $uploadCount = min($totalNew, $remainingSlots);

    if ($uploadCount <= 0) {
        $_SESSION['warning'] = 'Cannot add more images. Maximum 4 images per product.';
    } else {
        for ($i = 0; $i < $uploadCount; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $singleFile = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];

                $imagePath = $imageService->uploadImage($singleFile, $sellerId, $title, 'gallery_' . ($i + time()));
                if ($imagePath) {
                    $newImagePaths[] = $imagePath;
                } else {
                    $failedUploads++;
                }
            } else {
                $failedUploads++;
            }
        }

        if (!empty($newImagePaths)) {
            $productImageRepo->createMultiple($productId, $newImagePaths);
        }
    }
}

// ============================================================
// HANDLE IMAGE ORDER AND PRIMARY IMAGE
// ============================================================
$imageOrder = isset($_POST['image_order']) ? json_decode($_POST['image_order'], true) : [];

if (!empty($imageOrder)) {
    $updatedGallery = $productImageRepo->findByProductId($productId);

    $galleryUrlToId = [];
    foreach ($updatedGallery as $galleryImg) {
        $fullUrl = $productService->getImageUrl($galleryImg['image_url']);
        $galleryUrlToId[$fullUrl] = $galleryImg['image_id'];
    }

    $primaryImageId = null;
    $primaryImageUrl = null;

    foreach ($imageOrder as $index => $imgData) {
        $targetImageId = null;

        if (isset($imgData['image_id']) && $imgData['image_id'] > 0) {
            $targetImageId = $imgData['image_id'];
        } else if (!empty($imgData['is_new']) && !empty($newImagePaths)) {
            $primaryImageUrl = $newImagePaths[0];
            $fullUrl = $productService->getImageUrl($primaryImageUrl);
            if (isset($galleryUrlToId[$fullUrl])) {
                $targetImageId = $galleryUrlToId[$fullUrl];
            }
        }

        if ($targetImageId && !empty($imgData['is_primary'])) {
            $primaryImageId = $targetImageId;
            foreach ($updatedGallery as $galleryImg) {
                if ($galleryImg['image_id'] == $primaryImageId) {
                    $primaryImageUrl = $galleryImg['image_url'];
                    break;
                }
            }
        }
    }

    if (!$primaryImageId && !$primaryImageUrl && !empty($updatedGallery)) {
        foreach ($updatedGallery as $galleryImg) {
            $imgUrl = $productService->getImageUrl($galleryImg['image_url']);
            if (strpos($imgUrl, 'default-product.png') === false) {
                $primaryImageId = $galleryImg['image_id'];
                $primaryImageUrl = $galleryImg['image_url'];
                break;
            }
        }
    }

    if ($primaryImageId) {
        $productImageRepo->setPrimary($productId, $primaryImageId);
    }

    if ($primaryImageUrl && strpos($primaryImageUrl, 'default-product.png') === false) {
        $product->setImageUrl($primaryImageUrl);
    } else {
        $product->setImageUrl('');
    }
}

// ============================================================
// SAVE PRODUCT
// ============================================================
$result = $productService->update($product);

if ($result) {
    $_SESSION['success'] = 'Product updated successfully.';
    if ($failedUploads > 0) {
        $_SESSION['warning'] = 'Product saved, but ' . $failedUploads . ' image(s) failed to upload.';
    }
} else {
    $_SESSION['error'] = 'Could not update product.';
}

header('Location: ' . $baseUrl . 'admin/my-products.php');
exit;
