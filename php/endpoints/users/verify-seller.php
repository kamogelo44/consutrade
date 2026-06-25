<?php
/*
 * ConsuTrade - Verify Seller (Admin AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows admins to approve or reject seller verification documents.
 * Uses AdminService for all business logic.
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$auth->isAdmin()) {
    $response['message'] = 'Unauthorized.';
    echo json_encode($response);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$sellerId = (int) ($input['seller_id'] ?? 0);
$decision = $input['decision'] ?? '';

if ($sellerId <= 0) {
    $response['message'] = 'Invalid seller.';
    echo json_encode($response);
    exit;
}

if (!in_array($decision, ['approve', 'reject'])) {
    $response['message'] = 'Invalid decision.';
    echo json_encode($response);
    exit;
}

// Use AdminService for seller verification
$result = $adminService->verifySeller($sellerId, $decision);

$response['success'] = $result['success'];
$response['message'] = $result['message'];

echo json_encode($response);
