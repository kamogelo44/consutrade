<?php
/*
 * ConsuTrade - Clear Cart API
 * Author: Kamogelo Phale
 * 
 * Wipes the whole cart after someone checks out
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

$user_id = $_SESSION['user_id'];

// Clear cart from database
$sql = "DELETE FROM cart WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);

if ($stmt->execute()) {
    // Also clear session cart count
    $_SESSION['cart_count'] = 0;
    $response['success'] = true;
    $response['message'] = 'Cart cleared successfully';
} else {
    $response['message'] = 'Failed to clear cart';
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>