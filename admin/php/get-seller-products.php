<?php
/*
 * ConsuTrade - Get Seller Products
 * Author: Kamogelo Phale
 * 
 * Returns products for the logged-in seller (their own dashboard)
 */

require_once dirname(__DIR__) . '/../php/config.php';
require_once dirname(__DIR__) . '/../php/helpers.php';

header('Content-Type: application/json');

$response = ['success' => false, 'products' => []];
$baseUrl = getBaseUrl();

// Check if seller is logged in
if (!isSellerLoggedIn()) {
    echo json_encode($response);
    exit;
}

startSession('seller');

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