<?php
/*
 * ConsuTrade - Logout Handler
 * Author: Kamogelo Phale
 * 
 * Destroys session and redirects based on user role
 */

require_once dirname(__DIR__, 2) . '/init.php';

// Store role before logout
$role = $current_user['role'] ?? '';

// Use Auth class logout method
$auth->logout();

// Redirect based on previous role
if ($role === 'admin' || $role === 'seller') {
    header('Location: ' . $baseUrl . 'admin/login.php');
} else {
    header('Location: ' . $baseUrl . 'index.php');
}
exit;
?>