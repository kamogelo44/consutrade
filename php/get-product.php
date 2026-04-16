<?php
/*
 * ConsuTrade - Get Single Product
 * Author: Kamogelo Phale
 */

// Turn off error output to prevent HTML in JSON
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'product' => null];

if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    //Get product details with gallery images
    $sql = "SELECT p.product_id, p.title as product_name, p.price, p.description, 
            p.image_url, p.gallery_images, p.location, p.condition, p.category_id,
            p.status, p.created_at,
            u.full_name as seller_name, u.email as seller_email, u.user_id as seller_id,
            u.id_verified as is_verified,
            (SELECT AVG(rating) FROM reviews WHERE seller_id = u.user_id) as avg_rating,
            (SELECT COUNT(*) FROM reviews WHERE seller_id = u.user_id) as review_count
            FROM products p 
            JOIN users u ON p.seller_id = u.user_id 
            WHERE p.product_id = ? AND p.status != 'deleted'";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Get category name
            $category_name = 'General';
            $cat_sql = "SELECT name FROM categories WHERE category_id = ?";
            $cat_stmt = $conn->prepare($cat_sql);
            if ($cat_stmt) {
                $cat_stmt->bind_param('i', $row['category_id']);
                $cat_stmt->execute();
                $cat_result = $cat_stmt->get_result();
                if ($cat_row = $cat_result->fetch_assoc()) {
                    $category_name = $cat_row['name'];
                }
                $cat_stmt->close();
            }
            
            $response['product'] = [
                'id' => $row['product_id'],
                'name' => $row['product_name'],
                'price' => (float)$row['price'],
                'description' => $row['description'] ?? '',
                'condition' => $row['condition'] ?? '',
                'location' => $row['location'] ?? '',
                'category_id' => $row['category_id'],
                'category_name' => $category_name,
                'image' => $row['image_url'] ?? 'images/default-product.png',
                'gallery_images' => $row['gallery_images'],
                'seller_name' => $row['seller_name'],
                'seller_id' => $row['seller_id'],
                'seller_email' => $row['seller_email'],
                'is_verified' => $row['is_verified'] == 1,
                'avg_rating' => round($row['avg_rating'] ?? 0, 1),
                'review_count' => (int)($row['review_count'] ?? 0),
                'status' => $row['status'],
                'created_at' => $row['created_at']
            ];
            $response['success'] = true;
        }
        $stmt->close();
    }
}

$conn->close();

// Clean any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

echo json_encode($response);
exit;
?>