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

// Check if user is logged in
if (!$isLoggedIn) {
    $response['message'] = 'Unauthorized. Please login.';
    echo json_encode($response);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$productId = (int) ($data['product_id'] ?? 0);
$newStatus = $data['status'] ?? '';
$suspendedReason = $data['reason'] ?? '';

// Validate input
if ($productId <= 0 || empty($newStatus)) {
    $response['message'] = 'Invalid request. Product ID and status are required.';
    echo json_encode($response);
    exit;
}

// Validate status value
if (!in_array($newStatus, ['active', 'suspended'])) {
    $response['message'] = 'Invalid status value.';
    echo json_encode($response);
    exit;
}

// Get product
$product = $productRepo->getProductObject($productId);

if (!$product) {
    $response['message'] = 'Product not found.';
    echo json_encode($response);
    exit;
}

// Determine action
$action = ($newStatus === 'active') ? 'activate' : 'suspend';
$currentUserId = $currentUser->getUserId();

// Check permissions
$hasPermission = false;
$suspendedBy = 'seller';

if ($auth->isAdmin()) {
    // Admin can suspend or activate any product
    $hasPermission = true;
    $suspendedBy = 'admin';
} elseif ($auth->isSeller() && $product->getSellerId() === $currentUserId) {
    // Seller can only activate if product wasn't admin-suspended
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

// Update product status
$result = $productRepo->updateProductStatus($productId, $product->getSellerId(), $action, $suspendedBy, $suspendedReason);
$response['success'] = $result['success'];
$response['message'] = $result['message'];

echo json_encode($response);
