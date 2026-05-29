<?php
/*
 * ConsuTrade - Get Recent Users (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns recent users for admin dashboard
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'users' => [], 'message' => ''];

// Check if admin is logged in
if (!$auth->isAdminLoggedIn()) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

// Get recent users
$sql = "SELECT user_id, full_name, email, role, phone, id_verified,
        DATE_FORMAT(created_at, '%d %b %Y') as created_at
        FROM users 
        ORDER BY created_at DESC 
        LIMIT ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $limit);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        'user_id' => (int)$row['user_id'],
        'full_name' => $row['full_name'],
        'email' => $row['email'],
        'role' => $row['role'],
        'phone' => $row['phone'] ?? '-',
        'is_verified' => (bool)($row['id_verified'] ?? false),
        'created_at' => $row['created_at']
    ];
}
$stmt->close();

$response['success'] = true;
$response['users'] = $users;

echo json_encode($response);
?>