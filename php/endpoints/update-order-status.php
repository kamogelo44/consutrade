<?php
/*
 * ConsuTrade - Update Order Status (AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows sellers and admins to update order status
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check authentication
if ($auth->isAdminLoggedIn()) {
    $role    = 'admin';
    $user_id = $current_user_id;
} elseif ($auth->isSellerLoggedIn()) {
    $role    = 'seller';
    $user_id = $current_user_id;
} else {
    $response['message'] = 'Unauthorized.';
    echo json_encode($response);
    exit;
}

$input      = json_decode(file_get_contents('php://input'), true);
$order_id   = (int) ($input['order_id'] ?? 0);
$new_status = $input['status'] ?? '';

if ($order_id <= 0) {
    $response['message'] = 'Invalid order.';
    echo json_encode($response);
    exit;
}

// Sellers use repository; admins have direct access
if ($role === 'seller') {
    $result = $orderRepo->updateSellerOrderStatus($order_id, $user_id, $new_status);
    $response['success'] = $result['success'];
    $response['message'] = $result['message'];
} else {
    // Admin — verify order exists, then update
    $check_sql = "SELECT status FROM orders WHERE order_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $order_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        $response['message'] = 'Order not found.';
        echo json_encode($response);
        $check_stmt->close();
        exit;
    }

    $order = $check_result->fetch_assoc();
    $check_stmt->close();

    // Admin can update to any valid status
    $allowed_statuses = ['processing', 'shipped', 'completed', 'cancelled'];
    if (!in_array($new_status, $allowed_statuses)) {
        $response['message'] = 'Invalid status.';
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();

    try {
        $update_sql = "UPDATE orders SET status = ? WHERE order_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('si', $new_status, $order_id);

        if (!$update_stmt->execute()) {
            throw new Exception('Update failed');
        }
        $update_stmt->close();

        if ($new_status === 'cancelled') {
            $productRepo->restoreOrderStock($order_id);
        }

        $conn->commit();

        $response['success'] = true;
        $response['message'] = 'Order status updated.';

    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Could not update order.';
    }
}

echo json_encode($response);