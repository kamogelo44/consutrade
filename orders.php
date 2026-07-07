<?php
/*
 * ConsuTrade - Orders Router
 * Routes to the appropriate orders view based on user roles
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
include __DIR__ . '/includes/functions.php';

checkMaintenanceMode();

if (!$isLoggedIn) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$hasBuyerRole = $currentUser->hasRole('buyer');
$hasSellerRole = $currentUser->hasRole('seller');

// Neither role — redirect
if (!$hasBuyerRole && !$hasSellerRole) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Both roles — let user choose, default to buyer
if ($hasBuyerRole && $hasSellerRole) {
    $tab = $_GET['tab'] ?? $_SESSION['active_order_tab'] ?? 'buyer';
    $_SESSION['active_order_tab'] = $tab;

    if ($tab === 'seller') {
        require_once __DIR__ . '/includes/orders-seller.php';
    } else {
        require_once __DIR__ . '/includes/orders-buyer.php';
    }
    exit;
}

// Single role — direct to the right view
if ($hasBuyerRole) {
    require_once __DIR__ . '/includes/orders-buyer.php';
    exit;
}

if ($hasSellerRole) {
    require_once __DIR__ . '/includes/orders-seller.php';
    exit;
}
