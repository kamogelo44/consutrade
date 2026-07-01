<?php
/*
 * ConsuTrade - Upload Verification Document
 * Author: Kamogelo Phale
 * 
 * Allows sellers to upload identity verification documents.
 * Uses AdminService for file handling.
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$isLoggedIn || !$currentUser instanceof Seller) {
    $response['message'] = 'Unauthorized. Only sellers can upload verification documents.';
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

// ============================================================
// VALIDATE DOCUMENT TYPE
// ============================================================
$validTypes = ['id', 'proof_address'];
if (!in_array($docType, $validTypes)) {
    $response['message'] = 'Invalid document type. Only ID and Proof of Address are accepted.';
    echo json_encode($response);
    exit;
}

// ============================================================
// DEBUG: Log the upload attempt
// ============================================================
error_log("Verification upload attempt - Seller ID: $seller_id, Document Type: $docType");
error_log("File: " . print_r($_FILES['document'], true));

// Use AdminService for document upload
$result = $adminService->uploadVerification(
    $seller_id,
    $_FILES['document'],
    $docType
);

// ============================================================
// DEBUG: Log the result
// ============================================================
error_log("Upload result: " . print_r($result, true));

$response['success'] = $result['success'];
$response['message'] = $result['message'];

echo json_encode($response);
