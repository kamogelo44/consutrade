<?php
/*
 * ConsuTrade - Get Users (AJAX)
 * Author: Kamogelo Phale
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
$recent_only = isset($_GET['recent']) && $_GET['recent'] === 'true';
$limit       = $recent_only ? 5 : (isset($_GET['limit']) ? (int)$_GET['limit'] : 10);
$offset      = ($page - 1) * $limit;

// ===== RECENT USERS (Dashboard) =====
if ($recent_only) {
    $users = $userRepo->getRecentUsers($limit);

    $response['success'] = true;
    $response['users'] = $users;
    echo json_encode($response);
    exit;
}

// ===== PENDING VERIFICATIONS =====
if ($role_filter === 'pending') {
    $total_rows = $userRepo->getPendingVerificationsCount();
    $total_pages = ceil($total_rows / $limit);
    $users = $userRepo->getPendingVerificationsWithPagination($limit, $offset);

    $response['success'] = true;
    $response['users'] = $users;
    $response['total_pages'] = $total_pages;
    $response['current_page'] = $page;
    echo json_encode($response);
    exit;
}

// ===== REGULAR ROLE FILTERS =====
if ($role_filter !== 'all') {
    $total_rows = $userRepo->countUsersByRole($role_filter, $search_term);
    $total_pages = ceil($total_rows / $limit);
    $users = $userRepo->getUsersByRoleWithPagination($role_filter, $search_term, $limit, $offset);
} else {
    // All users - need to add getAllWithPagination method if you want
    // For now, keep existing logic or add method
    $all_users = $userRepo->getAll();

    if (!empty($search_term)) {
        $search_lower = strtolower($search_term);
        $all_users = array_filter($all_users, function ($user) use ($search_lower) {
            return strpos(strtolower($user['full_name']), $search_lower) !== false ||
                strpos(strtolower($user['email']), $search_lower) !== false;
        });
    }

    $total_rows = count($all_users);
    $total_pages = ceil($total_rows / $limit);
    $users = array_slice($all_users, $offset, $limit);
}

$formatted_users = [];
foreach ($users as $user) {
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

$response['success'] = true;
$response['users'] = $formatted_users;
$response['total_pages'] = $total_pages;
$response['current_page'] = $page;

echo json_encode($response);
