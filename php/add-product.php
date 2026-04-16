<?php
/*
 * ConsuTrade - Add Product Handler
 * Author: Kamogelo Phale
 * 
 * Handles product uploads with automatic WebP image compression for main image and thumbnails
 */

session_start();
require_once 'config.php';

$baseUrl = "/www/consutrade/";

// Check if user is logged in and is a seller
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'seller') {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $condition = !empty($_POST['condition']) ? trim($_POST['condition']) : NULL;
    $location = trim($_POST['location'] ?? '');
    $seller_id = $_SESSION['user_id'];
    
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
        
        $sql = "INSERT INTO products (seller_id, category_id, title, description, price, `condition`, location, image_url, gallery_images, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
        $condition_value = ($condition === NULL) ? NULL : $condition;
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iissdssss', $seller_id, $category_id, $title, $description, $price, $condition, $location, $main_image_path, $gallery_json);
        
        if ($stmt->execute()) {
            $_SESSION['flash'] = 'Product added successfully!';
            $success = true;
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
        
        $stmt->close();
    }
    
    $conn->close();
    
    if ($success) {
        header('Location: ' . $baseUrl . 'admin/seller-dashboard.php');
        exit;
    } else {
        $_SESSION['product_errors'] = $errors;
        header('Location: ' . $baseUrl . 'admin/seller-dashboard.php');
        exit;
    }
}

/**
 * Convert uploaded image to WebP format
 * 
 * @param array $file The uploaded file from $_FILES
 * @param int $seller_id The seller's ID
 * @param string $product_title The product title for filename
 * @param string $prefix Optional prefix for filename
 * @return string|false The path to the WebP image or false on failure
 */
function convertToWebP($file, $seller_id, $product_title, $prefix = 'main') {
    // Create upload directory if it doesn't exist
    $upload_dir = dirname(__DIR__) . '/uploads/products/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $timestamp = time();
    $safe_title = preg_replace('/[^a-zA-Z0-9_-]/', '_', $product_title);
    $safe_title = substr($safe_title, 0, 50);
    $filename = $seller_id . '_' . $timestamp . '_' . $prefix . '_' . $safe_title . '.webp';
    $destination = $upload_dir . $filename;
    
    // Get image info
    $source = $file['tmp_name'];
    $image_info = getimagesize($source);
    
    if (!$image_info) {
        return false;
    }
    
    // Create image resource based on original type
    switch ($image_info['mime']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    if (!$image) {
        return false;
    }
    
    // Resize image if too large (max 1200x1200)
    $orig_width = imagesx($image);
    $orig_height = imagesy($image);
    $max_dimension = 1200;
    
    if ($orig_width > $max_dimension || $orig_height > $max_dimension) {
        $ratio = min($max_dimension / $orig_width, $max_dimension / $orig_height);
        $new_width = round($orig_width * $ratio);
        $new_height = round($orig_height * $ratio);
        
        $resized = imagecreatetruecolor($new_width, $new_height);
        
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
        $image = $resized;
    }
    
    // Save as WebP with 80% quality
    $success = imagewebp($image, $destination, 80);
    
    if ($success) {
        return 'uploads/products/' . $filename;
    }
    
    return false;
}
?>