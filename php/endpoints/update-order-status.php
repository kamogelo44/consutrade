<?php
/*
 * ConsuTrade - Update Order Status (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$isLoggedIn) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = (int) ($input['order_id'] ?? 0);
$newStatus = $input['status'] ?? '';

if ($orderId <= 0 || empty($newStatus)) {
    $response['message'] = 'Invalid request';
    echo json_encode($response);
    exit;
}

// Handle seller updates
if ($currentUser instanceof Seller) {
    $orderData = $orderRepo->getOrderDetails($orderId, $currentUser->getUserId(), 'seller');

    if (!$orderData) {
        $response['message'] = 'Order not found';
        echo json_encode($response);
        exit;
    }

    $order = new Order($orderData);

    if (!$order->canTransitionTo($newStatus)) {
        $allowed = implode(', ', $order->getAllowedNextStatuses());
        $response['message'] = "Cannot change from {$order->getStatus()} to {$newStatus}. Allowed: $allowed";
        echo json_encode($response);
        exit;
    }

    $result = $orderRepo->updateSellerOrderStatus($orderId, $currentUser->getUserId(), $newStatus);
    $response['success'] = $result['success'];
    $response['message'] = $result['message'];

    // Handle admin updates
} elseif ($currentUser instanceof Admin) {
    $orderData = $orderRepo->getAllOrders();
    $targetOrder = null;

    foreach ($orderData as $ord) {
        if ($ord['order_id'] == $orderId) {
            $targetOrder = $ord;
            break;
        }
    }

    if (!$targetOrder) {
        $response['message'] = 'Order not found';
        echo json_encode($response);
        exit;
    }

    $order = new Order($targetOrder);

    if (!$order->canTransitionTo($newStatus)) {
        $allowed = implode(', ', $order->getAllowedNextStatuses());
        $response['message'] = "Cannot change from {$order->getStatus()} to {$newStatus}. Allowed: $allowed";
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
            $productRepo->restoreOrderStock($orderId);
        }

        $conn->commit();

        $response['success'] = true;
        $response['message'] = 'Order status updated successfully.';
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Could not update order status.';
    }
} else {
    $response['message'] = 'Unauthorized. Only sellers and admins can update orders.';
}

echo json_encode($response);
