<?php
/*
 * ConsuTrade - Logout
 * Author: Kamogelo Phale
 *
 * Destroys session and redirects to homepage
 */

session_start();

// Destroy all session data
$_SESSION = array();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to homepage
header('Location: ../index.php');
exit;
?>