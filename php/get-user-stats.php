<?php
/*
 * ConsuTrade - Get User Statistics (AJAX)
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$response = ['success' => false];

// Get role from the variables that init.php provides
$role = $current_user['role'] ?? '';
$user_id = $current_user_id ?? 0;

// ========== ADMIN STATS ==========
if ($role === 'admin') {
    // Total users
    $users_sql = "SELECT COUNT(*) as total FROM users";
    $users_result = $conn->query($users_sql);
    $total_users = (int)$users_result->fetch_assoc()['total'];
    
    // Total products (not deleted)
    $products_sql = "SELECT COUNT(*) as total FROM products WHERE status != 'deleted'";
    $products_result = $conn->query($products_sql);
    $total_products = (int)$products_result->fetch_assoc()['total'];
    
    // Pending orders
    $orders_sql = "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'";
    $orders_result = $conn->query($orders_sql);
    $pending_orders = (int)$orders_result->fetch_assoc()['total'];
    
    // Total earnings from completed orders
    $earnings_sql = "SELECT SUM(total_price) as total FROM orders WHERE status = 'completed'";
    $earnings_result = $conn->query($earnings_sql);
    $earnings_row = $earnings_result->fetch_assoc();
    $total_earnings = (float)($earnings_row['total'] ?? 0);
    
    $response = [
        'success' => true,
        'total_users' => $total_users,
        'total_products' => $total_products,
        'pending_orders' => $pending_orders,
        'total_earnings' => $total_earnings
    ];
    
// ========== SELLER STATS ==========
} elseif ($role === 'seller') {
    // Total products
    $product_sql = "SELECT COUNT(*) as total FROM products WHERE seller_id = ? AND status != 'deleted'";
    $product_stmt = $conn->prepare($product_sql);
    $product_stmt->bind_param('i', $user_id);
    $product_stmt->execute();
    $total_products = (int)$product_stmt->get_result()->fetch_assoc()['total'];
    $product_stmt->close();
    
    // Pending orders
    $orders_sql = "SELECT COUNT(*) as total FROM orders WHERE seller_id = ? AND status = 'pending'";
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param('i', $user_id);
    $orders_stmt->execute();
    $pending_orders = (int)$orders_stmt->get_result()->fetch_assoc()['total'];
    $orders_stmt->close();
    
    // Total earnings
    $earnings_sql = "SELECT SUM(total_price) as total FROM orders WHERE seller_id = ? AND status = 'completed'";
    $earnings_stmt = $conn->prepare($earnings_sql);
    $earnings_stmt->bind_param('i', $user_id);
    $earnings_stmt->execute();
    $earnings_row = $earnings_stmt->get_result()->fetch_assoc();
    $total_earnings = (float)($earnings_row['total'] ?? 0);
    $earnings_stmt->close();
    
    $response = [
        'success' => true,
        'total_products' => $total_products,
        'pending_orders' => $pending_orders,
        'total_earnings' => $total_earnings
    ];
    
// ========== BUYER STATS ==========
} elseif ($role === 'buyer') {
    $orders_sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
                    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                    SUM(CASE WHEN status IN ('completed', 'processing', 'shipped') THEN total_price ELSE 0 END) as total_spent
                  FROM orders 
                  WHERE buyer_id = ?";
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param('i', $user_id);
    $orders_stmt->execute();
    $orders_data = $orders_stmt->get_result()->fetch_assoc();
    $orders_stmt->close();
    
    $review_sql = "SELECT COUNT(*) as count FROM reviews WHERE buyer_id = ?";
    $review_stmt = $conn->prepare($review_sql);
    $review_stmt->bind_param('i', $user_id);
    $review_stmt->execute();
    $reviews_written = (int)$review_stmt->get_result()->fetch_assoc()['count'];
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
    
} else {
    $response['message'] = 'Not authenticated - Role: ' . $role;
}

echo json_encode($response);
?>