<?php
/*
 * ConsuTrade - Get Recent Users
 * Author: Kamogelo Phale
 * 
 * Returns JSON data for recent users
 */

session_start();
require_once '../php/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

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