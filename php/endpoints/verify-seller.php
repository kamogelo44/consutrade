<?php
/*
 * ConsuTrade - Verify Seller (Admin AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows admins to approve or reject seller verification documents
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$auth->isAdminLoggedIn()) {
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

if ($decision === 'approve') {
    $sql = "UPDATE seller_verification
            SET document_verified = 1,
                verified_at = NOW(),
                verification_score = verification_score + 25
            WHERE seller_id = ?";
} else {
    $sql = "UPDATE seller_verification
            SET document_verified = 0,
                document_path = NULL,
                document_type = NULL
            WHERE seller_id = ?";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $sellerId);

if ($stmt->execute()) {
    if ($decision === 'approve') {
        // Check if all verifications are complete (score >= 100)
        $checkSql = "SELECT verification_score FROM seller_verification WHERE seller_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param('i', $sellerId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        $checkStmt->close();

        if ($row && $row['verification_score'] >= 100) {
            $updateUserSql = "UPDATE users SET id_verified = 1 WHERE user_id = ?";
            $updateUserStmt = $conn->prepare($updateUserSql);
            $updateUserStmt->bind_param('i', $sellerId);
            $updateUserStmt->execute();
            $updateUserStmt->close();
        }
    }

    $response['success'] = true;
    $response['message'] = $decision === 'approve'
        ? 'Seller document approved.'
        : 'Seller document rejected.';
} else {
    $response['message'] = 'Could not update verification.';
}
$stmt->close();

echo json_encode($response);