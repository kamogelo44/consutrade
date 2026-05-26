<?php
/*
 * ConsuTrade - Logout Handler
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

$auth->logoutUser();
header('Location: ' . getBaseUrl() . 'index.php');
exit;