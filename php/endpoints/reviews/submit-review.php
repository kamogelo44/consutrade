<?php
/*
 * ConsuTrade - Submit Review (AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows buyers to submit reviews for completed orders
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');

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
    $response['message'] = 'Invalid order or seller.';
    echo json_encode($response);
    exit;
}

if ($rating < 1 || $rating > 5) {
    $response['message'] = 'Please select a rating between 1 and 5.';
    echo json_encode($response);
    exit;
}

$comment = substr($comment, 0, 500);

// ReviewRepository for data operations
$result = $reviewRepo->create($order_id, $seller_id, $currentUser->getUserId(), $rating, $comment);

$response['success'] = $result['success'];
$response['message'] = $result['message'];

echo json_encode($response);
