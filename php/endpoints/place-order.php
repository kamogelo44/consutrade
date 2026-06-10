<?php
require_once dirname(__DIR__, 2) . '/init.php';

$baseUrl = getBaseUrl();

// Check if this is an AJAX request
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
$cartItems = $cartRepo->getCartItems($userId);

if (empty($cartItems)) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
        exit;
    }
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

$stockErrors = $cartRepo->verifyCartStock($cartItems);

if (!empty($stockErrors)) {
    $_SESSION['checkout_errors'] = $stockErrors;
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Stock issues with some items', 'errors' => $stockErrors]);
        exit;
    }
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

$checkoutResult = $cartRepo->processCheckout($userId, $cartItems);

if (!$checkoutResult['success']) {
    $_SESSION['checkout_errors'] = $checkoutResult['errors'];
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Checkout failed', 'errors' => $checkoutResult['errors']]);
        exit;
    }
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

if ($isAjax) {
    echo json_encode(['success' => true]);
    exit;
}

header('Location: ' . $baseUrl . 'checkout.php');
exit;
