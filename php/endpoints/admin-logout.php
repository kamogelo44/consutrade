<?php
/*
 * ConsuTrade - Admin Logout
 * Author: Kamogelo Phale
 * 
 * Destroys admin session and redirects to login page
 */

require_once dirname(__DIR__, 2) . '/init.php';

// Use Auth class logout method
$auth->logout();

// Redirect to admin login page
header('Location: ' . $baseUrl . 'admin/login.php');
exit;
?>