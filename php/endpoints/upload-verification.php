<?php
/*
 * ConsuTrade - Upload Verification Document
 * Author: Kamogelo Phale
 * 
 * Allows sellers to upload identity verification documents
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$is_logged_in || ($current_user['role'] ?? '') !== 'seller') {
    $response['message'] = 'Unauthorized.';
    echo json_encode($response);
    exit;
}

$seller_id = $current_user_id;

if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'No document uploaded.';
    echo json_encode($response);
    exit;
}

$file    = $_FILES['document'];
$docType = $_POST['document_type'] ?? 'id';
$maxSize = 5 * 1024 * 1024; // 5MB
$allowed = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];

if (!in_array($file['type'], $allowed)) {
    $response['message'] = 'Only JPG, PNG, or PDF files are allowed.';
    echo json_encode($response);
    exit;
}

if ($file['size'] > $maxSize) {
    $response['message'] = 'File must be less than 5MB.';
    echo json_encode($response);
    exit;
}

// Create upload directory
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/uploads/verifications/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate filename
$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'seller_' . $seller_id . '_' . time() . '.' . $ext;
$dest     = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    $response['message'] = 'Could not upload file.';
    echo json_encode($response);
    exit;
}

$docPath = 'uploads/verifications/' . $filename;

// Insert or update verification record
$checkSql = "SELECT verification_id FROM seller_verification WHERE seller_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param('i', $seller_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    // Update existing record
    $sql = "UPDATE seller_verification
            SET document_path = ?, document_type = ?, document_verified = 0, last_check = NOW()
            WHERE seller_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $docPath, $docType, $seller_id);
} else {
    // Insert new record
    $sql = "INSERT INTO seller_verification (seller_id, document_path, document_type, last_check)
            VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iss', $seller_id, $docPath, $docType);
}
$checkStmt->close();

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Document uploaded. Awaiting verification.';
} else {
    $response['message'] = 'Could not save document.';
    unlink($dest);
}
$stmt->close();

echo json_encode($response);