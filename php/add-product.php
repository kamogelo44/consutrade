<?php
/*
 * ConsuTrade - Add Product Handler
 * Author: Kamogelo Phale
 * 
 * Handles product uploads with automatic WebP image compression
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
    
    // Handle image upload with WebP conversion
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_path = convertToWebP($_FILES['image'], $seller_id, $title);
        if (!$image_path) {
            $errors[] = 'Failed to process image. Please try again.';
        }
    } else {
        $errors[] = 'Please upload a product image';
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        $sql = "INSERT INTO products (seller_id, category_id, product_name, description, price, image_url, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iissds', $seller_id, $category_id, $title, $description, $price, $image_path);
        
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
        // Store errors in session and redirect back
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
 * @return string|false The path to the WebP image or false on failure
 */
function convertToWebP($file, $seller_id, $product_title) {
    // Create upload directory if it doesn't exist (relative to project root)
    $upload_dir = dirname(__DIR__) . '/uploads/products/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $timestamp = time();
    $safe_title = preg_replace('/[^a-zA-Z0-9_-]/', '_', $product_title);
    $safe_title = substr($safe_title, 0, 50); // Limit length
    $filename = $seller_id . '_' . $timestamp . '_' . $safe_title . '.webp';
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
            // Preserve transparency for PNG
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
        
        // Preserve transparency for resized image
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
        $image = null;
        $image = $resized;
    }
    
    // Save as WebP with 80% quality (good balance of size and quality)
    $success = imagewebp($image, $destination, 80);
    
    // Free memory
    $image = null;
    
    if ($success) {
        // Return the web path for database storage (relative to web root)
        return 'uploads/products/' . $filename;
    }
    
    return false;
}
?>