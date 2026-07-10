<?php
/*
 * ConsuTrade - Get Verification Document (AJAX)
 * Author: Kamogelo Phale
 * 
 * Retrieves a seller's verification document for admin review.
 */

require_once dirname(__DIR__, 3) . '/init.php';

// Rate limit: 30 document views per minute (admin only)
rateLimit('admin_view_doc', 30, 60);

header('Content-Type: application/json');

$response = ['success' => false, 'has_document' => false];

if (!$auth->isAdmin()) {
    $response['error'] = 'Unauthorized. Admin access required.';
    echo json_encode($response);
    exit;
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($userId <= 0) {
    $response['error'] = 'Invalid user ID.';
    echo json_encode($response);
    exit;
}

$doc = $adminService->getVerificationDocument($userId);

if ($doc && !empty($doc['document_path'])) {
    $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $doc['document_path'];

    if (file_exists($filePath)) {
        $response['success'] = true;
        $response['has_document'] = true;
        $response['document_path'] = $doc['document_path'];
        $response['document_type'] = $doc['document_type'] ?? 'id';
        $response['uploaded_at'] = isset($doc['submitted_at'])
            ? date('d M Y, H:i', strtotime($doc['submitted_at']))
            : 'Unknown date';
    } else {
        $response['success'] = true;
        $response['has_document'] = false;
        $response['message'] = 'Document file is missing. Please ask the seller to re-upload.';
    }
} else {
    $response['success'] = true;
    $response['has_document'] = false;
    $response['message'] = 'No document found for this seller.';
}

echo json_encode($response);
