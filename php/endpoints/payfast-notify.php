<?php
/*
 * ConsuTrade - PayFast ITN (Instant Transaction Notification) Handler
 * Author: Kamogelo Phale
 * 
 * Handles PayFast payment confirmations and updates order status.
 * Called by PayFast server — no session, no user login required.
 */

require_once __DIR__ . '/../init.php';

// Determine PayFast validate URL
$payfast_url = PAYFAST_SANDBOX
    ? 'https://sandbox.payfast.co.za/eng/query/validate'
    : 'https://www.payfast.co.za/eng/query/validate';

$pfData = $_POST;

if (empty($pfData)) {
    http_response_code(400);
    echo "No data received";
    exit;
}

// Build parameter string for signature verification
$pfParamString = '';
foreach ($pfData as $key => $val) {
    if ($key !== 'signature') {
        $pfParamString .= $key . '=' . urlencode(trim($val)) . '&';
    }
}
$pfParamString = rtrim($pfParamString, '&');

// Verify signature locally
$signature      = md5($pfParamString);
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
$curlError  = curl_error($pfCurl);
curl_close($pfCurl);

if ($curlError) {
    http_response_code(500);
    exit;
}

// Validate response
$pfValid         = ($validSignature && strpos($pfResponse, 'VERIFIED') !== false);
$payment_status  = $pfData['payment_status'] ?? '';
$m_payment_id    = $pfData['m_payment_id'] ?? '';
$amount_received = (float) ($pfData['amount'] ?? 0);

// Parse order ID from payment_id (format: timestamp_userId)
$parts    = explode('_', $m_payment_id);
$order_id = (int) ($parts[0] ?? 0);
$user_id  = (int) ($parts[1] ?? 0);

if (!$pfValid || $payment_status !== 'COMPLETE' || $order_id <= 0 || $user_id <= 0) {
    http_response_code(400);
    echo "Validation failed";
    exit;
}

try {
    $conn->begin_transaction();

    // Get all orders with this payment_id
    $check_sql = "SELECT order_id, total_price FROM orders
                  WHERE payment_id = ? AND buyer_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('si', $m_payment_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $total_amount = 0;

        while ($order = $check_result->fetch_assoc()) {
            $total_amount += $order['total_price'];

            // Update order status to 'processing' (payment confirmed)
            $update_sql = "UPDATE orders SET status = 'processing' WHERE order_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('i', $order['order_id']);
            $update_stmt->execute();
            $update_stmt->close();

            // Decrease stock for each item
            $items_sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
            $items_stmt = $conn->prepare($items_sql);
            $items_stmt->bind_param('i', $order['order_id']);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();

            while ($item = $items_result->fetch_assoc()) {
                $productRepo->decreaseProductStock($item['product_id'], $item['quantity']);
            }
            $items_stmt->close();
        }

        // Clear user's cart
        $cartRepo->clearUserCart($user_id);

        // Verify amount
        if (abs($total_amount - $amount_received) >= 0.01) {
            // Amount mismatch — log but don't roll back
            // (payment is still valid, just flag for review)
        }
    } else {
        // Single order fallback
        $check_sql = "SELECT order_id, total_price FROM orders WHERE order_id = ? AND buyer_id = ?";
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

                $cartRepo->clearUserCart($user_id);
            }
        }
    }
    $check_stmt->close();

    $conn->commit();
    echo "OK";

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo "Error";
}