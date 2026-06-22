<?php
/*
 * ConsuTrade - Get User Statistics (AJAX)
 * Author: Kamogelo Phale
 * 
 * This endpoint acts as a controller - it receives requests,
 * delegates to repositories, and returns JSON responses.
 */

require_once dirname(__DIR__, 2) . '/init.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// ============================================
// ADMIN STATS
// ============================================
if ($auth->isAdmin() && !isset($_GET['seller_id']) && !isset($_GET['user_id'])) {
    $userStats = [
        'total_users' => $userRepo->getTotalUsers(),
        'total_buyers' => $userRepo->countByRole('buyer'),
        'total_sellers' => $userRepo->countByRole('seller'),
        'pending_verifications' => $userRepo->getPendingVerificationsCount()
    ];

    $orderStats = [
        'total_orders' => $orderRepo->countAll(),
        'total_revenue' => $orderRepo->getTotalRevenue(),
        'pending_orders' => $orderRepo->countByStatus('pending')
    ];

    $productStats = [
        'total_products' => $productRepo->countAll()
    ];

    $reportStats = [
        'pending_reports' => $reportRepo->getPendingReportsCount()
    ];

    $admin = new Admin([]);
    $dashboardData = $admin->calculateDashboardStats(
        $userStats,
        $orderStats,
        $productStats,
        $reportStats
    );

    $response = ['success' => true] + $dashboardData;
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
    $totalProducts = $productRepo->countUserProducts($targetId);
    $totalOrders = $orderRepo->countSellerOrders($targetId);
    $pendingOrders = $orderRepo->countSellerOrdersByStatus($targetId, 'pending');
    $completedOrders = $orderRepo->getSellerTotalOrders($targetId);
    $totalRevenue = $orderRepo->getSellerTotalRevenue($targetId);
    $ratingData = $reviewRepo->getSellerRating($targetId);

    $stats = $targetUser->calculateStats(
        $totalProducts,
        $totalOrders,
        $totalRevenue,
        $ratingData['avg_rating'] ?? 0
    );

    $response = [
        'success' => true,
        'total_products' => $stats['total_products'],
        'total_revenue' => $stats['total_revenue'],
        'total_orders' => $stats['total_orders'],
        'pending_orders' => $pendingOrders,
        'avg_rating' => round($stats['avg_rating'], 1),
        'is_verified' => $stats['is_verified'],
        'member_since' => date('F Y', strtotime($stats['member_since'])),
        'has_verification_document' => $stats['has_verification_document']
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

    $orderStats = $orderRepo->getBuyerStats($targetId);
    $reviewsCount = $reviewRepo->countBuyerReviews($targetId);

    $stats = $targetUser->getStats(
        $orderStats['total_orders'] ?? 0,
        $orderStats['total_spent'] ?? 0
    );

    $response = [
        'success' => true,
        'total_orders' => $stats['total_orders'],
        'total_spent' => $stats['total_spent'],
        'member_since' => date('F Y', strtotime($stats['member_since'])),
        'is_active' => $stats['is_active'],
        'reviews_written' => $reviewsCount
    ];
    echo json_encode($response);
    exit;
}

// ============================================
// FALLBACK
// ============================================
$response['message'] = 'User stats not available for this account type';
echo json_encode($response);
