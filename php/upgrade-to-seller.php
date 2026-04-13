<?php
/*
 * ConsuTrade - Upgrade to Seller
 * Author: Kamogelo Phale
 * 
 * This file upgrades a buyer account to seller
 */

session_start();
require_once 'config.php';

$baseUrl = "/www/consutrade/";

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

if ($_SESSION['role'] === 'seller') {
    $_SESSION['flash'] = 'You are already a seller!';
    header('Location: ' . $baseUrl . 'admin/seller-dashboard.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$sql = "UPDATE users SET role = 'seller' WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);

if ($stmt->execute()) {
    $_SESSION['role'] = 'seller';
    $_SESSION['flash'] = 'Congratulations! You are now a seller.';
    header('Location: ' . $baseUrl . 'admin/seller-dashboard.php');
} else {
    $_SESSION['flash'] = 'Something went wrong. Please try again.';
    header('Location: ' . $baseUrl . 'sell.php');
}
$stmt->close();
$conn->close();
?>