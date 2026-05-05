<?php
/*
 * ConsuTrade - Check Email Exists (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns JSON to check if email is already registered
 * Used by registration form for real-time validation
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

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

$sql = "SELECT role, user_id FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $role = $user['role'];
    
    // Custom message based on role
    switch ($role) {
        case 'buyer':
            $message = 'This email is already registered as a buyer. Please login to your account.';
            break;
        case 'seller':
            $message = 'This email is already registered as a seller. Please login to your seller dashboard.';
            break;
        case 'admin':
            $message = 'This email is registered as an administrator.';
            break;
        default:
            $message = 'This email is already registered. Please login instead.';
            break;
    }
    
    echo json_encode([
        'exists' => true, 
        'role' => $role, 
        'message' => $message,
        'user_id' => $user['user_id']
    ]);
} else {
    echo json_encode([
        'exists' => false, 
        'role' => null, 
        'message' => 'Email available'
    ]);
}

$stmt->close();
?>