<?php
/*
 * ConsuTrade - PayFast ITN (Instant Transaction Notification) Handler
 * Author: Kamogelo Phale
 * 
 * This file handles PayFast payment confirmations
 */

require_once 'config.php';

// PayFast Sandbox settings
$payfast_url = 'https://sandbox.payfast.co.za/eng/query/validate';
$merchant_id = '10047996';
$merchant_key = 'f6r9pv9pnq6so';

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
$amount = $pfData['amount'] ?? 0;

// Parse order ID from m_payment_id (format: timestamp_userid)
$parts = explode('_', $m_payment_id);
$user_id = (int)($parts[1] ?? 0);
$order_timestamp = $parts[0] ?? time();

if ($pfValid && $payment_status === 'COMPLETE' && $user_id > 0) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Get cart items for this user
        $cart_sql = "SELECT c.product_id, c.quantity, p.price, p.seller_id, p.title 
                     FROM cart c 
                     JOIN products p ON c.product_id = p.product_id 
                     WHERE c.user_id = ?";
        $cart_stmt = $conn->prepare($cart_sql);
        $cart_stmt->bind_param('i', $user_id);
        $cart_stmt->execute();
        $cart_result = $cart_stmt->get_result();
        
        $cart_items = [];
        while ($item = $cart_result->fetch_assoc()) {
            $cart_items[] = $item;
        }
        
        if (count($cart_items) > 0) {
            // For each cart item, create a separate order (since your table has product_id directly)
            $order_sql = "INSERT INTO orders (buyer_id, seller_id, product_id, quantity, total_price, status, created_at) 
                          VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
            $order_stmt = $conn->prepare($order_sql);
            
            foreach ($cart_items as $item) {
                $item_total = $item['price'] * $item['quantity'];
                $order_stmt->bind_param('iiidd', $user_id, $item['seller_id'], $item['product_id'], $item['quantity'], $item_total);
                $order_stmt->execute();
            }
            
            $order_stmt->close();
            
            // Clear user's cart from database
            $clear_sql = "DELETE FROM cart WHERE user_id = ?";
            $clear_stmt = $conn->prepare($clear_sql);
            $clear_stmt->bind_param('i', $user_id);
            $clear_stmt->execute();
            $clear_stmt->close();
            
            // Update session cart count
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['cart_count'] = 0;
            session_write_close();
            
            error_log("PayFast: " . count($cart_items) . " orders created for user $user_id");
        } else {
            error_log("PayFast: Cart is empty for user $user_id");
        }
        
        $cart_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("PayFast order creation failed: " . $e->getMessage());
    }
} else {
    error_log("PayFast payment failed or invalid");
}

$conn->close();
?>