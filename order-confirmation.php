<?php
/*
 * ConsuTrade - Order Confirmation Page
 * Author: Kamogelo Phale
 * 
 * This page shows after successful payment
 */

session_start();

$baseUrl = "/www/consutrade/";

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - ConsuTrade</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main style="max-width: 600px; margin: 60px auto; padding: 0 20px; text-align: center;">
        <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <div style="font-size: 64px; margin-bottom: 20px;">✅</div>
            <h1 style="font-size: 28px; font-weight: bold; margin-bottom: 10px;">Order Confirmed!</h1>
            <p style="color: #666; margin-bottom: 30px;">Thank you for your purchase. Your order has been received.</p>
            
            <p style="margin-bottom: 20px;">You will receive an email confirmation shortly.</p>
            
            <div style="display: flex; gap: 15px; justify-content: center;">
                <a href="my-orders.php" style="background-color: #FF6B00; color: white; padding: 10px 24px; border-radius: 8px; text-decoration: none;">View My Orders</a>
                <a href="product-listings.php" style="background-color: #f5f5f5; color: #333; padding: 10px 24px; border-radius: 8px; text-decoration: none;">Continue Shopping</a>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>