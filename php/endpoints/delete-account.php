<?php

/**
 * ConsuTrade - Delete Account Endpoint
 * 
 * Handles permanent account deletion requests from users.
 * Validates password, checks for active orders, and removes all user data.
 *
 * @author Kamogelo Phale
 * @version 1.0.0
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

// Verify password
if (!password_verify($password, $currentUser->getPassword())) {
    $response['message'] = 'Invalid password';
    echo json_encode($response);
    exit;
}

// Start transaction to ensure all or nothing deletion
$conn->begin_transaction();

try {
    // Delete cart items
    $cartRepo->deleteAllByUser($user_id);

    // Delete orders where user is buyer
    $orderRepo->deleteByBuyer($user_id);

    // For sellers, need to check active orders first
    if ($role === 'seller') {
        // Count any non-cancelled orders
        $activeOrders = $orderRepo->countActiveBySeller($user_id);

        if ($activeOrders > 0) {
            throw new Exception('Cannot delete account with active orders. Please cancel all pending orders first.');
        }

        // Delete seller's orders (should only be cancelled ones by now)
        $orderRepo->deleteBySeller($user_id);

        // Delete all products from this seller
        $productRepo->deleteBySeller($user_id);
    }

    // Delete reviews written by this user (as buyer)
    $reviewRepo->deleteByBuyer($user_id);

    // Delete reviews received by this user (as seller)
    $reviewRepo->deleteBySeller($user_id);

    // Delete profile image file from server
    $profileImage = $currentUser->getProfileImage();
    if (!empty($profileImage)) {
        $basePaths = [
            $_SERVER['DOCUMENT_ROOT'] . '/',
            $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/',
            dirname(__DIR__, 2) . '/',
        ];
        foreach ($basePaths as $basePath) {
            $fullPath = rtrim($basePath, '/') . '/' . ltrim($profileImage, '/');
            if (file_exists($fullPath)) {
                unlink($fullPath);
                break;
            }
        }
    }

    // Delete seller verification records if user is a seller
    if ($role === 'seller') {
        $userRepo->deleteVerification($user_id);
    }

    // Finally, delete the user record itself
    $userRepo->delete($user_id);

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
