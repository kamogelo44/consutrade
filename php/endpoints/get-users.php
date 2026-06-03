<?php
/*
 * ConsuTrade - Get Users (AJAX)
 * Author: Kamogelo Phale
 * 
 * Handles user listing for admin panel with filtering, pagination, and search.
 */

require_once dirname(__DIR__, 2) . '/init.php';
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
    $offset = ($page - 1) * $limit;

    if ($recentOnly) {
        $users = $userRepo->getRecentUsers($limit);
        $response['success'] = true;
        $response['users'] = $users;
        $response['total_pages'] = 1;
        $response['current_page'] = 1;
        echo json_encode($response);
        exit;
    }

    if ($roleFilter === 'pending') {
        $totalRows = $userRepo->getPendingVerificationsCount();
        $totalPages = ceil($totalRows / $limit);
        $users = $userRepo->getPendingVerificationsWithPagination($limit, $offset);

        $response['success'] = true;
        $response['users'] = $users;
        $response['total_pages'] = $totalPages;
        $response['current_page'] = $page;
        echo json_encode($response);
        exit;
    }

    if ($roleFilter !== 'all') {
        $totalRows = $userRepo->countUsersByRole($roleFilter, $searchTerm);
        $totalPages = ceil($totalRows / $limit);
        $users = $userRepo->getUsersByRoleWithPagination($roleFilter, $searchTerm, $limit, $offset);
    } else {
        $allUsers = $userRepo->getAll('all', $searchTerm, 0, 0);
        $totalRows = count($allUsers);
        $totalPages = ceil($totalRows / $limit);
        $users = $userRepo->getAll('all', $searchTerm, $limit, $offset);
    }

    $response['success'] = true;
    $response['users'] = $users;
    $response['total_pages'] = $totalPages;
    $response['current_page'] = $page;
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    $response['file'] = $e->getFile();
    $response['line'] = $e->getLine();
}

echo json_encode($response);
