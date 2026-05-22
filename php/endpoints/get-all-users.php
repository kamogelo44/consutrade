<?php
/*
 * ConsuTrade - Get All Users (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns all users for admin management with pagination and filters
 */

require_once dirname(__DIR__) . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'users' => [], 'total_pages' => 1, 'current_page' => 1];

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    echo json_encode($response);
    exit;
}

// Get parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$role_filter = isset($_GET['role']) ? $_GET['role'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$sql = "SELECT user_id, full_name, email, phone, role, id_verified as is_verified, 
        DATE_FORMAT(created_at, '%d %b %Y') as created_at
        FROM users WHERE 1=1";

$params = [];
$types = "";

if ($role_filter !== 'all') {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if (!empty($search_term)) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

// Get total count
$count_sql = str_replace("SELECT user_id, full_name, email, phone, role, id_verified, DATE_FORMAT(created_at, '%d %b %Y') as created_at", 
                         "SELECT COUNT(*) as total", $sql);
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'] ?? 0;
$count_stmt->close();

$total_pages = ceil($total_rows / $limit);

// Add pagination
$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        'user_id' => (int)$row['user_id'],
        'full_name' => $row['full_name'],
        'email' => $row['email'],
        'phone' => $row['phone'] ?? '-',
        'role' => $row['role'],
        'is_verified' => (bool)$row['is_verified'],
        'created_at' => $row['created_at']
    ];
}
$stmt->close();

$response['success'] = true;
$response['users'] = $users;
$response['total_pages'] = $total_pages;
$response['current_page'] = $page;

echo json_encode($response);
?>