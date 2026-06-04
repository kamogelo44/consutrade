<?php
// Initialize cart variables for cart page
$cart_items = [];
$cart_totals = ['subtotal' => 0, 'delivery_fee' => 0, 'total' => 0];
$total_quantity = 0;

if ($isLoggedIn && $currentUser instanceof Buyer) {
    $cart_items = $cartRepo->getCartItems($currentUser->getUserId());
    $cart_totals = $cartRepo->calculateCartTotals($cart_items);
    $total_quantity = $cartRepo->getCartCount($currentUser->getUserId());
}
