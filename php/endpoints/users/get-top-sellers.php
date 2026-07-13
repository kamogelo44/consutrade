<?php
require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');

if (!isset($userRepo) || !isset($productRepo) || !isset($reviewRepo) || !isset($orderRepo)) {
    echo json_encode(['success' => false, 'error' => 'Repositories not available']);
    exit;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 4;

try {
    // Get sellers
    $sellers = $userRepo->findByRole('seller');

    // Filter ONLY verified sellers
    $verified = array_filter($sellers, function ($seller) {
        return $seller->isVerified() === true;
    });

    // Only use verified sellers
    $sellers = array_slice($verified, 0, $limit);

    $result = [];
    foreach ($sellers as $seller) {
        $userId = $seller->getUserId();

        $result[] = [
            'user_id' => $userId,
            'full_name' => $seller->getFullName(),
            'location' => $seller->getLocation() ?: 'South Africa',
            'id_verified' => $seller->isVerified(),
            'product_count' => $productRepo->countByUser($userId),
            'rating' => $reviewRepo->getSellerRating($userId)['avg_rating'] ?? 0,
            'trades' => $orderRepo->countCompletedBySeller($userId)
        ];
    }

    echo json_encode([
        'success' => true,
        'sellers' => $result
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
