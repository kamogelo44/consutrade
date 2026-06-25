<?php
/*
 * ConsuTrade - Get Payment Status Endpoint
 * Author: Kamogelo Phale
 * 
 * Processes payment redirect from PayFast and returns status data.
 * Called by order-confirmation.php via include.
 */

require_once dirname(__DIR__, 3) . '/init.php';

if (!$isLoggedIn) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$result = $paymentStatusService->getStatus(
    $_GET['payment_status'] ?? '',
    $_GET['m_payment_id'] ?? '',
    (int)($_GET['order_id'] ?? 0),
    $currentUser->getUserId()
);

// Store error message if payment failed
if (!$result['success'] && !empty($result['message'])) {
    $_SESSION['payment_error'] = $result['message'];
    $_SESSION['payment_error_timestamp'] = time();
} else {
    unset($_SESSION['payment_error']);
    unset($_SESSION['payment_error_timestamp']);
    unset($_SESSION['checkout_data']);
}

$result['baseUrl'] = $baseUrl;
return $result;
