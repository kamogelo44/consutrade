<?php
/*
 * ConsuTrade - Get Seller Reviews (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';
header('Content-Type: application/json');

$response = ['success' => false, 'reviews' => [], 'message' => ''];

$seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;

if ($seller_id <= 0) {
    $response['message'] = 'Invalid seller ID';
    echo json_encode($response);
    exit;
}

// ReviewRepository for data retrieval (CRUD - READ)
$reviews = $reviewRepo->findBySeller($seller_id);

// Get seller rating stats
$ratingStats = $reviewRepo->getSellerRating($seller_id);

$response['success'] = true;
$response['reviews'] = $reviews;
$response['avg_rating'] = $ratingStats['avg_rating'] ?? 0;
$response['review_count'] = $ratingStats['review_count'] ?? 0;

echo json_encode($response);
exit;
