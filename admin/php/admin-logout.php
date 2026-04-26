<?php
/*
 * ConsuTrade - Admin Logout
 * Author: Kamogelo Phale
 *
 * Destroys admin session and redirects to login page
 */

require_once dirname(__DIR__) . '/../php/helpers.php';

destroySession('admin');

// Redirect to admin login page
header('Location: ../login.php');
exit;
?>