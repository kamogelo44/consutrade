<?php
/*
 * ConsuTrade - Cancel Order (AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows buyers to cancel pending orders
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in using centralized auth
if (!$is_logged_in) {
    $response['message'] = 'Please login to cancel orders';
    echo json_encode($response);
    exit;
}

// Only buyers can cancel orders
if ($current_user['role'] !== 'buyer') {
    $response['message'] = 'Only buyers can cancel orders';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$buyer_id = $current_user_id;

if ($order_id <= 0) {
    $response['message'] = 'Invalid order ID';
    echo json_encode($response);
    exit;
}

// Check if order belongs to this buyer and is pending
$check_sql = "SELECT order_id, status, payment_id FROM orders WHERE order_id = ? AND buyer_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('ii', $order_id, $buyer_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $response['message'] = 'Order not found or does not belong to you';
    echo json_encode($response);
    $check_stmt->close();
    exit;
}

$order = $check_result->fetch_assoc();

// Only pending orders can be cancelled
if ($order['status'] !== 'pending') {
    $response['message'] = 'Only pending orders can be cancelled. Current status: ' . ucfirst($order['status']);
    echo json_encode($response);
    $check_stmt->close();
    exit;
}
$check_stmt->close();

// Start transaction to ensure data consistency
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
    
    // Restore product stock quantities
    $items_sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param('i', $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    while ($item = $items_result->fetch_assoc()) {
        // Increase stock back
        $stock_sql = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?";
        $stock_stmt = $conn->prepare($stock_sql);
        $stock_stmt->bind_param('ii', $item['quantity'], $item['product_id']);
        $stock_stmt->execute();
        $stock_stmt->close();
        
        error_log("Cancel Order: Restored {$item['quantity']} units of stock for product #{$item['product_id']}");
    }
    $items_stmt->close();
    
    $conn->commit();
    
    $response['success'] = true;
    $response['message'] = 'Order cancelled successfully. Stock has been restored.';
    
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Failed to cancel order: ' . $e->getMessage();
    error_log("Cancel Order Error: " . $e->getMessage());
}

echo json_encode($response);
?>