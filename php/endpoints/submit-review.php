<?php
/*
 * ConsuTrade - Submit Review (AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows buyers to submit reviews for completed orders
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in using centralized auth
if (!$is_logged_in) {
    $response['message'] = 'Please login to submit a review';
    echo json_encode($response);
    exit;
}

// Only buyers can submit reviews
if ($current_user['role'] !== 'buyer') {
    $response['message'] = 'Only buyers can submit reviews';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$seller_id = isset($input['seller_id']) ? (int)$input['seller_id'] : 0;
$rating = isset($input['rating']) ? (int)$input['rating'] : 0;
$comment = isset($input['comment']) ? trim($input['comment']) : '';
$buyer_id = $current_user_id;

// Validate input
if ($order_id <= 0 || $seller_id <= 0) {
    $response['message'] = 'Invalid request: Missing order or seller information';
    echo json_encode($response);
    exit;
}

if ($rating < 1 || $rating > 5) {
    $response['message'] = 'Invalid rating. Please select a rating between 1 and 5 stars.';
    echo json_encode($response);
    exit;
}

// Sanitize comment (limit length, remove excessive whitespace)
$comment = substr($comment, 0, 500); // Max 500 characters
$comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');

// Check if order exists, belongs to this buyer, matches seller, and is completed
$check_sql = "SELECT order_id, status FROM orders 
              WHERE order_id = ? AND buyer_id = ? AND seller_id = ? AND status = 'completed'";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('iii', $order_id, $buyer_id, $seller_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $response['message'] = 'You can only review completed orders that belong to you.';
    echo json_encode($response);
    $check_stmt->close();
    exit;
}
$check_stmt->close();

// Check if review already exists for this order
$check_review_sql = "SELECT review_id FROM reviews WHERE order_id = ? AND buyer_id = ?";
$check_review_stmt = $conn->prepare($check_review_sql);
$check_review_stmt->bind_param('ii', $order_id, $buyer_id);
$check_review_stmt->execute();
$check_review_result = $check_review_stmt->get_result();

if ($check_review_result->num_rows > 0) {
    $response['message'] = 'You have already submitted a review for this order.';
    echo json_encode($response);
    $check_review_stmt->close();
    exit;
}
$check_review_stmt->close();

// Insert review
$insert_sql = "INSERT INTO reviews (order_id, seller_id, buyer_id, rating, comment, created_at) 
               VALUES (?, ?, ?, ?, ?, NOW())";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param('iiiis', $order_id, $seller_id, $buyer_id, $rating, $comment);

if ($insert_stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Thank you for your review!';
} else {
    $response['message'] = 'Failed to submit review. Please try again.';
}

$insert_stmt->close();
echo json_encode($response);
?>