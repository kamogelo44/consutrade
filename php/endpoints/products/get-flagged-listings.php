<?php
/*
 * ConsuTrade - Get Flagged Listings (Admin AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns pending product reports for admin review
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'reports' => [], 'total_pages' => 1, 'total' => 0];

// Check if user is admin
if (!$isLoggedIn || !$currentUser instanceof Admin) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

// Get and sanitize parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(50, (int)$_GET['limit']) : 20;
$offset = ($page - 1) * $limit;

// Get pending reports with details
$reports = $reportRepo->getPendingReportsWithDetails($limit, $offset);
$total = $reportRepo->getPendingReportsCount();

$response['success'] = true;
$response['reports'] = $reports;
$response['total_pages'] = ceil($total / $limit);
$response['current_page'] = $page;
$response['total'] = $total;

echo json_encode($response);
