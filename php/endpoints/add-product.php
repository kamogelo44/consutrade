<?php
/*
 * ConsuTrade - Add Product Handler
 * Author: Kamogelo Phale
 * 
 * Handles product uploads with automatic WebP image compression for main image and thumbnails
 */

require_once __DIR__ . '/../init.php';

// Check if user is logged in and is a seller
if (!$is_logged_in || $current_user['role'] !== 'seller') {
    header('Location: ' . getBaseUrl() . 'index.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $stock_quantity = (int)($_POST['stock_quantity'] ?? 1);
    $description = trim($_POST['description'] ?? '');
    $condition = !empty($_POST['condition']) ? trim($_POST['condition']) : NULL;
    $location = trim($_POST['location'] ?? '');
    $seller_id = $current_user_id;
    
    // Validate inputs
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
    
    // Handle main image upload with WebP conversion
    $main_image_path = '';
    $gallery_images = [];
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $main_image_path = convertToWebP($_FILES['image'], $seller_id, $title, 'main');
        if (!$main_image_path) {
            $errors[] = 'Failed to process main image. Please try again.';
        }
    } else {
        $errors[] = 'Please upload a product image';
    }
    
    // Handle thumbnail images (up to 4)
    for ($i = 0; $i <= 3; $i++) {
        $thumbnail_key = 'thumbnail_' . $i;
        if (isset($_FILES[$thumbnail_key]) && $_FILES[$thumbnail_key]['error'] === UPLOAD_ERR_OK) {
            $thumbnail_path = convertToWebP($_FILES[$thumbnail_key], $seller_id, $title, 'thumb_' . $i);
            if ($thumbnail_path) {
                $gallery_images[] = $thumbnail_path;
            }
        }
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        // Convert gallery images array to JSON for storage
        $gallery_json = !empty($gallery_images) ? json_encode($gallery_images) : null;
        
        $sql = "INSERT INTO products (seller_id, category_id, title, description, price, stock_quantity, `condition`, location, image_url, gallery_images, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iissdsssss', $seller_id, $category_id, $title, $description, $price, $stock_quantity, $condition, $location, $main_image_path, $gallery_json);
        
        if ($stmt->execute()) {
            $_SESSION['flash'] = 'Product added successfully!';
            $success = true;
        } else {
            $errors[] = 'Database error: ' . $conn->error;
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
?>