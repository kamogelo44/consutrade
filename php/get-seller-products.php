<?php
/*
 * ConsuTrade - Get Seller Products
 * Author: Kamogelo Phale
 * 
 * Returns all products for the logged-in seller
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Using 'title' as product_name since your table uses 'title'
$sql = "SELECT product_id, title as product_name, price, image_url, status, created_at
        FROM products 
        WHERE seller_id = ? 
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    $response['message'] = 'SQL Error: ' . $conn->error;
    echo json_encode($response);
    exit;
}

$stmt->bind_param('i', $seller_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Fix image path
    $imagePath = $row['image_url'];
    if (empty($imagePath)) {
        $imagePath = 'images/default-product.jpg';
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