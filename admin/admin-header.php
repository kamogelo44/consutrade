<?php
/*
 * ConsuTrade - Admin Dashboard Header Component
 * Author: Kamogelo Phale
 * 
 * This file contains the header HTML and navigation for admin pages only
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

// Set active state for sidebar navigation
$active_dashboard = '';
$active_users = '';
$active_products = '';
$active_orders = '';
$active_profile = '';

switch ($current_page) {
    case 'admin-dashboard.php':
        $active_dashboard = 'active';
        break;
    case 'users.php':
        $active_users = 'active';
        break;
    case 'all-products.php':
        $active_products = 'active';
        break;
    case 'all-orders.php':
        $active_orders = 'active';
        break;
    case 'admin-profile.php':
        $active_profile = 'active';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/admin-header.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/admin-dashboard.css">
</head>
<body>

<!-- Admin Dashboard Header -->
<header class="admin-header">
    <!-- Left: Hamburger -->
    <button class="hamburger" id="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- Center: Logo (hidden on mobile, visible on desktop) -->
    <div class="logo desktop-logo">
        <a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php">ConsuTrade<span>Admin</span></a>
    </div>

    <!-- Right: User Dropdown -->
    <div class="user-dropdown">
        <div class="user-info" id="adminUserInfo">
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" class="profile-icon" width="32px" height="32px" alt="Profile">
            <button class="dropdown-toggle" id="adminDropdownToggle">
                <img src="<?php echo $baseUrl; ?>images/icons/chevron-down-svgrepo-com.svg" class="icon-white" width="16px" height="16px" alt="Menu">
            </button>
        </div>
        <ul class="dropdown-menu" id="adminDropdownMenu">
            <li><a href="<?php echo $baseUrl; ?>admin/admin-profile.php">My Profile</a></li>
            <li><a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php">Dashboard</a></li>
            <li class="dropdown-divider"></li>
            <li><a href="<?php echo $baseUrl; ?>php/logout.php">Logout</a></li>
        </ul>
    </div>
</header>

<!-- Mobile Side Menu (sliding panel) -->
<div class="mobile-side-menu" id="mobileSideMenu">
    <button class="side-menu-hamburger" id="sideMenuHamburger">
        <span></span>
        <span></span>
        <span></span>
    </button>
    
    <div class="mobile-menu-header">
        <div class="mobile-menu-logo">
            <a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php">ConsuTrade<span>Admin</span></a>
        </div>
    </div>
    
    <div class="mobile-profile-section">
        <div class="mobile-profile-info">
            <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" class="mobile-profile-avatar" width="40px" height="40px" alt="Profile">
            <div class="mobile-profile-text">
                <span class="mobile-profile-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <span class="mobile-profile-role">Administrator</span>
            </div>
        </div>
    </div>
    
    <ul class="mobile-nav-links">
        <li><a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php" class="<?php echo $active_dashboard; ?>">Dashboard</a></li>
        <li><a href="<?php echo $baseUrl; ?>admin/users.php" class="<?php echo $active_users; ?>">Manage Users</a></li>
        <li><a href="<?php echo $baseUrl; ?>admin/all-products.php" class="<?php echo $active_products; ?>">All Products</a></li>
        <li><a href="<?php echo $baseUrl; ?>admin/all-orders.php" class="<?php echo $active_orders; ?>">All Orders</a></li>
        <li class="mobile-menu-divider"></li>
        <li><a href="<?php echo $baseUrl; ?>admin/admin-profile.php" class="<?php echo $active_profile; ?>">My Profile</a></li>
        <li class="mobile-menu-divider"></li>
        <li><a href="<?php echo $baseUrl; ?>php/logout.php" class="mobile-logout-link">Logout</a></li>
    </ul>
</div>

<!-- Overlay -->
<div class="menu-overlay" id="menuOverlay"></div>

<!-- Admin Sidebar Navigation (Desktop only) -->
<nav class="admin-sidebar">
    <ul class="admin-nav-links">
        <li>
            <a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php" class="<?php echo $active_dashboard; ?>">
                <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="20px" height="20px" alt="Dashboard">
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $baseUrl; ?>admin/users.php" class="<?php echo $active_users; ?>">
                <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" width="20px" height="20px" alt="Users">
                <span>Manage Users</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $baseUrl; ?>admin/all-products.php" class="<?php echo $active_products; ?>">
                <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="20px" height="20px" alt="Products">
                <span>All Products</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $baseUrl; ?>admin/all-orders.php" class="<?php echo $active_orders; ?>">
                <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="20px" height="20px" alt="Orders">
                <span>All Orders</span>
            </a>
        </li>
    </ul>
</nav>

<!-- Main Content Wrapper -->
<main class="admin-main-content">