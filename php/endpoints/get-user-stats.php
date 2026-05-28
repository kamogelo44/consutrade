<?php
/*
 * ConsuTrade - Get User Statistics (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns public stats for a seller (no login required)
 * Returns private stats for buyer when authenticated
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

// Get user role using UserRepository
$user_data = $userRepo->getById($target_id);
$user_role = $user_data['role'] ?? '';

// ========== SELLER STATS (Public) ==========
if ($user_role === 'seller') {
    // Total active products using ProductRepository
    $total_products = $productRepo->countUserProducts($target_id);
    
    // Completed orders using OrderRepository
    $total_sales = $orderRepo->countSellerCompletedOrders($target_id);
    
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
        // Get order statistics using OrderRepository
        $order_stats = $orderRepo->getBuyerStats($target_id);
        
        // Get reviews written count using ReviewRepository
        $reviews_count = $reviewRepo->countBuyerReviews($target_id);
        
        $response = [
            'success' => true,
            'total_orders' => $order_stats['total_orders'],
            'total_spent' => $order_stats['total_spent'],
            'pending_orders' => $order_stats['pending_orders'],
            'completed_orders' => $order_stats['completed_orders'],
            'reviews_written' => $reviews_count
        ];
    } else {
        $response['message'] = 'Unauthorized to view buyer stats';
    }
    
} else {
    $response['message'] = 'User not found';
}

echo json_encode($response);
?>