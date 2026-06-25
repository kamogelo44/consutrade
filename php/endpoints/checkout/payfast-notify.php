<?php
/*
 * ConsuTrade - PayFast ITN Handler
 * Author: Kamogelo Phale
 * 
 * PayFast calls this file after someone pays.
 * Updates the order status in our database.
 */

require_once dirname(__DIR__, 2) . '/init.php';

// Force clean output - no HTML, no extra spaces
ob_clean();
header('Content-Type: text/plain');

try {
    $result = $payfastService->handleItn($_POST);

    if ($result['success']) {
        // PayFast expects a 200 OK response with "OK" in the body
        http_response_code(200);
        echo "OK";
        error_log("PayFast ITN: Successfully processed payment for " . ($_POST['m_payment_id'] ?? 'unknown'));
    } else {
        // Log the error but still return 200 to prevent PayFast from retrying
        // If we return a non-200, PayFast will keep retrying the notification
        error_log("PayFast ITN Error: " . ($result['message'] ?? 'Unknown error') .
            " for payment_id: " . ($_POST['m_payment_id'] ?? 'unknown'));
        http_response_code(200);
        echo "OK"; // Always return OK to prevent retries
    }
} catch (Exception $e) {
    // Something crashed - log it but still return OK to PayFast
    error_log("PayFast ITN CRASH: " . $e->getMessage() .
        " for payment_id: " . ($_POST['m_payment_id'] ?? 'unknown'));
    http_response_code(200);
    echo "OK";
}
