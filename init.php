<?php
/*
 * ConsuTrade - Application Initialization
 * Included in every page
 * Author: Kamogelo Phale
 */

// Session settings MUST be set before any session starts
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_domain', '');
    ini_set('session.cookie_lifetime', 0);
    ini_set('session.gc_maxlifetime', 7200);
}

// Load required files
require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/helpers.php';
require_once __DIR__ . '/php/auth.php';

// Auto-detect and start appropriate session
$session_data = initAppSession();

// Make variables available globally
$current_user = $session_data['current_user'];
$is_logged_in = $session_data['is_logged_in'];
$current_user_id = $session_data['current_user_id'];
$baseUrl = getBaseUrl();
?>