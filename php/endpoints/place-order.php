<?php
/*
 * ConsuTrade - Place Order / Checkout Handler
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/../init.php';

$baseUrl = getBaseUrl();

if (!$isLoggedIn || !$currentUser instanceof Buyer) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$userId = $currentUser->getUserId();
$cartItems = $cartRepo->getCartItems($userId);

if (empty($cartItems)) {
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

$stockErrors = $cartRepo->verifyCartStock($cartItems);

if (!empty($stockErrors)) {
    $_SESSION['checkout_errors'] = $stockErrors;
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

$checkoutResult = $cartRepo->processCheckout($userId, $cartItems);

if (!$checkoutResult['success']) {
    $_SESSION['checkout_errors'] = $checkoutResult['errors'];
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

$userInfo = $cartRepo->getUserCheckoutInfo($userId);
$totals = $cartRepo->calculateCartTotals($cartItems);

$_SESSION['checkout_data'] = [
    'payment_id' => $checkoutResult['payment_id'],
    'primary_order_id' => $checkoutResult['primary_order_id'],
    'order_ids' => $checkoutResult['order_ids'],
    'total' => $totals['total'],
    'subtotal' => $totals['subtotal'],
    'delivery_fee' => $totals['delivery_fee'],
    'buyer_name' => $userInfo['full_name'],
    'buyer_email' => $userInfo['email'],
    'buyer_phone' => $userInfo['phone'] ?? '',
    'cart_items' => $cartItems,
];

header('Location: ' . $baseUrl . 'checkout.php');
exit;
