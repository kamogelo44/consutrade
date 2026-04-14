<?php
/*
 * ConsuTrade - Get Single Product
 * Author: Kamogelo Phale
 * 
 * Returns details for a single product
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'product' => null];

if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    
    $sql = "SELECT p.*, u.full_name as seller_name, u.email as seller_email,
            (SELECT COUNT(*) FROM reviews WHERE seller_id = u.user_id AND rating >= 4) as is_verified
            FROM products p 
            JOIN users u ON p.seller_id = u.user_id 
            WHERE p.product_id = ? AND p.status = 'active'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $response['product'] = [
            'id' => $row['product_id'],
            'name' => $row['product_name'],
            'price' => $row['price'],
            'description' => $row['description'],
            'condition' => $row['condition'] ?: 'New',
            'location' => $row['location'] ?: 'South Africa',
            'category' => $row['category'],
            'quantity' => $row['quantity'],
            'image' => $row['image_url'] ?: 'images/default-product.jpg',
            'seller_name' => $row['seller_name'],
            'seller_id' => $row['seller_id'],
            'is_verified' => $row['is_verified'] > 0
        ];
        $response['success'] = true;
    }
    
    $stmt->close();
}

$conn->close();
echo json_encode($response);
?>