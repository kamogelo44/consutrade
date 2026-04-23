<?php
/*
 * ConsuTrade - Get User Statistics
 * Author: Kamogelo Phale
 */

session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// ========== ADD THIS PUBLIC VIEW LOGIC FIRST ==========
// Check if we're viewing a specific seller profile (public view)
$view_seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;

if ($view_seller_id > 0) {
    // Get stats for the viewed seller (no login required)
    $user_id = $view_seller_id;
    
    // Get total products
    $product_sql = "SELECT COUNT(*) as count FROM products WHERE seller_id = ? AND status != 'deleted'";
    $product_stmt = $conn->prepare($product_sql);
    $product_stmt->bind_param('i', $user_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    $total_products = (int)$product_result->fetch_assoc()['count'];
    $product_stmt->close();
    
    // Get completed orders count for seller
    $sales_sql = "SELECT COUNT(*) as count FROM orders WHERE seller_id = ? AND status = 'completed'";
    $sales_stmt = $conn->prepare($sales_sql);
    $sales_stmt->bind_param('i', $user_id);
    $sales_stmt->execute();
    $sales_result = $sales_stmt->get_result();
    $total_sales = (int)$sales_result->fetch_assoc()['count'];
    $sales_stmt->close();
    
    // Get reviews
    $review_sql = "SELECT COUNT(*) as count, AVG(rating) as avg FROM reviews WHERE seller_id = ?";
    $review_stmt = $conn->prepare($review_sql);
    $review_stmt->bind_param('i', $user_id);
    $review_stmt->execute();
    $review_result = $review_stmt->get_result();
    $review_data = $review_result->fetch_assoc();
    $total_reviews = (int)($review_data['count'] ?? 0);
    $avg_rating = round($review_data['avg'] ?? 0, 1);
    $review_stmt->close();
    
    echo json_encode([
        'success' => true,
        'total_products' => $total_products,
        'total_sales' => $total_sales,
        'total_revenue' => 0,
        'total_reviews' => $total_reviews,
        'avg_rating' => $avg_rating,
        'pending_orders' => 0,
        'total_orders' => 0,
        'total_earnings' => 0
    ]);
    exit;
}
// ========== END PUBLIC VIEW LOGIC ==========

// Initialize response for logged-in users
$response = [
    'success' => false,
    'total_products' => 0,
    'total_sales' => 0,
    'total_revenue' => 0,
    'total_reviews' => 0,
    'avg_rating' => 0,
    'pending_orders' => 0,
    'total_orders' => 0,
    'total_earnings' => 0
];

// Check authentication for logged-in users
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode($response);
    exit;
}

