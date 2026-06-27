<?php
/*
 * ConsuTrade - Get Payment Status Endpoint
 * Processes payment redirect from PayFast and returns status data.
 * 
 * Called by order-confirmation.php via include.
 * Also can be called directly for AJAX status checks.
 */

require_once dirname(__DIR__, 3) . '/init.php';

// When called via include, return array. When called directly, output JSON.
$isDirectAccess = basename($_SERVER['SCRIPT_FILENAME']) === 'get-payment-status.php';

if (!$isLoggedIn) {
    $result = [
        'success' => false,
        'message' => 'Please log in',
        'redirect' => false,
        'order' => null,
        'transaction' => null
    ];

    if ($isDirectAccess) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    return $result;
}

$paymentStatus = $_GET['payment_status'] ?? '';
$paymentId = $_GET['m_payment_id'] ?? '';
$orderId = (int)($_GET['order_id'] ?? 0);

try {
    // Use PaymentStatusService for all logic
    $result = $paymentStatusService->getStatus(
        $paymentStatus,
        $paymentId,
        $orderId,
        $currentUser->getUserId()
    );

    // Ensure all array keys exist
    $result = array_merge([
        'success' => false,
        'message' => '',
        'order_id' => 0,
        'order' => null,
        'transaction' => null,
        'redirect' => false,
        'redirect_url' => $baseUrl . 'cart.php'
    ], $result);

    // On success, clean up session
    if ($result['success']) {
        unset($_SESSION['checkout_data']);
        unset($_SESSION['payment_error']);
        $_SESSION['cart_count'] = 0;
    } else {
        $_SESSION['payment_error'] = $result['message'] ?: 'Unknown payment status';
    }

    if ($isDirectAccess) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    return $result;
} catch (Exception $e) {
    error_log("Payment Status Error: " . $e->getMessage());

    $errorResult = [
        'success' => false,
        'message' => 'An error occurred while checking your payment. Please check your orders page.',
        'redirect' => false,
        'order' => null,
        'transaction' => null
    ];

    if ($isDirectAccess) {
        header('Content-Type: application/json');
        echo json_encode($errorResult);
        exit;
    }

    return $errorResult;
}
