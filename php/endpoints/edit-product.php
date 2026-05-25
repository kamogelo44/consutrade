<?php
/*
 * ConsuTrade - Edit Product Handler
 * Author: Kamogelo Phale
 * 
 * Handles product editing including main image and gallery updates
 */

require_once __DIR__ . '/../init.php';

if (!$is_logged_in || $current_user['role'] !== 'seller') {
    header('Location: ' . getBaseUrl() . 'admin/login.php');
    exit;
}

$seller_id  = $current_user_id;
$product_id = (int) ($_POST['product_id'] ?? 0);

if ($product_id <= 0) {
    $_SESSION['error'] = 'Invalid product.';
    header('Location: ' . getBaseUrl() . 'admin/my-products.php');
    exit;
}

// Verify product belongs to this seller
$product = $productRepo->getProductForEdit($product_id, $seller_id);
if (!$product) {
    $_SESSION['error'] = 'Product not found.';
    header('Location: ' . getBaseUrl() . 'admin/my-products.php');
    exit;
}

// Get form data
$title          = trim($_POST['title'] ?? '');
$category_id    = (int) ($_POST['category_id'] ?? 0);
$price          = (float) ($_POST['price'] ?? 0);
$stock_quantity = (int) ($_POST['stock_quantity'] ?? 1);
$description    = trim($_POST['description'] ?? '');
$condition      = !empty($_POST['condition']) ? trim($_POST['condition']) : null;
$location       = trim($_POST['location'] ?? '');

$errors = [];

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

if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    header('Location: ' . getBaseUrl() . 'admin/edit-product.php?id=' . $product_id);
    exit;
}

// Handle main image replacement
$main_image_path = $product['image_url']; // Keep existing by default

if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
    $new_image = $productRepo->convertToWebP($_FILES['main_image'], $seller_id, $title, 'main');
    if ($new_image) {
        // Delete old main image file
        $productRepo->deleteProductImage($main_image_path);
        $main_image_path = $new_image;
    }
}

// Update product in database
$data = [
    'title'          => $title,
    'description'    => $description,
    'price'          => $price,
    'stock_quantity' => $stock_quantity,
    'condition'      => $condition,
    'location'       => $location,
    'category_id'    => $category_id,
];

// Also update image_url if changed
$sql = "UPDATE products SET
            title = ?, description = ?, price = ?, stock_quantity = ?,
            `condition` = ?, location = ?, category_id = ?, image_url = ?
        WHERE product_id = ? AND seller_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'ssdissisi',
    $data['title'],
    $data['description'],
    $data['price'],
    $data['stock_quantity'],
    $data['condition'],
    $data['location'],
    $data['category_id'],
    $main_image_path,
    $product_id,
    $seller_id
);

if ($stmt->execute()) {
    // Handle new gallery images
    if (isset($_FILES['new_gallery_images']) && !empty($_FILES['new_gallery_images']['name'][0])) {
        $uploaded_urls = [];
        $files = $_FILES['new_gallery_images'];

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $single_file = [
                    'tmp_name' => $files['tmp_name'][$i],
                    'name'     => $files['name'][$i],
                    'error'    => $files['error'][$i],
                ];
                $thumb = $productRepo->convertToWebP($single_file, $seller_id, $title, 'thumb_' . ($i + time()));
                if ($thumb) {
                    $uploaded_urls[] = $thumb;
                }
            }
        }

        if (!empty($uploaded_urls)) {
            $productRepo->addProductGalleryImages($product_id, $uploaded_urls);
        }
    }

    $_SESSION['success'] = 'Product updated.';
} else {
    $_SESSION['error'] = 'Could not update product.';
}

$stmt->close();

header('Location: ' . getBaseUrl() . 'admin/edit-product.php?id=' . $product_id);
exit;