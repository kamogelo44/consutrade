<?php
/*
 * ConsuTrade - Update Review (AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows buyers to edit their existing reviews
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$isLoggedIn || !$currentUser instanceof Buyer) {
    $response['message'] = 'Unauthorized. Only buyers can update reviews.';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$rating = isset($input['rating']) ? (int)$input['rating'] : 0;
$comment = isset($input['comment']) ? trim($input['comment']) : '';

if ($order_id <= 0) {
    $response['message'] = 'Invalid order ID.';
    echo json_encode($response);
    exit;
}

if ($rating < 1 || $rating > 5) {
    $response['message'] = 'Please select a rating between 1 and 5.';
    echo json_encode($response);
    exit;
}

// Limit comment length
$comment = substr($comment, 0, 500);

// Check if review exists first
$existing = $reviewRepo->getReviewByOrderAndBuyer($order_id, $currentUser->getUserId());

if (!$existing) {
    $response['message'] = 'Review not found.';
    echo json_encode($response);
    exit;
}

$result = $reviewRepo->updateReview($order_id, $currentUser->getUserId(), $rating, $comment);

$response['success'] = $result['success'];
$response['message'] = $result['message'];

echo json_encode($response);
