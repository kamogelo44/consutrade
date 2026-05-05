<?php
/*
 * ConsuTrade - PayFast ITN (Instant Transaction Notification) Handler
 * Author: Kamogelo Phale
 * 
 * This file handles PayFast payment confirmations and updates order status
 * Called by PayFast server - no session, no user login required
 */

require_once __DIR__ . '/../init.php';

// PayFast URLs
if (PAYFAST_SANDBOX) {
    $payfast_url = 'https://sandbox.payfast.co.za/eng/query/validate';
} else {
    $payfast_url = 'https://www.payfast.co.za/eng/query/validate';
}

// Get all POST data from PayFast
$pfData = $_POST;

// If no data received, log and exit
if (empty($pfData)) {
    error_log("PayFast ITN: No data received");
    http_response_code(400);
    echo "No data received";
    exit;
}

error_log("PayFast ITN: Processing notification for payment_id: " . ($pfData['m_payment_id'] ?? 'unknown'));

// Prepare variables for signature verification
$pfParamString = '';
foreach ($pfData as $key => $val) {
    if ($key !== 'signature') {
        $pfParamString .= $key . '=' . urlencode(trim($val)) . '&';
    }
}
$pfParamString = rtrim($pfParamString, '&');

// Calculate signature (no passphrase for standard PayFast)
$signature = md5($pfParamString);
$validSignature = ($signature === ($pfData['signature'] ?? ''));

// Verify with PayFast server
$pfRequest = '';
foreach ($pfData as $key => $val) {
    $pfRequest .= $key . '=' . urlencode(trim($val)) . '&';
}
$pfRequest = rtrim($pfRequest, '&');

$pfCurl = curl_init();
curl_setopt($pfCurl, CURLOPT_URL, $payfast_url);
curl_setopt($pfCurl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($pfCurl, CURLOPT_HEADER, false);
curl_setopt($pfCurl, CURLOPT_POST, true);
curl_setopt($pfCurl, CURLOPT_POSTFIELDS, $pfRequest);
curl_setopt($pfCurl, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($pfCurl, CURLOPT_TIMEOUT, 30);
curl_setopt($pfCurl, CURLOPT_CONNECTTIMEOUT, 10);

$pfResponse = curl_exec($pfCurl);
$curlError = curl_error($pfCurl);
curl_close($pfCurl);

if ($curlError) {
    error_log("PayFast ITN: cURL Error - " . $curlError);
    http_response_code(500);
    exit;
}

// Check if payment was successful
$pfValid = ($validSignature && strpos($pfResponse, 'VERIFIED') !== false);
$payment_status = $pfData['payment_status'] ?? '';
$m_payment_id = $pfData['m_payment_id'] ?? '';
$amount_received = (float)($pfData['amount'] ?? 0);

// Parse order ID from m_payment_id (format: orderId_userId)
$parts = explode('_', $m_payment_id);
$order_id = (int)($parts[0] ?? 0);
$user_id = (int)($parts[1] ?? 0);

error_log("PayFast ITN: Order ID: $order_id, User ID: $user_id, Status: $payment_status, Valid: " . ($pfValid ? 'Yes' : 'No'));

if ($pfValid && $payment_status === 'COMPLETE' && $order_id > 0 && $user_id > 0) {
    try {
        $conn->begin_transaction();
        
        // Get all orders with this payment_id (multiple orders for multiple sellers)
        $check_sql = "SELECT order_id, status, total_price FROM orders WHERE payment_id = ? AND buyer_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('si', $m_payment_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $total_amount = 0;
            $order_ids = [];
            
            while ($order = $check_result->fetch_assoc()) {
                $total_amount += $order['total_price'];
                $order_ids[] = $order['order_id'];
                
                // Update order status to 'processing' (payment confirmed)
                $update_sql = "UPDATE orders SET status = 'processing' WHERE order_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('i', $order['order_id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                error_log("PayFast ITN: Order #{$order['order_id']} updated to 'processing'");
                
                // Get order items to reduce stock
                $items_sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
                $items_stmt = $conn->prepare($items_sql);
                $items_stmt->bind_param('i', $order['order_id']);
                $items_stmt->execute();
                $items_result = $items_stmt->get_result();
                
                while ($item = $items_result->fetch_assoc()) {
                    // Reduce stock quantity
                    $stock_sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?";
                    $stock_stmt = $conn->prepare($stock_sql);
                    $stock_stmt->bind_param('iii', $item['quantity'], $item['product_id'], $item['quantity']);
                    $stock_stmt->execute();
                    $stock_stmt->close();
                    
                    error_log("PayFast ITN: Reduced stock for product #{$item['product_id']} by {$item['quantity']}");
                }
                $items_stmt->close();
            }
            
            // Verify amount matches total
            if (abs($total_amount - $amount_received) >= 0.01) {
                error_log("PayFast ITN: Amount mismatch - Expected: $total_amount, Received: $amount_received");
            }
            
            // Clear user's cart (for all orders in this payment)
            $clear_sql = "DELETE FROM cart WHERE user_id = ?";
            $clear_stmt = $conn->prepare($clear_sql);
            $clear_stmt->bind_param('i', $user_id);
            $clear_stmt->execute();
            $clear_stmt->close();
            
            error_log("PayFast ITN: Cleared cart for user $user_id");
            
        } else {
            // Single order fallback (legacy)
            $check_sql = "SELECT order_id, status, total_price FROM orders WHERE order_id = ? AND buyer_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param('ii', $order_id, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $order = $check_result->fetch_assoc();
                
                if (abs($order['total_price'] - $amount_received) < 0.01) {
                    $update_sql = "UPDATE orders SET status = 'processing' WHERE order_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param('i', $order_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    error_log("PayFast ITN: Order #$order_id updated to 'processing'");
                    
                    // Clear user's cart
                    $clear_sql = "DELETE FROM cart WHERE user_id = ?";
                    $clear_stmt = $conn->prepare($clear_sql);
                    $clear_stmt->bind_param('i', $user_id);
                    $clear_stmt->execute();
                    $clear_stmt->close();
                }
            } else {
                error_log("PayFast ITN: No orders found for payment_id: $m_payment_id or order_id: $order_id");
            }
        }
        $check_stmt->close();
        
        $conn->commit();
        error_log("PayFast ITN: Transaction committed successfully");
        
        // Return valid response to PayFast
        echo "OK";
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("PayFast ITN: Transaction failed - " . $e->getMessage());
        http_response_code(500);
        echo "Error";
    }
} else {
    error_log("PayFast ITN: Validation failed - Valid: " . ($pfValid ? 'Yes' : 'No') . ", Status: $payment_status");
    http_response_code(400);
    echo "Validation failed";
}

$conn->close();
?>