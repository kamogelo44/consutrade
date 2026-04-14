<?php
/*
 * ConsuTrade - Admin Dashboard Header Component
 * Author: Kamogelo Phale
 * 
 * This file contains the header HTML and navigation for admin pages only
 * Different from main site header - no search, no cart, admin-specific logo
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: /www/consutrade/index.php');
    exit();
}

$baseUrl = "/www/consutrade/";

// Determine current page for active links
$current_page = basename($_SERVER['REQUEST_URI']);
$current_page = strtok($current_page, '?');
$current_dashboard_page = '';

if ($current_page === 'admin-dashboard.php') {
    $current_dashboard_page = 'Admin Dashboard';
} elseif ($current_page === 'users.php') {
    $current_dashboard_page = 'Manage Users';
} elseif ($current_page === 'all-products.php') {
    $current_dashboard_page = 'All Products';
} elseif ($current_page === 'all-orders.php') {
    $current_dashboard_page = 'All Orders';
}

$user_role = $_SESSION['role'];
$is_admin = ($user_role === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/styles.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/admin-header.css">
</head>
<body>

<!-- Admin Dashboard Header -->
<header class="admin-header">
    <div class="admin-header-container">
        <!-- Left: Admin Logo -->
        <div class="admin-logo">
            <a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php">
                Consu<span>Trade</span><span class="admin-badge">Admin</span>
            </a>
        </div>

        <!-- Right: User Profile with Dropdown -->
        <div class="admin-user-section">
            <div class="admin-user-info" id="adminUserInfo">
                <span class="admin-welcome">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" class="admin-profile-icon" width="36px" height="36px" alt="Profile">
                <button class="admin-dropdown-toggle" id="adminDropdownToggle">
                    <img src="<?php echo $baseUrl; ?>images/icons/chevron-down-svgrepo-com.svg" class="icon-white" width="16px" height="16px" alt="Menu">
                </button>
            </div>
            <ul class="admin-dropdown-menu" id="adminDropdownMenu">
                <li><a href="<?php echo $baseUrl; ?>profile.php">My Profile</a></li>
                <li><a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php">Dashboard</a></li>
                <li class="dropdown-divider"></li>
                <li><a href="<?php echo $baseUrl; ?>php/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</header>

<!-- Admin Navigation Sidebar (for larger screens) -->
<nav class="admin-sidebar">
    <ul class="admin-nav-links">
        <li>
            <a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php" class="<?php echo ($current_dashboard_page === 'Admin Dashboard') ? 'active' : ''; ?>">
                <img src="<?php echo $baseUrl; ?>images/icons/dashboard-svgrepo-com.svg" width="20px" height="20px" alt="Dashboard">
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $baseUrl; ?>admin/users.php" class="<?php echo ($current_dashboard_page === 'Manage Users') ? 'active' : ''; ?>">
                <img src="<?php echo $baseUrl; ?>images/icons/users-svgrepo-com.svg" width="20px" height="20px" alt="Users">
                <span>Manage Users</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $baseUrl; ?>admin/all-products.php" class="<?php echo ($current_dashboard_page === 'All Products') ? 'active' : ''; ?>">
                <img src="<?php echo $baseUrl; ?>images/icons/products-svgrepo-com.svg" width="20px" height="20px" alt="Products">
                <span>All Products</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $baseUrl; ?>admin/all-orders.php" class="<?php echo ($current_dashboard_page === 'All Orders') ? 'active' : ''; ?>">
                <img src="<?php echo $baseUrl; ?>images/icons/orders-svgrepo-com.svg" width="20px" height="20px" alt="Orders">
                <span>All Orders</span>
            </a>
        </li>
    </ul>
</nav>

<!-- Main Content Wrapper -->
<main class="admin-main-content">