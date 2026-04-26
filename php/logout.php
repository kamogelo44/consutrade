<?php
/*
 * ConsuTrade - Logout (Main Website)
 * Author: Kamogelo Phale
 *
 * Destroys main website session and redirects to homepage
 */

require_once 'helpers.php';

destroySession('user');

// Redirect to homepage
header('Location: ../index.php');
exit;
?>