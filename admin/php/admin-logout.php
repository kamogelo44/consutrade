<?php
/*
 * ConsuTrade - Admin Logout
 * Author: Kamogelo Phale
 * 
 * Destroys admin session and redirects to login page
 */

require_once dirname(__DIR__) . '/../init.php';

// Use centralized logout function
logoutUser();

// Redirect to admin login page
header('Location: ../login.php');
exit;
?>