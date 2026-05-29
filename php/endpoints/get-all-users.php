<?php
/*
 * ConsuTrade - Get All Users (Admin AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns all users for admin management with pagination and filters using UserRepository
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'users' => [], 'total_pages' => 1, 'current_page' => 1];

if (!$auth->isAdminLoggedIn()) {
    echo json_encode($response);
    exit;
}

$page        = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$role_filter = $_GET['role'] ?? 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit       = 10;
$offset      = ($page - 1) * $limit;

// Use UserRepository to get users
if ($role_filter !== 'all') {
    $users = $userRepo->getUserByRole($role_filter);
} else {
    $users = $userRepo->getAll();
}

// Apply search filter
if (!empty($search_term)) {
    $search_lower = strtolower($search_term);
    $users = array_filter($users, function($user) use ($search_lower) {
        return strpos(strtolower($user['full_name']), $search_lower) !== false ||
               strpos(strtolower($user['email']), $search_lower) !== false;
    });
}

$total_rows = count($users);
$total_pages = ceil($total_rows / $limit);

// Paginate
$paginated_users = array_slice($users, $offset, $limit);

$formatted_users = [];
foreach ($paginated_users as $user) {
    $formatted_users[] = [
        'user_id'     => (int) $user['user_id'],
        'full_name'   => $user['full_name'],
        'email'       => $user['email'],
        'phone'       => $user['phone'] ?? '-',
        'role'        => $user['role'],
        'is_verified' => (bool) ($user['id_verified'] ?? false),
        'created_at'  => date('d M Y', strtotime($user['created_at']))
    ];
}

$response['success']      = true;
$response['users']        = $formatted_users;
$response['total_pages']  = $total_pages;
$response['current_page'] = $page;

echo json_encode($response);
?>