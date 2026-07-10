<?php
/*
 * ConsuTrade - Upload Verification Document
 * Author: Kamogelo Phale
 * 
 * Allows sellers to upload identity verification documents.
 */

require_once dirname(__DIR__, 3) . '/init.php';

// Rate limit: 5 uploads per hour
rateLimit('upload_verification', 5, 3600);

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$isLoggedIn || !$currentUser->hasRole('seller')) {
    $response['message'] = 'Unauthorized. Only sellers can upload verification documents.';
    echo json_encode($response);
    exit;
}

if (!$currentUser->isEmailVerified()) {
    $response['message'] = 'Please verify your email address before uploading verification documents.';
    echo json_encode($response);
    exit;
}

$seller_id = $currentUser->getUserId();

if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'No document uploaded or upload error.';
    echo json_encode($response);
    exit;
}

$docType = $_POST['document_type'] ?? 'id';

$validTypes = ['id', 'proof_address'];
if (!in_array($docType, $validTypes)) {
    $response['message'] = 'Invalid document type. Only ID and Proof of Address are accepted.';
    echo json_encode($response);
    exit;
}

$result = $adminService->uploadVerification($seller_id, $_FILES['document'], $docType);

$response['success'] = $result['success'];
$response['message'] = $result['message'];

echo json_encode($response);
