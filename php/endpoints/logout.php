<?php
/*
 * ConsuTrade - Logout Handler
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

// Use Auth class logout method
$auth->logout();

header('Location: ' . getBaseUrl() . 'index.php');
exit;
?>