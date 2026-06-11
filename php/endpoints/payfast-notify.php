<?php
/*
 * ConsuTrade - PayFast ITN Handler
 * Author: Kamogelo Phale
 * 
 * PayFast calls this file after someone pays.
 * Updates the order status in our database.
 */

require_once dirname(__DIR__, 2) . '/init.php';

try {
    // Use the payfast service that was already set up in init.php
    $result = $payfastService->handleItn($_POST);

    if ($result['success']) {
        http_response_code(200);
        echo "OK";
    } else {
        // Log error but still tell PayFast everything is fine
        // If we don't send 200, PayFast will keep trying to send the notification
        error_log("PayFast ITN error: " . ($result['message'] ?? 'Unknown'));
        http_response_code(200);
        echo "OK";
    }
} catch (Exception $e) {
    // Something crashed - log it but still return OK to PayFast
    error_log("PayFast ITN crashed: " . $e->getMessage());
    http_response_code(200);
    echo "OK";
}
