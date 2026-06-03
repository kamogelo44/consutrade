<?php
/*
 * ConsuTrade - Submit Review (AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows buyers to submit reviews for completed orders
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'message' => ''];

if (!$isLoggedIn || !$currentUser instanceof Buyer) {
    $response['message'] = 'Unauthorized. Only buyers can submit reviews.';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$seller_id = isset($input['seller_id']) ? (int)$input['seller_id'] : 0;
$rating = isset($input['rating']) ? (int)$input['rating'] : 0;
$comment = isset($input['comment']) ? trim($input['comment']) : '';

if ($order_id <= 0 || $seller_id <= 0) {
    $response['message'] = 'Invalid request.';
    echo json_encode($response);
    exit;
}

if ($rating < 1 || $rating > 5) {
    $response['message'] = 'Please select a rating between 1 and 5.';
    echo json_encode($response);
    exit;
}

$comment = substr($comment, 0, 500);
$comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');

// reviewRepo is already initialized in init.php
$result = $reviewRepo->submitReview($order_id, $seller_id, $currentUser->getUserId(), $rating, $comment);

$response['success'] = $result['success'];
$response['message'] = $result['message'];

echo json_encode($response);
