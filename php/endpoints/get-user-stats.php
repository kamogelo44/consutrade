<?php
/*
 * ConsuTrade - Get User Statistics (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns public stats for a seller (no login required)
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false];

$seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Use seller_id if provided, otherwise use user_id
$target_id = $seller_id > 0 ? $seller_id : $user_id;

if ($target_id <= 0) {
    $response['message'] = 'Invalid user ID';
    echo json_encode($response);
    exit;
}

// Get user role
$role_sql = "SELECT role FROM users WHERE user_id = ?";
$role_stmt = $conn->prepare($role_sql);
$role_stmt->bind_param('i', $target_id);
$role_stmt->execute();
$role_result = $role_stmt->get_result();
$user_role = $role_result->fetch_assoc()['role'] ?? '';
$role_stmt->close();

// ========== SELLER STATS (Public) ==========
if ($user_role === 'seller') {
    // Total active products
    $product_sql = "SELECT COUNT(*) as total FROM products WHERE seller_id = ? AND status = 'active'";
    $product_stmt = $conn->prepare($product_sql);
    $product_stmt->bind_param('i', $target_id);
    $product_stmt->execute();
    $total_products = (int)$product_stmt->get_result()->fetch_assoc()['total'];
    $product_stmt->close();
    
    // Completed orders for this seller's products
    $orders_sql = "SELECT COUNT(DISTINCT o.order_id) as total 
                   FROM orders o
                   JOIN order_items oi ON o.order_id = oi.order_id
                   JOIN products p ON oi.product_id = p.product_id
                   WHERE p.seller_id = ? AND o.status = 'completed'";
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param('i', $target_id);
    $orders_stmt->execute();
    $total_sales = (int)$orders_stmt->get_result()->fetch_assoc()['total'];
    $orders_stmt->close();
    
    $response = [
        'success' => true,
        'total_products' => $total_products,
        'total_sales' => $total_sales
    ];
    
// ========== BUYER STATS (Private - only for logged in user viewing their own profile) ==========
} elseif ($user_role === 'buyer') {
    // Only return data if the viewer is the same buyer (logged in)
    $is_authenticated = isset($current_user_id) && $current_user_id == $target_id;
    
    if ($is_authenticated) {
        $orders_sql = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(CASE WHEN status IN ('completed', 'processing', 'shipped') THEN total_price ELSE 0 END) as total_spent,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders
                      FROM orders 
                      WHERE buyer_id = ?";
        $orders_stmt = $conn->prepare($orders_sql);
        $orders_stmt->bind_param('i', $target_id);
        $orders_stmt->execute();
        $orders_data = $orders_stmt->get_result()->fetch_assoc();
        $orders_stmt->close();
        
        $response = [
            'success' => true,
            'total_orders' => (int)($orders_data['total_orders'] ?? 0),
            'total_spent' => (float)($orders_data['total_spent'] ?? 0),
            'pending_orders' => (int)($orders_data['pending_orders'] ?? 0),
            'completed_orders' => (int)($orders_data['completed_orders'] ?? 0)
        ];
    } else {
        $response['message'] = 'Unauthorized to view buyer stats';
    }
    
} else {
    $response['message'] = 'User not found';
}

echo json_encode($response);
?>