<?php
/*
 * ConsuTrade - Get Seller Products
 * Author: Kamogelo Phale
 * 
 * Returns products for a specific seller (public view) or logged-in seller
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'products' => []];
$baseUrl = "/www/consutrade/";

// Check if we're viewing a specific seller's products (public view)
$view_seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;

// If viewing a specific seller, get their products (no login required)
if ($view_seller_id > 0) {
    $seller_id = $view_seller_id;
    
    $sql = "SELECT p.product_id, p.title as product_name, p.price, p.image_url, p.status, p.created_at,
            u.full_name as seller_name, u.profile_image as seller_profile_image, u.id_verified as is_verified
            FROM products p
            LEFT JOIN users u ON p.seller_id = u.user_id
            WHERE p.seller_id = ? AND p.status != 'deleted'
            ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Image path
        $imagePath = $row['image_url'];
        
        // Check if the image file actually exists
        if (!empty($imagePath)) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $imagePath;
            if (file_exists($fullPath)) {
                $imagePath = $baseUrl . $imagePath;
            } else {
                $imagePath = $baseUrl . 'images/default-product.png';
            }
        } else {
            $imagePath = $baseUrl . 'images/default-product.png';
        }
        
        // Get seller profile image
        $sellerProfileImage = $row['seller_profile_image'];
        if (!empty($sellerProfileImage)) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $sellerProfileImage;
            if (!file_exists($fullPath)) {
                $sellerProfileImage = null;
            }
        }
        
        $response['products'][] = [
            'id' => $row['product_id'],
            'name' => $row['product_name'],
            'price' => (float)$row['price'],
            'image' => $imagePath,
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'seller_name' => $row['seller_name'] ?? 'Seller',
            'profile_image' => $sellerProfileImage,
            'is_verified' => (bool)($row['is_verified'] ?? false)
        ];
    }
    
    $response['success'] = true;
    $stmt->close();
    $conn->close();
    
    echo json_encode($response);
    exit;
}

// Otherwise, return products for the logged-in seller (their own dashboard)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'seller') {
    echo json_encode($response);
    exit;
}

$seller_id = $_SESSION['user_id'];

// Get seller info for the logged-in user
$user_sql = "SELECT full_name, profile_image, id_verified FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param('i', $seller_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_info = $user_result->fetch_assoc();
$user_stmt->close();

$sql = "SELECT product_id, title as product_name, price, image_url, status, created_at
        FROM products 
        WHERE seller_id = ? AND status != 'deleted'
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Image path
    $imagePath = $row['image_url'];
    
    // Check if the image file actually exists
    if (!empty($imagePath)) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $imagePath;
        if (file_exists($fullPath)) {
            $imagePath = $baseUrl . $imagePath;
        } else {
            $imagePath = $baseUrl . 'images/default-product.png';
        }
    } else {
        $imagePath = $baseUrl . 'images/default-product.png';
    }
    
    $response['products'][] = [
        'id' => $row['product_id'],
        'name' => $row['product_name'],
        'price' => (float)$row['price'],
        'image' => $imagePath,
        'status' => $row['status'],
        'created_at' => $row['created_at'],
        'seller_name' => $user_info['full_name'] ?? 'You',
        'profile_image' => $user_info['profile_image'] ?? null,
        'is_verified' => (bool)($user_info['id_verified'] ?? false)
    ];
}

$response['success'] = true;
$stmt->close();
$conn->close();

echo json_encode($response);
exit;
?>