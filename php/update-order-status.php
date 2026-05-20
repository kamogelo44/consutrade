<?php
/*
 * ConsuTrade - Cancel Order (AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows buyers to cancel their pending orders
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in as a buyer
if (!isLoggedIn()) {
    $response['message'] = 'Please login to cancel orders.';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = (int)($input['order_id'] ?? 0);
$buyer_id = getCurrentUserId();

if ($order_id <= 0) {
    $response['message'] = 'Invalid order ID';
    echo json_encode($response);
    exit;
}

// Verify order belongs to this buyer and is pending
$check_sql = "SELECT order_id, status FROM orders WHERE order_id = ? AND buyer_id = ? AND status = 'pending'";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('ii', $order_id, $buyer_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $response['message'] = 'Order not found or cannot be cancelled';
    echo json_encode($response);
    $check_stmt->close();
    exit;
}
$check_stmt->close();

// Begin transaction
$conn->begin_transaction();

try {
    // Update order status to cancelled
    $update_sql = "UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND buyer_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('ii', $order_id, $buyer_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to cancel order');
    }
    $update_stmt->close();

    // Restore stock
    restoreOrderStock($conn, $order_id);

    $conn->commit();
    
    $response['success'] = true;
    $response['message'] = 'Order cancelled successfully';
    
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Failed to cancel order. Please try again.';
}

echo json_encode($response);
?>