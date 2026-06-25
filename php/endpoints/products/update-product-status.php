<?php
/*
 * ConsuTrade - Update Product Status (AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows admins and sellers to suspend/activate products.
 * If admin suspends a product, only admin can reactivate it.
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$isLoggedIn) {
    $response['message'] = 'Unauthorized. Please login.';
    echo json_encode($response);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$productId = (int) ($data['product_id'] ?? 0);
$newStatus = $data['status'] ?? '';
$suspendedReason = $data['reason'] ?? '';

if ($productId <= 0 || empty($newStatus)) {
    $response['message'] = 'Invalid request. Product ID and status are required.';
    echo json_encode($response);
    exit;
}

if (!in_array($newStatus, ['active', 'suspended'])) {
    $response['message'] = 'Invalid status value.';
    echo json_encode($response);
    exit;
}

$product = $productRepo->findById($productId);

if (!$product) {
    $response['message'] = 'Product not found.';
    echo json_encode($response);
    exit;
}

$action = ($newStatus === 'active') ? 'activate' : 'suspend';
$currentUserId = $currentUser->getUserId();

$hasPermission = false;
$suspendedBy = 'seller';

if ($auth->isAdmin()) {
    $hasPermission = true;
    $suspendedBy = 'admin';
} elseif ($auth->isSeller() && $product->getSellerId() === $currentUserId) {
    if ($action === 'activate' && $product->isAdminSuspended()) {
        $response['message'] = 'This product was suspended by an admin. Only an admin can reactivate it.';
        echo json_encode($response);
        exit;
    }
    $hasPermission = true;
    $suspendedBy = 'seller';
}

if (!$hasPermission) {
    $response['message'] = 'You do not have permission to update this product.';
    echo json_encode($response);
    exit;
}

$result = $productRepo->updateStatus($productId, $product->getSellerId(), $action, $suspendedBy, $suspendedReason);
$response['success'] = $result['success'];
$response['message'] = $result['message'];

echo json_encode($response);
