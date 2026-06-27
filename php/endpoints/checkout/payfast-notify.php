<?php
/*
 * ConsuTrade - PayFast ITN Handler
 * PayFast calls this file after someone pays.
 * 
 * This is a server-to-server notification (ITN).
 * PayFast expects a 200 OK response regardless of success/failure.
 * 
 * IMPORTANT: This file should NOT start a session or output anything.
 * All processing is done via PayFastService.
 */

// Critical: No session, no output buffering, clean response
while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: text/plain');

require_once dirname(__DIR__, 3) . '/init.php';

try {
    if (empty($_POST)) {
        http_response_code(200);
        die("OK");
    }

    // Delegate everything to PayFastService
    $result = $payfastService->handleItn($_POST);

    if (!$result['success']) {
        error_log("PayFast ITN: FAILED - " . ($result['message'] ?? 'Unknown error'));
    }
} catch (Exception $e) {
    error_log("PayFast ITN CRASH: " . $e->getMessage());
}

// Always return 200 OK to prevent PayFast retries
http_response_code(200);
die("OK");
