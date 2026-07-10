<?php
/*
 * ConsuTrade - Checkout Endpoint
 * Author: Kamogelo Phale
 * 
 * Creates orders and prepares checkout data for payment.
 * Called when user clicks "Proceed to Checkout" or "Buy Now".
 */

require_once dirname(__DIR__, 3) . '/init.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (!$isLoggedIn) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Please log in']);
        exit;
    }
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

if (!$currentUser->hasRole('buyer')) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'You need a buyer account']);
        exit;
    }
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$userId = $currentUser->getUserId();
$cartItems = $cartRepo->findByUser($userId);

if (empty($cartItems)) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
        exit;
    }
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

$stockErrors = $cartService->verifyStock($cartItems);
if (!empty($stockErrors)) {
    $_SESSION['checkout_errors'] = $stockErrors;
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Some items are out of stock.']);
        exit;
    }
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

$totals = $cartService->calculateTotals($cartItems);
$userInfo = $userRepo->findCheckoutInfo($userId);
$paymentId = time() . '_' . $userId;

$checkoutResult = $cartService->processCheckout($userId, $cartItems);

if (!$checkoutResult['success']) {
    $_SESSION['checkout_errors'] = $checkoutResult['errors'];
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Checkout failed', 'errors' => $checkoutResult['errors']]);
        exit;
    }
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

$_SESSION['checkout_data'] = [
    'payment_id' => $paymentId,
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

if ($isAjax) {
    echo json_encode(['success' => true]);
    exit;
}

header('Location: ' . $baseUrl . 'checkout.php');
exit;
