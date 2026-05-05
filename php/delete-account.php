<?php
/*
 * ConsuTrade - Delete Account
 * Author: Kamogelo Phale
 * 
 * Permanently deletes user account and all associated data
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in using centralized auth
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

// Verify password
$sql = "SELECT password FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    $response['message'] = 'Invalid password';
    echo json_encode($response);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete user's cart items
    $cart_sql = "DELETE FROM cart WHERE user_id = ?";
    $cart_stmt = $conn->prepare($cart_sql);
    $cart_stmt->bind_param('i', $user_id);
    $cart_stmt->execute();
    $cart_stmt->close();
    
    // Delete user's orders (as buyer)
    $buyer_orders_sql = "DELETE FROM orders WHERE buyer_id = ?";
    $buyer_orders_stmt = $conn->prepare($buyer_orders_sql);
    $buyer_orders_stmt->bind_param('i', $user_id);
    $buyer_orders_stmt->execute();
    $buyer_orders_stmt->close();
    
    // Delete user's orders (as seller) - but first check if they have completed orders
    if ($role === 'seller') {
        // Check if seller has any completed orders
        $check_sql = "SELECT COUNT(*) as count FROM orders WHERE seller_id = ? AND status IN ('completed', 'shipped', 'processing')";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('i', $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        $check_stmt->close();
        
        if ($check_row['count'] > 0) {
            // Seller has completed orders - cannot delete account
            throw new Exception('Cannot delete account with completed orders. Please contact support.');
        }
        
        // Delete seller's orders
        $seller_orders_sql = "DELETE FROM orders WHERE seller_id = ?";
        $seller_orders_stmt = $conn->prepare($seller_orders_sql);
        $seller_orders_stmt->bind_param('i', $user_id);
        $seller_orders_stmt->execute();
        $seller_orders_stmt->close();
        
        // Delete seller's products
        $products_sql = "DELETE FROM products WHERE seller_id = ?";
        $products_stmt = $conn->prepare($products_sql);
        $products_stmt->bind_param('i', $user_id);
        $products_stmt->execute();
        $products_stmt->close();
    }
    
    // Delete user's reviews (as buyer)
    $reviews_buyer_sql = "DELETE FROM reviews WHERE buyer_id = ?";
    $reviews_buyer_stmt = $conn->prepare($reviews_buyer_sql);
    $reviews_buyer_stmt->bind_param('i', $user_id);
    $reviews_buyer_stmt->execute();
    $reviews_buyer_stmt->close();
    
    // Delete user's reviews (as seller) - these will be handled by ON DELETE CASCADE or manually
    $reviews_seller_sql = "DELETE FROM reviews WHERE seller_id = ?";
    $reviews_seller_stmt = $conn->prepare($reviews_seller_sql);
    $reviews_seller_stmt->bind_param('i', $user_id);
    $reviews_seller_stmt->execute();
    $reviews_seller_stmt->close();
    
    // Delete user's profile image if exists
    $profile_image_sql = "SELECT profile_image FROM users WHERE user_id = ?";
    $profile_stmt = $conn->prepare($profile_image_sql);
    $profile_stmt->bind_param('i', $user_id);
    $profile_stmt->execute();
    $profile_result = $profile_stmt->get_result();
    $profile_row = $profile_result->fetch_assoc();
    $profile_stmt->close();
    
    if (!empty($profile_row['profile_image'])) {
        $image_path = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $profile_row['profile_image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Finally, delete the user
    $delete_sql = "DELETE FROM users WHERE user_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param('i', $user_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    $conn->commit();
    
    // Clear session using auth helper
    logoutUser();
    
    $response['success'] = true;
    $response['message'] = 'Account deleted successfully';
    
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>