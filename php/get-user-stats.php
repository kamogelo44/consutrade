<?php
/*
 * ConsuTrade - Get User Statistics (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns statistics for users based on role:
 * - Public: View seller stats (products, sales, reviews)
 * - Authenticated: Personal stats (dashboard data)
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false];

// ========== PUBLIC VIEW LOGIC (no login required for public seller stats) ==========
$view_seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;

if ($view_seller_id > 0) {
    // Get stats for the viewed seller (public view)
    $user_id = $view_seller_id;
    
    // Total products
    $product_sql = "SELECT COUNT(*) as count FROM products WHERE seller_id = ? AND status = 'active'";
    $product_stmt = $conn->prepare($product_sql);
    $product_stmt->bind_param('i', $user_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    $total_products = (int)$product_result->fetch_assoc()['count'];
    $product_stmt->close();
    
    // Completed sales
    $sales_sql = "SELECT COUNT(*) as count FROM orders WHERE seller_id = ? AND status = 'completed'";
    $sales_stmt = $conn->prepare($sales_sql);
    $sales_stmt->bind_param('i', $user_id);
    $sales_stmt->execute();
    $sales_result = $sales_stmt->get_result();
    $total_sales = (int)$sales_result->fetch_assoc()['count'];
    $sales_stmt->close();
    
    // Reviews
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
        'total_reviews' => $total_reviews,
        'avg_rating' => $avg_rating
    ]);
    exit;
}

// ========== AUTHENTICATED USERS (requires login) ==========
if (!$is_logged_in) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$user_id = $current_user_id;
$role = $current_user['role'];

if ($role === 'seller') {
    // Total products
    $product_sql = "SELECT COUNT(*) as count FROM products WHERE seller_id = ? AND status != 'deleted'";
    $product_stmt = $conn->prepare($product_sql);
    $product_stmt->bind_param('i', $user_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    $total_products = (int)$product_result->fetch_assoc()['count'];
    $product_stmt->close();
    
    // Sales statistics
    $sales_sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
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
    $shipped_orders = (int)($sales_data['shipped_orders'] ?? 0);
    $processing_orders = (int)($sales_data['processing_orders'] ?? 0);
    $total_orders = (int)($sales_data['total_orders'] ?? 0);
    $sales_stmt->close();
    
    // Reviews
    $review_sql = "SELECT COUNT(*) as count, AVG(rating) as avg FROM reviews WHERE seller_id = ?";
    $review_stmt = $conn->prepare($review_sql);
    $review_stmt->bind_param('i', $user_id);
    $review_stmt->execute();
    $review_result = $review_stmt->get_result();
    $review_data = $review_result->fetch_assoc();
    $total_reviews = (int)($review_data['count'] ?? 0);
    $avg_rating = round($review_data['avg'] ?? 0, 1);
    $review_stmt->close();
    
    $response = [
        'success' => true,
        'total_products' => $total_products,
        'total_sales' => $total_sales,
        'total_revenue' => $total_revenue,
        'total_reviews' => $total_reviews,
        'avg_rating' => $avg_rating,
        'pending_orders' => $pending_orders,
        'shipped_orders' => $shipped_orders,
        'processing_orders' => $processing_orders,
        'total_orders' => $total_orders,
        'total_earnings' => $total_revenue
    ];
    
} elseif ($role === 'buyer') {
    // Buyer stats
    $orders_sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status IN ('completed', 'processing', 'shipped') THEN total_price ELSE 0 END) as total_spent,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
                    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
                  FROM orders 
                  WHERE buyer_id = ?";
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param('i', $user_id);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();
    $orders_data = $orders_result->fetch_assoc();
    $orders_stmt->close();
    
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
        'completed_orders' => (int)($orders_data['completed_orders'] ?? 0),
        'processing_orders' => (int)($orders_data['processing_orders'] ?? 0),
        'shipped_orders' => (int)($orders_data['shipped_orders'] ?? 0),
        'cancelled_orders' => (int)($orders_data['cancelled_orders'] ?? 0),
        'reviews_written' => $reviews_written
    ];
    
} elseif ($role === 'admin') {
    // Admin stats
    $users_sql = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN role = 'buyer' THEN 1 ELSE 0 END) as total_buyers,
                    SUM(CASE WHEN role = 'seller' THEN 1 ELSE 0 END) as total_sellers
                  FROM users";
    $users_result = $conn->query($users_sql);
    $users_data = $users_result->fetch_assoc();
    $total_users = (int)$users_data['total_users'];
    $total_buyers = (int)$users_data['total_buyers'];
    $total_sellers = (int)$users_data['total_sellers'];
    
    $products_sql = "SELECT 
                        COUNT(*) as total_products,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_products,
                        SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold_products
                     FROM products";
    $products_result = $conn->query($products_sql);
    $products_data = $products_result->fetch_assoc();
    $total_products = (int)$products_data['total_products'];
    $active_products = (int)$products_data['active_products'];
    $sold_products = (int)$products_data['sold_products'];
    
    $orders_sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
                    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
                  FROM orders";
    $orders_result = $conn->query($orders_sql);
    $orders_data = $orders_result->fetch_assoc();
    $total_orders = (int)($orders_data['total_orders'] ?? 0);
    $pending_orders = (int)($orders_data['pending_orders'] ?? 0);
    $processing_orders = (int)($orders_data['processing_orders'] ?? 0);
    $shipped_orders = (int)($orders_data['shipped_orders'] ?? 0);
    $completed_orders = (int)($orders_data['completed_orders'] ?? 0);
    $cancelled_orders = (int)($orders_data['cancelled_orders'] ?? 0);
    
    $earnings_sql = "SELECT SUM(total_price) as total FROM orders WHERE status = 'completed'";
    $earnings_result = $conn->query($earnings_sql);
    $earnings_row = $earnings_result->fetch_assoc();
    $total_earnings = (float)($earnings_row['total'] ?? 0);
    
    $response = [
        'success' => true,
        'total_users' => $total_users,
        'total_buyers' => $total_buyers,
        'total_sellers' => $total_sellers,
        'total_products' => $total_products,
        'active_products' => $active_products,
        'sold_products' => $sold_products,
        'total_orders' => $total_orders,
        'pending_orders' => $pending_orders,
        'processing_orders' => $processing_orders,
        'shipped_orders' => $shipped_orders,
        'completed_orders' => $completed_orders,
        'cancelled_orders' => $cancelled_orders,
        'total_earnings' => $total_earnings
    ];
}

echo json_encode($response);
?>