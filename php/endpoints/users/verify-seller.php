<?php
/*
 * ConsuTrade - Verify Seller (Admin AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows admins to approve or reject seller verification documents.
 */

require_once dirname(__DIR__, 3) . '/init.php';

// Rate limit: 20 verification decisions per minute
rateLimit('admin_verify_seller', 20, 60);

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

$result = $adminService->verifySeller($sellerId, $decision);

$response['success'] = $result['success'];
$response['message'] = $result['message'];

echo json_encode($response);
