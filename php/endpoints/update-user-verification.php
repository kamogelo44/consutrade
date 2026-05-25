<?php
/*
 * ConsuTrade - Update User Verification (AJAX)
 * Author: Kamogelo Phale
 * 
 * Toggles user verification status for sellers
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$auth->isAdminLoggedIn()) {
    $response['message'] = 'Unauthorized.';
    echo json_encode($response);
    exit;
}

$data    = json_decode(file_get_contents('php://input'), true);
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
$verify  = isset($data['verify']) ? (bool)$data['verify'] : false;

if (!$user_id) {
    $response['message'] = 'Invalid user.';
    echo json_encode($response);
    exit;
}

// Only sellers can be verified
$check_sql = "SELECT role FROM users WHERE user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('i', $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$user = $check_result->fetch_assoc();
$check_stmt->close();

if ($user['role'] !== 'seller') {
    $response['message'] = 'Only sellers can be verified.';
    echo json_encode($response);
    exit;
}

$new_status = $verify ? 1 : 0;
$sql  = "UPDATE users SET id_verified = ? WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $new_status, $user_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = $verify ? 'Seller verified.' : 'Verification removed.';
} else {
    $response['message'] = 'Could not update verification.';
}
$stmt->close();

echo json_encode($response);