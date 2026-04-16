<?php
/*
 * ConsuTrade - Remove from Cart
 * Author: Kamogelo Phale
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Please login to remove items';
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

// Delete item from cart
$sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $user_id, $product_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Item removed from cart';
} else {
    $response['message'] = 'Failed to remove item';
}

$stmt->close();
$conn->close();

echo json_encode($response);
exit;
?>