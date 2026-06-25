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
    $response['message'] = 'No document uploaded.';
    echo json_encode($response);
    exit;
}

$docType = $_POST['document_type'] ?? 'id';

// Use AdminService for document upload
$result = $adminService->uploadVerification(
    $seller_id,
    $_FILES['document'],
    $docType
);

$response['success'] = $result['success'];
$response['message'] = $result['message'];

echo json_encode($response);
