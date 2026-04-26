<?php
/*
 * ConsuTrade - PayFast ITN (Instant Transaction Notification) Handler
 * Author: Kamogelo Phale
 * 
 * This file handles PayFast payment confirmations and updates order status
 */

require_once 'config.php';
require_once 'helpers.php';

// PayFast Sandbox settings
$payfast_url = 'https://sandbox.payfast.co.za/eng/query/validate';
$merchant_id = PAYFAST_MERCHANT_ID;
$merchant_key = PAYFAST_MERCHANT_KEY;

// Get all POST data from PayFast
$pfData = $_POST;

// If no data received, log and exit
if (empty($pfData)) {
    error_log("PayFast: No data received");
    exit;
}

// Prepare variables for signature verification
$pfParamString = '';
foreach ($pfData as $key => $val) {
    if ($key !== 'signature') {
        $pfParamString .= $key . '=' . urlencode($val) . '&';
    }
}
$pfParamString = rtrim($pfParamString, '&');

// Calculate signature
$signature = md5($pfParamString . '&passphrase=');
$validSignature = ($signature === ($pfData['signature'] ?? ''));

// Verify with PayFast server
$pfRequest = '';
foreach ($pfData as $key => $val) {
    $pfRequest .= $key . '=' . urlencode($val) . '&';
}
$pfRequest = rtrim($pfRequest, '&');

$pfCurl = curl_init();
curl_setopt($pfCurl, CURLOPT_URL, $payfast_url);
curl_setopt($pfCurl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($pfCurl, CURLOPT_HEADER, false);
curl_setopt($pfCurl, CURLOPT_POST, true);
curl_setopt($pfCurl, CURLOPT_POSTFIELDS, $pfRequest);
curl_setopt($pfCurl, CURLOPT_SSL_VERIFYPEER, false);

$pfResponse = curl_exec($pfCurl);
curl_close($pfCurl);

// Check if payment was successful
$pfValid = ($validSignature && strpos($pfResponse, 'VERIFIED') !== false);
$payment_status = $pfData['payment_status'] ?? '';
$m_payment_id = $pfData['m_payment_id'] ?? '';
$amount = (float)($pfData['amount'] ?? 0);

// Parse order ID from m_payment_id (format: orderId_userId)
$parts = explode('_', $m_payment_id);
$order_id = (int)($parts[0] ?? 0);
$user_id = (int)($parts[1] ?? 0);

if ($pfValid && $payment_status === 'COMPLETE' && $order_id > 0 && $user_id > 0) {
    try {
        $conn->begin_transaction();
        
        // Check if order exists and belongs to this user
        $check_sql = "SELECT order_id, status, total_price FROM orders WHERE order_id = ? AND buyer_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('ii', $order_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $order = $check_result->fetch_assoc();
            
            // Verify amount matches
            if (abs($order['total_price'] - $amount) < 0.01) {
                // Update order status to 'processing' (payment confirmed)
                $update_sql = "UPDATE orders SET status = 'processing', payment_id = ? WHERE order_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('si', $m_payment_id, $order_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                error_log("PayFast: Order #$order_id updated to 'processing' for user $user_id");
            } else {
                error_log("PayFast: Amount mismatch for order #$order_id - Expected: {$order['total_price']}, Received: $amount");
            }
        } else {
            error_log("PayFast: Order #$order_id not found for user $user_id");
        }
        $check_stmt->close();
        
        // Clear user's cart after successful payment
        $clear_sql = "DELETE FROM cart WHERE user_id = ?";
        $clear_stmt = $conn->prepare($clear_sql);
        $clear_stmt->bind_param('i', $user_id);
        $clear_stmt->execute();
        $clear_stmt->close();
        
        // Update session cart count if session exists
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['cart_count'] = 0;
        session_write_close();
        
        $conn->commit();
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("PayFast order update failed: " . $e->getMessage());
    }
} else {
    error_log("PayFast payment validation failed. Valid: $pfValid, Status: $payment_status");
}

$conn->close();
?>