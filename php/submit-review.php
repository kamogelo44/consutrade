<?php
/*
 * ConsuTrade - Submit Review
 * Author: Kamogelo Phale
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$seller_id = isset($input['seller_id']) ? (int)$input['seller_id'] : 0;
$rating = isset($input['rating']) ? (int)$input['rating'] : 0;
$comment = isset($input['comment']) ? trim($input['comment']) : '';
$buyer_id = $_SESSION['user_id'];

if ($order_id <= 0 || $seller_id <= 0) {
    $response['message'] = 'Invalid request';
    echo json_encode($response);
    exit;
}

if ($rating < 1 || $rating > 5) {
    $response['message'] = 'Invalid rating';
    echo json_encode($response);
    exit;
}

// Check if order exists and is completed
$check_sql = "SELECT order_id FROM orders WHERE order_id = ? AND buyer_id = ? AND seller_id = ? AND status = 'completed'";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('iii', $order_id, $buyer_id, $seller_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $response['message'] = 'You can only review completed orders';
    echo json_encode($response);
    $check_stmt->close();
    $conn->close();
    exit;
}
$check_stmt->close();

// Check if review already exists
$check_review_sql = "SELECT review_id FROM reviews WHERE order_id = ? AND buyer_id = ?";
$check_review_stmt = $conn->prepare($check_review_sql);
$check_review_stmt->bind_param('ii', $order_id, $buyer_id);
$check_review_stmt->execute();
$check_review_result = $check_review_stmt->get_result();

if ($check_review_result->num_rows > 0) {
    $response['message'] = 'You have already reviewed this order';
    echo json_encode($response);
    $check_review_stmt->close();
    $conn->close();
    exit;
}
$check_review_stmt->close();

// Insert review
$insert_sql = "INSERT INTO reviews (order_id, seller_id, buyer_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param('iiiis', $order_id, $seller_id, $buyer_id, $rating, $comment);

if ($insert_stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Review submitted successfully';
} else {
    $response['message'] = 'Failed to submit review';
}

$insert_stmt->close();
$conn->close();

echo json_encode($response);
?>