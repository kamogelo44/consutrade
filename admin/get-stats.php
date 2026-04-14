<?php
/*
 * ConsuTrade - Get Admin Statistics
 * Author: Kamogelo Phale
 * 
 * Returns JSON data for admin dashboard stats
 * Same pattern as get-cart.php in the main website
 */

session_start();
require_once '../php/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$response = ['success' => true];

// Get total users count
$sql = "SELECT COUNT(*) as count FROM users";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$response['total_users'] = $row['count'];

// Get total products count
$sql = "SELECT COUNT(*) as count FROM products";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$response['total_products'] = $row['count'];

// Get total orders count
$sql = "SELECT COUNT(*) as count FROM orders";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$response['total_orders'] = $row['count'];

// Get pending orders count
$sql = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$response['pending_orders'] = $row['count'];

$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
?>