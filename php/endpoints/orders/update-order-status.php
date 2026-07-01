<?php
/*
 * ConsuTrade - Update Order Status (AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows:
 * - Sellers to update orders they received (pending → processing → shipped → completed)
 * - Admins to update any order
 * - Buyers to CANCEL their own pending orders
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$isLoggedIn) {
    $response['message'] = 'Unauthorized. Please log in.';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = (int) ($input['order_id'] ?? 0);
$newStatus = $input['status'] ?? '';

if ($orderId <= 0 || empty($newStatus)) {
    $response['message'] = 'Invalid request.';
    echo json_encode($response);
    exit;
}

$userId = $currentUser->getUserId();

// Check user roles (not just active role)
$isBuyer = $currentUser->hasRole('buyer');
$isSeller = $currentUser->hasRole('seller');
$isAdmin = $currentUser->hasRole('admin');

// ========== BUYER CANCELLATION ==========
if ($isBuyer) {
    if ($newStatus !== 'cancelled') {
        $response['message'] = 'Buyers can only cancel orders.';
        echo json_encode($response);
        exit;
    }

    // Check if order belongs to this buyer
    $orderCheck = $orderService->findByIdForBuyer($orderId, $userId);
    if (!$orderCheck) {
        $response['message'] = 'Order not found or you do not have permission.';
        echo json_encode($response);
        exit;
    }

    // Use OrderService for cancellation with stock restoration
    $result = $orderService->cancelByBuyer($orderId, $userId);

    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Order cancelled successfully.';
    } else {
        $response['message'] = 'Could not cancel order. Make sure it is still pending.';
    }

    echo json_encode($response);
    exit;
}

// ========== SELLER UPDATE ==========
if ($isSeller) {
    // Use OrderService for order lookup - use seller-specific method
    $orderData = $orderService->findByIdForSeller($orderId, $userId);

    if (!$orderData) {
        $response['message'] = 'Order not found or you do not have permission.';
        echo json_encode($response);
        exit;
    }

    $orderObj = new Order($orderData);

    if (!$orderObj->canTransitionTo($newStatus)) {
        $allowed = implode(', ', $orderObj->getAllowedNextStatuses());
        $response['message'] = "Cannot change from {$orderObj->getStatus()} to {$newStatus}. Allowed: $allowed";
        echo json_encode($response);
        exit;
    }

    $oldStatus = $orderData['status'];

    $conn->begin_transaction();

    try {
        // Use OrderService for status update with stock restoration
        $result = $orderService->updateStatus($orderId, $userId, $newStatus);

        if (!$result['success']) {
            throw new Exception($result['message']);
        }

        // If going from pending to processing, create transaction if missing
        if ($oldStatus === 'pending' && $newStatus === 'processing') {
            $transaction = $transactionRepo->findByOrderId($orderId);

            if (!$transaction) {
                $transactionRepo->createFromPayment(
                    $orderId,
                    'PF-MANUAL-' . time(),
                    $orderData['total_price']
                );
            }
        }

        $conn->commit();

        $response['success'] = true;
        $response['message'] = $result['message'];
    } catch (Exception $e) {
        $conn->rollback();
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

// ========== ADMIN UPDATE ==========
if ($isAdmin) {
    // Admin can view any order - use admin method
    $orderData = $orderService->findByIdForAdmin($orderId);

    if (!$orderData) {
        $response['message'] = 'Order not found.';
        echo json_encode($response);
        exit;
    }

    $orderObj = new Order($orderData);

    if (!$orderObj->canTransitionTo($newStatus)) {
        $allowed = implode(', ', $orderObj->getAllowedNextStatuses());
        $response['message'] = "Cannot change from {$orderObj->getStatus()} to {$newStatus}. Allowed: $allowed";
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();

    try {
        // Admin uses direct status update (bypasses seller validation)
        $updated = $orderRepo->updateStatusDirect($orderId, $newStatus);

        if (!$updated) {
            throw new Exception('Failed to update order status');
        }

        if ($newStatus === 'cancelled') {
            $productService->restoreStockFromOrder($orderId);
        }

        $conn->commit();

        $response['success'] = true;
        $response['message'] = 'Order status updated successfully.';
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Could not update order status.';
    }

    echo json_encode($response);
    exit;
}

// ========== NO PERMISSION ==========
$response['message'] = 'Unauthorized. You do not have permission to update this order.';
echo json_encode($response);
