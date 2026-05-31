<?php
/*
 * ConsuTrade - Get Seller Reviews (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';
header('Content-Type: application/json');

$response = ['success' => false, 'reviews' => [], 'message' => ''];
$seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;

if ($seller_id <= 0) {
    $response['message'] = 'Invalid seller ID';
    echo json_encode($response);
    exit;
}

$reviews = $reviewRepo->getSellerReviews($seller_id);
$response['success'] = true;
$response['reviews'] = $reviews;
echo json_encode($response);
exit;
