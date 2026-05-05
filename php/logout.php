<?php
/*
 * ConsuTrade - Logout Handler
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/../init.php';

logoutUser();
header('Location: ../index.php');
exit;
?>