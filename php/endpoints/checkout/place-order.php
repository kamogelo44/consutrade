<?php
/*
 * ConsuTrade - Checkout Endpoint
 * Author: Kamogelo Phale
 * 
 * Creates orders and prepares checkout data for payment.
 * Called when user clicks "Proceed to Checkout" button.
 * 
 * Flow:
 * 1. Verify user is logged in as buyer
 * 2. Get cart items and verify stock
 * 3. Create orders (pending status)
 * 4. Create transaction with placeholder reference
 * 5. Store checkout data in session
 * 6. Redirect to checkout.php
 */

require_once dirname(__DIR__, 3) . '/init.php';

// $baseUrl is already defined in init.php

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (!$isLoggedIn || !$currentUser instanceof Buyer) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Please log in as a buyer']);
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

// Verify stock
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

// Calculate totals
$totals = $cartService->calculateTotals($cartItems);

// Get user info
$userInfo = $userRepo->findCheckoutInfo($userId);

// Generate clean payment ID - NO "PF-PENDING-" prefix
$paymentId = time() . '_' . $userId;

// CREATE ORDER NOW (BEFORE PAYMENT)
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

// Store checkout data in session for the checkout page
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
