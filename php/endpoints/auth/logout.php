<?php
/*
 * ConsuTrade - Logout Handler
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

$role = $currentUser ? $currentUser->getRole() : null;

$auth->logout();

if ($role === 'admin' || $role === 'seller') {
    header('Location: ' . $baseUrl . 'admin/login.php');
} else {
    header('Location: ' . $baseUrl . 'index.php');
}
exit;
