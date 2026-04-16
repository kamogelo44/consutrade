<?php
/*
 * ConsuTrade - Get Single Product
 * Author: Kamogelo Phale
 */

error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Database connection
$host = 'localhost';
$db_name = 'consutrade';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $db_name);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$conn->set_charset('utf8mb4');

$response = ['success' => false, 'product' => null];

if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    
    if ($product_id > 0) {
        $sql = "SELECT p.product_id, p.title, p.price, p.description, 
                p.image_url, p.gallery_images, p.location, p.condition, p.category_id,
                u.full_name as seller_name, u.user_id as seller_id,
                u.id_verified as is_verified
                FROM products p 
                LEFT JOIN users u ON p.seller_id = u.user_id 
                WHERE p.product_id = ?";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param('i', $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                
                // Get category name using 'category_name' column
                $category_name = 'General';
                if ($row['category_id'] > 0) {
                    $cat_sql = "SELECT category_name FROM categories WHERE category_id = ?";
                    $cat_stmt = $conn->prepare($cat_sql);
                    if ($cat_stmt) {
                        $cat_stmt->bind_param('i', $row['category_id']);
                        $cat_stmt->execute();
                        $cat_result = $cat_stmt->get_result();
                        if ($cat_result && $cat_result->num_rows > 0) {
                            $cat_row = $cat_result->fetch_assoc();
                            $category_name = $cat_row['category_name'];
                        }
                        $cat_stmt->close();
                    }
                }
                
                // Get seller rating
                $avg_rating = 0;
                $review_count = 0;
                $rating_sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE seller_id = ?";
                $rating_stmt = $conn->prepare($rating_sql);
                if ($rating_stmt) {
                    $rating_stmt->bind_param('i', $row['seller_id']);
                    $rating_stmt->execute();
                    $rating_result = $rating_stmt->get_result();
                    if ($rating_row = $rating_result->fetch_assoc()) {
                        $avg_rating = round($rating_row['avg_rating'] ?? 0, 1);
                        $review_count = (int)($rating_row['review_count'] ?? 0);
                    }
                    $rating_stmt->close();
                }
                
                $response['product'] = [
                    'id' => $row['product_id'],
                    'name' => $row['title'],
                    'price' => (float)$row['price'],
                    'description' => $row['description'] ?? '',
                    'condition' => $row['condition'] ?? '',
                    'location' => $row['location'] ?? '',
                    'category_id' => $row['category_id'],
                    'category_name' => $category_name,
                    'image' => $row['image_url'] ?? 'images/default-product.png',
                    'gallery_images' => $row['gallery_images'],
                    'seller_name' => $row['seller_name'] ?? 'Unknown Seller',
                    'seller_id' => $row['seller_id'],
                    'is_verified' => $row['is_verified'] == 1,
                    'avg_rating' => $avg_rating,
                    'review_count' => $review_count
                ];
                $response['success'] = true;
            } else {
                $response['error'] = 'Product not found';
            }
            $stmt->close();
        } else {
            $response['error'] = 'Database prepare error';
        }
    } else {
        $response['error'] = 'Invalid product ID';
    }
} else {
    $response['error'] = 'No product ID provided';
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>