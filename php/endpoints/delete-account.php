<?php
/*
 * ConsuTrade - Delete Account
 * Author: Kamogelo Phale
 * 
 * Permanently deletes user account and all associated data
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in
if (!$is_logged_in) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$password = $input['password'] ?? '';
$user_id = $current_user_id;
$role = $current_user['role'];

if (empty($password)) {
    $response['message'] = 'Password is required';
    echo json_encode($response);
    exit;
}

// Verify password using UserRepository
$user_data = $userRepo->getById($user_id);

if (!$user_data || !password_verify($password, $user_data['password'])) {
    $response['message'] = 'Invalid password';
    echo json_encode($response);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete user's cart items
    $cart_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $cart_stmt->bind_param('i', $user_id);
    $cart_stmt->execute();
    $cart_stmt->close();
    
    // Delete user's orders (as buyer)
    $buyer_orders_stmt = $conn->prepare("DELETE FROM orders WHERE buyer_id = ?");
    $buyer_orders_stmt->bind_param('i', $user_id);
    $buyer_orders_stmt->execute();
    $buyer_orders_stmt->close();
    
    // Delete user's orders (as seller) - check for completed orders first
    if ($role === 'seller') {
        // Check if seller has any completed orders
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE seller_id = ? AND status IN ('completed', 'shipped', 'processing')");
        $check_stmt->bind_param('i', $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        $check_stmt->close();
        
        if ($check_row['count'] > 0) {
            throw new Exception('Cannot delete account with completed orders. Please contact support.');
        }
        
        // Delete seller's orders
        $seller_orders_stmt = $conn->prepare("DELETE FROM orders WHERE seller_id = ?");
        $seller_orders_stmt->bind_param('i', $user_id);
        $seller_orders_stmt->execute();
        $seller_orders_stmt->close();
        
        // Delete seller's products
        $products_stmt = $conn->prepare("DELETE FROM products WHERE seller_id = ?");
        $products_stmt->bind_param('i', $user_id);
        $products_stmt->execute();
        $products_stmt->close();
    }
    
    // Delete user's reviews (as buyer)
    $reviews_buyer_stmt = $conn->prepare("DELETE FROM reviews WHERE buyer_id = ?");
    $reviews_buyer_stmt->bind_param('i', $user_id);
    $reviews_buyer_stmt->execute();
    $reviews_buyer_stmt->close();
    
    // Delete user's reviews (as seller)
    $reviews_seller_stmt = $conn->prepare("DELETE FROM reviews WHERE seller_id = ?");
    $reviews_seller_stmt->bind_param('i', $user_id);
    $reviews_seller_stmt->execute();
    $reviews_seller_stmt->close();
    
    // Delete profile image if exists
    if (!empty($user_data['profile_image'])) {
        $image_path = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $user_data['profile_image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Finally, delete the user using UserRepository
    $delete_stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $delete_stmt->bind_param('i', $user_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    $conn->commit();
    
    // Logout using Auth class
    $auth->logout();
    
    $response['success'] = true;
    $response['message'] = 'Account deleted successfully';
    
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>