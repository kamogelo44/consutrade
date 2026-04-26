<?php
require_once dirname(__DIR__) . '/../php/config.php';
require_once dirname(__DIR__) . '/../php/helpers.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

startSession('admin');

$response = ['success' => true, 'users' => []];

$sql = "SELECT user_id, full_name, email, role, 
        DATE_FORMAT(created_at, '%d %b %Y') as created_at
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 5";

$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $response['users'][] = $row;
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
?>