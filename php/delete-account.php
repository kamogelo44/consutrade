<?php
/*
 * ConsuTrade - Delete Account
 * Author: Kamogelo Phale
 */

session_start();
require_once 'config.php';
require_once 'helpers.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$password = $input['password'] ?? '';
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if (empty($password)) {
    $response['message'] = 'Password is required';
    echo json_encode($response);
    exit;
}

// Verify password
$sql = "SELECT password FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    $response['message'] = 'Invalid password';
    echo json_encode($response);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete user's cart items
    $cart_sql = "DELETE FROM cart WHERE user_id = ?";
    $cart_stmt = $conn->prepare($cart_sql);
    $cart_stmt->bind_param('i', $user_id);
    $cart_stmt->execute();
    $cart_stmt->close();
    
    // Delete user's reviews
    $reviews_sql = "DELETE FROM reviews WHERE buyer_id = ? OR seller_id = ?";
    $reviews_stmt = $conn->prepare($reviews_sql);
    $reviews_stmt->bind_param('ii', $user_id, $user_id);
    $reviews_stmt->execute();
    $reviews_stmt->close();
    
    // Delete user
    $delete_sql = "DELETE FROM users WHERE user_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param('i', $user_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    $conn->commit();
    
    // Clear session
    session_destroy();
    
    $response['success'] = true;
    $response['message'] = 'Account deleted successfully';
    
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Failed to delete account. Please try again.';
}

$conn->close();
echo json_encode($response);
?>