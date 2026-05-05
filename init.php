<?php
/*
 * ConsuTrade - Application Initialization
 * Include this at the top of EVERY page
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
    
    // Set consistent session name for main website
    session_name('CONSUTRADE_USER_SESSION');
}

// Load config first
require_once __DIR__ . '/php/config.php';

// Load auth (handles session start)
require_once __DIR__ . '/php/auth.php';

// Load helpers
require_once __DIR__ . '/php/helpers.php';

// Initialize authentication (starts session if needed)
initAuth();

// Make current user available globally
$current_user = getCurrentUser();
$is_logged_in = isLoggedIn();
$current_user_id = getCurrentUserId();

// Update cart count in session if logged in
if ($is_logged_in) {
    updateCartCount();
}
?>