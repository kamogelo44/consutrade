<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Check if user is already a seller
if ($_SESSION['role'] === 'seller') {
    $_SESSION['flash'] = 'You are already a seller!';
    header('Location: ../seller-dashboard.php');
    exit;
}

// Update user role from buyer to seller
$user_id = $_SESSION['user_id'];
$sql = "UPDATE users SET role = 'seller' WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);

if ($stmt->execute()) {
    $_SESSION['role'] = 'seller';
    $_SESSION['flash'] = 'Congratulations! You are now a seller.';
    header('Location: ../seller-dashboard.php');
} else {
    $_SESSION['flash'] = 'Something went wrong. Please try again.';
    header('Location: ../sell.php');
}
$stmt->close();
$conn->close();
?>