// Rest of your existing code for logged-in users...
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role === 'seller') {
    // Total products
    $product_sql = "SELECT COUNT(*) as count FROM products WHERE seller_id = ? AND status != 'deleted'";
    $product_stmt = $conn->prepare($product_sql);
    $product_stmt->bind_param('i', $user_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    $total_products = $product_result->fetch_assoc()['count'];
    $product_stmt->close();
    
    // Sales statistics
    $sales_sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'completed' THEN total_price ELSE 0 END) as total_revenue
                  FROM orders 
                  WHERE seller_id = ?";
    $sales_stmt = $conn->prepare($sales_sql);
    $sales_stmt->bind_param('i', $user_id);
    $sales_stmt->execute();
    $sales_result = $sales_stmt->get_result();
    $sales_data = $sales_result->fetch_assoc();
    $total_sales = (int)($sales_data['completed_orders'] ?? 0);
    $total_revenue = (float)($sales_data['total_revenue'] ?? 0);
    $pending_orders = (int)($sales_data['pending_orders'] ?? 0);
    $total_orders = (int)($sales_data['total_orders'] ?? 0);
    $sales_stmt->close();
    
    // Reviews - FIXED: Use correct column names for your table
    // Your table has: review_id, buyer_id, seller_id, order_id, rating, comment, created_at
    $review_sql = "SELECT COUNT(*) as count, AVG(rating) as avg FROM reviews WHERE seller_id = ?";
    $review_stmt = $conn->prepare($review_sql);
    if ($review_stmt) {
        $review_stmt->bind_param('i', $user_id);
        $review_stmt->execute();
        $review_result = $review_stmt->get_result();
        $review_data = $review_result->fetch_assoc();
        $total_reviews = (int)($review_data['count'] ?? 0);
        $avg_rating = round($review_data['avg'] ?? 0, 1);
        $review_stmt->close();
    } else {
        $total_reviews = 0;
        $avg_rating = 0;
    }
    
    $response = [
        'success' => true,
        'total_products' => (int)$total_products,
        'total_sales' => $total_sales,
        'total_revenue' => $total_revenue,
        'total_reviews' => $total_reviews,
        'avg_rating' => $avg_rating,
        'pending_orders' => $pending_orders,
        'total_orders' => $total_orders,
        'total_earnings' => $total_revenue
    ];
    
} elseif ($role === 'buyer') {
    // Buyer stats
    $orders_sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status IN ('completed', 'paid') THEN total_price ELSE 0 END) as total_spent,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders
                  FROM orders 
                  WHERE buyer_id = ?";
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param('i', $user_id);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();
    $orders_data = $orders_result->fetch_assoc();
    $orders_stmt->close();
    
    // Reviews written by buyer - FIXED: Use correct table name
    $review_sql = "SELECT COUNT(*) as count FROM reviews WHERE buyer_id = ?";
    $review_stmt = $conn->prepare($review_sql);
    $review_stmt->bind_param('i', $user_id);
    $review_stmt->execute();
    $review_result = $review_stmt->get_result();
    $reviews_written = (int)$review_result->fetch_assoc()['count'];
    $review_stmt->close();
    
    $response = [
        'success' => true,
        'total_orders' => (int)($orders_data['total_orders'] ?? 0),
        'total_spent' => (float)($orders_data['total_spent'] ?? 0),
        'pending_orders' => (int)($orders_data['pending_orders'] ?? 0),
        'reviews_written' => $reviews_written
    ];
    
} elseif ($role === 'admin') {
    // Admin stats
    $users_sql = "SELECT COUNT(*) as count FROM users";
    $users_result = $conn->query($users_sql);
    $total_users = (int)$users_result->fetch_assoc()['count'];
    
    $products_sql = "SELECT COUNT(*) as count FROM products WHERE status != 'deleted'";
    $products_result = $conn->query($products_sql);
    $total_products = (int)$products_result->fetch_assoc()['count'];
    
    $orders_sql = "SELECT COUNT(*) as count FROM orders";
    $orders_result = $conn->query($orders_sql);
    $total_orders = (int)$orders_result->fetch_assoc()['count'];
    
    $pending_sql = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
    $pending_result = $conn->query($pending_sql);
    $pending_orders = (int)$pending_result->fetch_assoc()['count'];
    
    $earnings_sql = "SELECT SUM(total_price) as total FROM orders WHERE status = 'completed'";
    $earnings_result = $conn->query($earnings_sql);
    $earnings_row = $earnings_result->fetch_assoc();
    $total_earnings = (float)($earnings_row['total'] ?? 0);
    
    $response = [
        'success' => true,
        'total_users' => $total_users,
        'total_products' => $total_products,
        'total_orders' => $total_orders,
        'pending_orders' => $pending_orders,
        'total_earnings' => $total_earnings
    ];
} else {
    $response = [
        'success' => true,
        'total_products' => 0,
        'total_sales' => 0,
        'total_revenue' => 0,
        'total_reviews' => 0,
        'avg_rating' => 0,
        'pending_orders' => 0,
        'total_orders' => 0,
        'total_earnings' => 0
    ];
}

$conn->close();
echo json_encode($response);
?>