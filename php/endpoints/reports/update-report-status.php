<?php
/*
 * ConsuTrade - Update Report Status (Admin AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows admin to dismiss reports or suspend products
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$isLoggedIn || !$currentUser instanceof Admin) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$reportId = (int) ($input['report_id'] ?? 0);
$action = trim($input['action'] ?? '');
$adminNotes = trim($input['admin_notes'] ?? '');

if ($reportId <= 0) {
    $response['message'] = 'Invalid report ID.';
    echo json_encode($response);
    exit;
}

if (!in_array($action, ['dismiss', 'suspend'])) {
    $response['message'] = 'Invalid action.';
    echo json_encode($response);
    exit;
}

$report = $reportRepo->getReportWithDetails($reportId);
if (!$report) {
    $response['message'] = 'Report not found.';
    echo json_encode($response);
    exit;
}

$adminId = $currentUser->getUserId();
$productId = $report['product_id'];

if ($action === 'dismiss') {
    $success = $reportRepo->dismissReport($reportId, $adminId, $adminNotes);

    if ($success) {
        $response['success'] = true;
        $response['message'] = 'Report dismissed. Product remains active.';
    } else {
        $response['message'] = 'Failed to update report status.';
    }
} else if ($action === 'suspend') {
    $updateResult = $productRepo->updateStatus(
        $productId,
        $report['seller_id'],
        'suspend',
        'admin',
        'Product suspended due to user report: ' . ($report['reason_label'] ?? 'Violation')
    );

    if ($updateResult['success']) {
        $fullNotes = "Product suspended. " . $adminNotes;
        $success = $reportRepo->markActionTaken($reportId, $adminId, $fullNotes);

        if ($success) {
            $response['success'] = true;
            $response['message'] = 'Product has been suspended.';
        } else {
            $response['success'] = true;
            $response['message'] = 'Product suspended but failed to update report status.';
        }
    } else {
        $response['message'] = $updateResult['message'] ?? 'Failed to suspend product.';
    }
}

echo json_encode($response);
