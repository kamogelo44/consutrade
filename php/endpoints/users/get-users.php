<?php
/*
 * ConsuTrade - Get Users (AJAX)
 * Author: Kamogelo Phale
 * 
 * Handles user listing for admin panel with filtering, pagination, and search.
 */

require_once dirname(__DIR__, 3) . '/init.php';

// Rate limit: 30 requests per minute (admin only)
rateLimit('admin_get_users', 30, 60);

header('Content-Type: application/json');

$response = ['success' => false, 'users' => [], 'total_pages' => 1, 'current_page' => 1];

if (!$auth->isAdmin()) {
    $response['error'] = 'Unauthorized. Admin access required.';
    echo json_encode($response);
    exit;
}

try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $roleFilter = $_GET['role'] ?? 'all';
    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
    $recentOnly = isset($_GET['recent']) && $_GET['recent'] === 'true';

    $limit = $recentOnly ? 5 : (isset($_GET['limit']) ? (int)$_GET['limit'] : 10);

    if ($recentOnly) {
        $users = $userRepo->findRecent($limit);
        $response['success'] = true;
        $response['users'] = $users;
        $response['total_pages'] = 1;
        $response['current_page'] = 1;
        echo json_encode($response);
        exit;
    }

    $result = $adminService->getUsers($roleFilter, $searchTerm, $page, $limit);

    $response['success'] = true;
    $response['users'] = $result['users'];
    $response['total_pages'] = $result['total_pages'];
    $response['current_page'] = $result['current_page'];
} catch (Exception $e) {
    $response['error'] = 'Could not load users.';
}

echo json_encode($response);
