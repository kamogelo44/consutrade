<?php
/*
 * ConsuTrade - Seller Logout
 * Author: Kamogelo Phale
 *
 * Destroys seller session and redirects to login page
 */

require_once dirname(__DIR__) . '/../php/helpers.php';

destroySession('seller');

// Redirect to admin login page
header('Location: ../login.php');
exit;
?>