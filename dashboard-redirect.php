<?php
/* 
 * Consutrade - Redirect
 * Author: Kamogelo Phale 
 *
 *Redirects based on the role
 *
 */
require_once __DIR__ . '/init.php';

if ($auth->isAdminLoggedIn()) {
    header('Location: admin/admin-dashboard.php');
    exit;
} elseif ($auth->isSellerLoggedIn()) {
    header('Location: admin/seller-dashboard.php');
    exit;
} elseif ($auth->isBuyerLoggedIn()) {
    header('Location: profile.php');
    exit;
} else {
    header('Location: admin/login.php');
}
exit;
