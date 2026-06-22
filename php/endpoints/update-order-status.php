<?php
/*
 * ConsuTrade - Update Order Status (AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows:
 * - Sellers to update orders they received (processing → shipped → completed)
 * - Admins to update any order
 * - Buyers to CANCEL their own pending orders (uses OrderRepository)
 */

require_once dirname(__DIR__, 2) . '/init.php';

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
$userRole = $currentUserRole;

// ========== BUYER CANCELLATION ==========
if ($userRole === 'buyer') {
    if ($newStatus !== 'cancelled') {
        $response['message'] = 'Buyers can only cancel orders.';
        echo json_encode($response);
        exit;
    }

    $result = $orderRepo->cancelByBuyer($orderId, $userId);

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
if ($userRole === 'seller') {
    $orderData = $orderRepo->findById($orderId, $userId, 'seller');

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

    $result = $orderRepo->updateStatus($orderId, $userId, $newStatus);
    $response['success'] = $result['success'];
    $response['message'] = $result['message'];

    echo json_encode($response);
    exit;
}

// ========== ADMIN UPDATE ==========
if ($userRole === 'admin') {
    $orderData = $orderRepo->findAll();
    $targetOrder = null;

    foreach ($orderData as $ord) {
        if ($ord['order_id'] == $orderId) {
            $targetOrder = $ord;
            break;
        }
    }

    if (!$targetOrder) {
        $response['message'] = 'Order not found.';
        echo json_encode($response);
        exit;
    }

    $orderObj = new Order($targetOrder);

    if (!$orderObj->canTransitionTo($newStatus)) {
        $allowed = implode(', ', $orderObj->getAllowedNextStatuses());
        $response['message'] = "Cannot change from {$orderObj->getStatus()} to {$newStatus}. Allowed: $allowed";
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();

    try {
        $updateSql = "UPDATE orders SET status = ? WHERE order_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param('si', $newStatus, $orderId);

        if (!$updateStmt->execute()) {
            throw new Exception('Update failed');
        }
        $updateStmt->close();

        if ($newStatus === 'cancelled') {
            $productRepo->restoreStockFromOrder($orderId);
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
