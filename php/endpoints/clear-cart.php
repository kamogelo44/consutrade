<?php
/*
 * ConsuTrade - Clear Cart API
 * Author: Kamogelo Phale
 * 
 * Wipes the whole cart after someone checks out
 */

require_once __DIR__ . '/../init.php';
startSession('user');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isUserLoggedIn()) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];

// Clear cart from database using direct query (simple enough, no helper needed)
$stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
$stmt->bind_param('i', $user_id);

if ($stmt->execute()) {
    $_SESSION['cart_count'] = 0;
    $response['success'] = true;
    $response['message'] = 'Cart cleared successfully';
} else {
    $response['message'] = 'Failed to clear cart';
}

$stmt->close();
echo json_encode($response);
?>