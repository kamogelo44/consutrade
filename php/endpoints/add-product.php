<?php
/*
 * ConsuTrade - Add Product Handler
 * Author: Kamogelo Phale
 * 
 * Handles product uploads with automatic WebP image compression
 */

require_once __DIR__ . '/../init.php';

if (!$is_logged_in || $current_user['role'] !== 'seller') {
    header('Location: ' . getBaseUrl() . 'index.php');
    exit;
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title          = trim($_POST['title'] ?? '');
    $category_id    = (int) ($_POST['category_id'] ?? 0);
    $price          = (float) ($_POST['price'] ?? 0);
    $stock_quantity = (int) ($_POST['stock_quantity'] ?? 1);
    $description    = trim($_POST['description'] ?? '');
    $condition      = !empty($_POST['condition']) ? trim($_POST['condition']) : null;
    $location       = trim($_POST['location'] ?? '');
    $seller_id      = $current_user_id;

    // Validate
    if (empty($title)) {
        $errors[] = 'Product title is required';
    }
    if ($category_id <= 0) {
        $errors[] = 'Please select a category';
    }
    if ($price <= 0) {
        $errors[] = 'Please enter a valid price';
    }
    if ($stock_quantity < 1) {
        $errors[] = 'Stock quantity must be at least 1';
    }
    if (empty($description)) {
        $errors[] = 'Product description is required';
    }

    // Handle main image
    $main_image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $main_image_path = $productRepo->convertToWebP($_FILES['image'], $seller_id, $title, 'main');
        if (!$main_image_path) {
            $errors[] = 'Failed to process main image.';
        }
    } else {
        $errors[] = 'Please upload a product image';
    }

    // Handle thumbnail images (up to 4)
    $gallery_images = [];
    for ($i = 0; $i <= 3; $i++) {
        $key = 'thumbnail_' . $i;
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
            $thumb = $productRepo->convertToWebP($_FILES[$key], $seller_id, $title, 'thumb_' . $i);
            if ($thumb) {
                $gallery_images[] = $thumb;
            }
        }
    }

    // Save to database
    if (empty($errors)) {
        $sql = "INSERT INTO products (seller_id, category_id, title, description, price,
                    stock_quantity, `condition`, location, image_url, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'iissdiss',
            $seller_id,
            $category_id,
            $title,
            $description,
            $price,
            $stock_quantity,
            $condition,
            $location,
            $main_image_path
        );

        if ($stmt->execute()) {
            $product_id = $stmt->insert_id;

            // Add gallery images to product_images table
            if (!empty($gallery_images)) {
                $productRepo->addProductGalleryImages($product_id, $gallery_images);
            }

            $_SESSION['flash'] = 'Product added.';
            $success = true;
        } else {
            $errors[] = 'Could not save product.';
        }

        $stmt->close();
    }

    if ($success) {
        header('Location: ' . getBaseUrl() . 'admin/seller-dashboard.php');
        exit;
    } else {
        $_SESSION['product_errors'] = $errors;
        header('Location: ' . getBaseUrl() . 'admin/add-product.php');
        exit;
    }
}