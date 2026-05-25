<?php
/*
 * ConsuTrade - Place Order / Checkout Handler
 * Author: Kamogelo Phale
 * 
 * Processes checkout: verifies stock, creates orders, clears cart,
 * then redirects to checkout page for PayFast payment.
 */

require_once __DIR__ . '/../init.php';

$baseUrl = getBaseUrl();

if (!$is_logged_in) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$user_id = $current_user_id;

// Get cart items
$cart_items = $cartRepo->getCartItems($user_id);

if (empty($cart_items)) {
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

// Verify stock
$stock_errors = $cartRepo->verifyCartStock($cart_items);

if (!empty($stock_errors)) {
    $_SESSION['checkout_errors'] = $stock_errors;
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

// Process checkout
$checkout_result = $cartRepo->processCheckout($user_id, $cart_items);

if (!$checkout_result['success']) {
    $_SESSION['checkout_errors'] = $checkout_result['errors'];
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

// Get user info for PayFast
$user = $cartRepo->getUserCheckoutInfo($user_id);

// Calculate totals
$totals = $cartRepo->calculateCartTotals($cart_items);

// Store checkout data in session for the checkout page
$_SESSION['checkout_data'] = [
    'payment_id'        => $checkout_result['payment_id'],
    'primary_order_id'  => $checkout_result['primary_order_id'],
    'order_ids'         => $checkout_result['order_ids'],
    'total'             => $totals['total'],
    'subtotal'          => $totals['subtotal'],
    'delivery_fee'      => $totals['delivery_fee'],
    'buyer_name'        => $user['full_name'],
    'buyer_email'       => $user['email'],
    'buyer_phone'       => $user['phone'] ?? '',
    'cart_items'        => $cart_items,
];

// Redirect to checkout page
header('Location: ' . $baseUrl . 'checkout.php');
exit;