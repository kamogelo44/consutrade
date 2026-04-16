<?php
/*
 * ConsuTrade - Get Seller Products
 * Author: Kamogelo Phale
 * 
 * Returns all products for the logged-in seller
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'products' => []];

// Check if user is logged in and is a seller
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'seller') {
    echo json_encode($response);
    exit;
}

$seller_id = $_SESSION['user_id'];
$baseUrl = "/www/consutrade/";

$sql = "SELECT product_id, title as product_name, price, image_url, status, created_at
        FROM products 
        WHERE seller_id = ? 
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
        'created_at' => $row['created_at']
    ];
}

$response['success'] = true;
$stmt->close();
$conn->close();

echo json_encode($response);
exit;
?>