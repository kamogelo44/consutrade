<?php
/*
 * ConsuTrade - Get User Statistics (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

$sellerId = isset($_GET['seller_id']) ? (int) $_GET['seller_id'] : 0;
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
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

if ($targetUser instanceof Seller) {
    $totalProducts = $productRepo->countUserProducts($targetId);
    $totalRevenue = $orderRepo->getSellerTotalRevenue($targetId);
    $orderComplete = $orderRepo->getSellerTotalOrders($targetId);

    $response = [
        'success' => true,
        'total_products' => $totalProducts,
        'total_sales' => $totalRevenue,
        'total_orders'  => $orderComplete
    ];
} elseif ($targetUser instanceof Buyer) {
    $isAuthenticated = ($isLoggedIn && $currentUser instanceof Buyer && $currentUser->getUserId() === $targetId);

    if ($isAuthenticated) {
        $orderStats = $orderRepo->getBuyerStats($targetId);
        $reviewsCount = $reviewRepo->countBuyerReviews($targetId);

        $response = [
            'success' => true,
            'total_orders' => $orderStats['total_orders'],
            'total_spent' => $orderStats['total_spent'],
            'pending_orders' => $orderStats['pending_orders'],
            'completed_orders' => $orderStats['completed_orders'],
            'reviews_written' => $reviewsCount
        ];
    } else {
        $response['message'] = 'Unauthorized to view buyer stats';
    }
}

echo json_encode($response);
