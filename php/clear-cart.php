<?php
/*
 * ConsuTrade - Clear Cart API
 * Author: Kamogelo Phale
 * 
 * Wipes the whole cart after someone checks out
 * I call this after the order saves to the database
 */

session_start();

unset($_SESSION['cart']);

echo json_encode([
    'success' => true,
    'message' => 'Cart cleared'
]);
?>