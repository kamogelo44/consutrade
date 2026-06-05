<?php

/**
 * ConsuTrade - Delete Account Endpoint
 * 
 * Handles permanent account deletion requests from users.
 * Validates password, checks for active orders, and removes all user data.
 *
 * @author Kamogelo Phale
 * @version 1.0.0
 * @since 2026
 *
 * References:
 * - PHP Group, 2025. Password Hashing. Available at:
 *   https://www.php.net/manual/en/function.password-verify.php
 * - MySQL, 2025. Transactions. Available at:
 *   https://dev.mysql.com/doc/refman/8.0/en/commit.html
 * - W3Schools, 2025. PHP JSON. Available at:
 *   https://www.w3schools.com/php/php_json.asp
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in
if (!$isLoggedIn) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

// Admin accounts cannot be deleted through this endpoint
if ($currentUser->getRole() === 'admin') {
    $response['message'] = 'Admin accounts cannot be deleted via this endpoint';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$password = $input['password'] ?? '';
$user_id = $currentUser->getUserId();
$role = $currentUser->getRole();

if (empty($password)) {
    $response['message'] = 'Password is required';
    echo json_encode($response);
    exit;
}

// Verify password - using PHP's built-in password_verify function
if (!password_verify($password, $currentUser->getPassword())) {
    $response['message'] = 'Invalid password';
    echo json_encode($response);
    exit;
}

// Start transaction to ensure all or nothing deletion
$conn->begin_transaction();

try {
    // Delete cart items first (foreign key dependency)
    $cart_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $cart_stmt->bind_param('i', $user_id);
    $cart_stmt->execute();
    $cart_stmt->close();

    // Delete orders where user is buyer
    $buyer_orders_stmt = $conn->prepare("DELETE FROM orders WHERE buyer_id = ?");
    $buyer_orders_stmt->bind_param('i', $user_id);
    $buyer_orders_stmt->execute();
    $buyer_orders_stmt->close();

    // For sellers, need to check active orders first
    if ($role === 'seller') {
        // Count any non-cancelled orders
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE seller_id = ? AND status != 'cancelled'");
        $check_stmt->bind_param('i', $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        $check_stmt->close();

        if ($check_row['count'] > 0) {
            throw new Exception('Cannot delete account with active orders. Please cancel all pending orders first.');
        }

        // Delete seller's orders (should only be cancelled ones by now)
        $seller_orders_stmt = $conn->prepare("DELETE FROM orders WHERE seller_id = ?");
        $seller_orders_stmt->bind_param('i', $user_id);
        $seller_orders_stmt->execute();
        $seller_orders_stmt->close();

        // Delete all products from this seller
        $products_stmt = $conn->prepare("DELETE FROM products WHERE seller_id = ?");
        $products_stmt->bind_param('i', $user_id);
        $products_stmt->execute();
        $products_stmt->close();
    }

    // Delete reviews written by this user (as buyer)
    $reviews_buyer_stmt = $conn->prepare("DELETE FROM reviews WHERE buyer_id = ?");
    $reviews_buyer_stmt->bind_param('i', $user_id);
    $reviews_buyer_stmt->execute();
    $reviews_buyer_stmt->close();

    // Delete reviews received by this user (as seller)
    $reviews_seller_stmt = $conn->prepare("DELETE FROM reviews WHERE seller_id = ?");
    $reviews_seller_stmt->bind_param('i', $user_id);
    $reviews_seller_stmt->execute();
    $reviews_seller_stmt->close();

    // Delete profile image file from server
    $profileImage = $currentUser->getProfileImage();
    if (!empty($profileImage)) {
        // Try multiple possible base paths (different server configurations)
        $basePaths = [
            $_SERVER['DOCUMENT_ROOT'] . '/',
            $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/',
            dirname(__DIR__, 2) . '/',
        ];
        foreach ($basePaths as $basePath) {
            $fullPath = rtrim($basePath, '/') . '/' . ltrim($profileImage, '/');
            if (file_exists($fullPath)) {
                unlink($fullPath);  // Delete the file
                break;
            }
        }
    }

    // Delete seller verification records if user is a seller
    if ($role === 'seller') {
        $verif_stmt = $conn->prepare("DELETE FROM seller_verification WHERE seller_id = ?");
        $verif_stmt->bind_param('i', $user_id);
        $verif_stmt->execute();
        $verif_stmt->close();
    }

    // Finally, delete the user record itself
    $delete_stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $delete_stmt->bind_param('i', $user_id);
    $delete_stmt->execute();
    $delete_stmt->close();

    // Commit all changes to database
    $conn->commit();

    // Destroy session and log user out
    $auth->logout();

    $response['success'] = true;
    $response['message'] = 'Account deleted successfully';
} catch (Exception $e) {
    // Something went wrong - rollback all changes
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
