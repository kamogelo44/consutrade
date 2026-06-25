<?php
/*
 * ConsuTrade - Checkout Endpoint
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

$baseUrl = getBaseUrl();

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

// Use CartService for stock verification
$stockErrors = $cartService->verifyStock($cartItems);

if (!empty($stockErrors)) {
    $_SESSION['checkout_errors'] = $stockErrors;
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Some items are out of stock. Please update your cart.']);
        exit;
    }
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

// Use CartService for checkout processing
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

// Use UserRepository for user data (CRUD - READ)
$userInfo = $userRepo->findCheckoutInfo($userId);

// Use CartService for totals calculation
$totals = $cartService->calculateTotals($cartItems);

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

if ($isAjax) {
    echo json_encode(['success' => true]);
    exit;
}

header('Location: ' . $baseUrl . 'checkout.php');
exit;
