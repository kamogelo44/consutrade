<?php
/*
 * ConsuTrade - Check Email Exists
 * Author: Kamogelo Phale
 * 
 * Returns JSON to check if email is already registered
 * Used by registration form for real-time validation
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json');

$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if (empty($email)) {
    echo json_encode(['exists' => false, 'role' => null, 'message' => '']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['exists' => false, 'role' => null, 'message' => 'Invalid email format']);
    exit;
}

$sql = "SELECT role FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $role = $user['role'];
    
    if ($role === 'buyer') {
        $message = 'This email is already registered as a buyer. Please login to your account.';
    } elseif ($role === 'seller') {
        $message = 'This email is already registered as a seller. Please login to your seller dashboard.';
    } else {
        $message = 'This email is already registered. Please login instead.';
    }
    
    echo json_encode(['exists' => true, 'role' => $role, 'message' => $message]);
} else {
    echo json_encode(['exists' => false, 'role' => null, 'message' => '']);
}

$stmt->close();
$conn->close();
?>