<?php
/*
 * ConsuTrade - Get User Statistics (AJAX)
 * Author: Kamogelo Phale
 * 
 * Uses UserService for all statistics calculations.
 */

require_once dirname(__DIR__, 3) . '/init.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// ============================================
// ADMIN STATS
// ============================================
if ($auth->isAdmin() && !isset($_GET['seller_id']) && !isset($_GET['user_id'])) {
    $stats = $userService->getAdminStats();

    $response = ['success' => true] + $stats;
    echo json_encode($response);
    exit;
}

// ============================================
// SELLER OR BUYER STATS
// ============================================
$sellerId = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$targetId = $sellerId > 0 ? $sellerId : $userId;

if ($targetId <= 0) {
    $response['message'] = 'Invalid user ID';
    echo json_encode($response);
    exit;
}

$targetUser = $userRepo->findById($targetId);

if (!$targetUser) {
    $response['message'] = 'User not found';
    echo json_encode($response);
    exit;
}

// ============================================
// SELLER STATS
// ============================================
if ($targetUser instanceof Seller) {
    $stats = $userService->getSellerStats($targetId);

    $response = [
        'success' => true,
        'total_products' => $stats['total_products'],
        'total_revenue' => $stats['total_revenue'],
        'total_orders' => $stats['total_orders'],
        'pending_orders' => $stats['pending_orders'],
        'completed_orders' => $stats['completed_orders'],
        'avg_rating' => $stats['avg_rating'],
        'is_verified' => $targetUser->isVerified(),
        'member_since' => date('F Y', strtotime($targetUser->getCreatedAt())),
        'has_verification_document' => $targetUser->getVerification() !== null
    ];
    echo json_encode($response);
    exit;
}

// ============================================
// BUYER STATS
// ============================================
if ($targetUser instanceof Buyer) {
    $isAuthenticated = ($isLoggedIn && $currentUser instanceof Buyer && $currentUser->getUserId() === $targetId);

    if (!$isAuthenticated) {
        $response['message'] = 'Unauthorized to view buyer stats';
        echo json_encode($response);
        exit;
    }

    $stats = $userService->getBuyerStats($targetId);

    $response = [
        'success' => true,
        'total_orders' => $stats['total_orders'],
        'total_spent' => $stats['total_spent'],
        'member_since' => date('F Y', strtotime($targetUser->getCreatedAt())),
        'is_active' => $targetUser->getStatus() === 'active',
        'reviews_written' => $stats['reviews_written']
    ];
    echo json_encode($response);
    exit;
}

// ============================================
// FALLBACK
// ============================================
$response['message'] = 'User stats not available for this account type';
echo json_encode($response);
