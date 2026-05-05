<?php
/*
 * ConsuTrade - Seller Logout
 * Author: Kamogelo Phale
 * 
 * Destroys seller session and redirects to login page
 */

require_once dirname(__DIR__) . '/../init.php';

// Use centralized logout function
logoutUser();

// Redirect to seller login page
header('Location: ../login.php');
exit;
?>