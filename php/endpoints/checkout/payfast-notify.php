<?php
/*
 * ConsuTrade - PayFast ITN Handler
 * PayFast calls this file after someone pays.
 * Server-to-server notification - no session, no output.
 */

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

    $payfastService->handleItn($_POST);
} catch (Exception $e) {
}

http_response_code(200);
die("OK");
