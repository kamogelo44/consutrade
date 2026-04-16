<?php
/*
 * ConsuTrade - Update Cart
 * Author: Kamogelo Phale
 * 
 * Updates quantity of a product in cart (disabled - quantity always 1)
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Quantity cannot be changed. Each item can only be added once.'];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Please login to update cart';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$user_id = $_SESSION['user_id'];

if ($product_id <= 0) {
    $response['message'] = 'Invalid product';
    echo json_encode($response);
    exit;
}

// Since quantity is always 1 for unique items, we don't allow updates

echo json_encode($response);
exit;
?>