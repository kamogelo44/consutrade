<?php
/*
 * ConsuTrade - Unified User Profile
 * Author: Kamogelo Phale
 * 
 * Single profile page that adapts to user's roles
 * - Buyers see order history, reviews
 * - Sellers see products, sales stats
 * - Admins see system stats
 * - Users with multiple roles see tabs to switch between views
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';

$breadcrumbItems = [
    ['label' => 'My Profile']
];

// Check if user is logged in
if (!$isLoggedIn) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$user_id = $currentUser->getUserId();
$full_name = $currentUser->getFullName();
$email = $currentUser->getEmail();
$phone = $currentUser->getPhone();
$location = $currentUser->getLocation();
$created_at = $currentUser->getCreatedAt();
$profile_image = $currentUser->getProfileImageUrl();

// Determine which roles the user has
$hasBuyerRole = $currentUser->hasRole('buyer');
$hasSellerRole = $currentUser->hasRole('seller');
$hasAdminRole = $currentUser->hasRole('admin');

// Get the active role for default tab
$activeRole = $auth->getActiveRole();

// Count how many roles the user has
$roleCount = 0;
if ($hasBuyerRole) $roleCount++;
if ($hasSellerRole) $roleCount++;
if ($hasAdminRole) $roleCount++;

// If user only has one role, use that as default tab
if ($roleCount === 1) {
    if ($hasBuyerRole) $defaultTab = 'buyer';
    elseif ($hasSellerRole) $defaultTab = 'seller';
    elseif ($hasAdminRole) $defaultTab = 'admin';
} else {
    // Use active role as default tab
    $defaultTab = $activeRole ?? 'buyer';
}

$defaultTab = $defaultTab ?? 'buyer';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <style>
        /* ========== PROFILE PAGE STYLES ========== */
        .profile-container {
            width: 100%;
            padding: var(--spacing-xl);
            min-height: calc(100vh - 200px);
        }

        /* User Header */
        .profile-user-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-xl);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: var(--radius-lg);
            padding: var(--spacing-2xl) var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
            color: var(--white);
        }

        .profile-user-avatar {
            position: relative;
            width: 120px;
            height: 120px;
            flex-shrink: 0;
        }

        .profile-user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--white);
            box-shadow: var(--shadow-md);
        }

        .avatar-upload-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: var(--primary-color);
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-fast);
            border: 2px solid var(--white);
        }

        .avatar-upload-btn:hover {
            transform: scale(1.1);
            background: var(--primary-dark);
        }

        .avatar-upload-btn img {
            width: 16px;
            height: 16px;
            filter: brightness(0) invert(1);
            margin: 0;
            border: none;
        }

        .profile-user-info {
            flex: 1;
        }

        .profile-user-info h1 {
            font-size: var(--font-3xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-sm);
            color: var(--white);
        }

        .profile-user-meta {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            flex-wrap: wrap;
            margin-top: var(--spacing-sm);
        }

        .role-badge {
            padding: 4px 12px;
            border-radius: var(--radius-round);
            font-size: var(--font-sm);
            font-weight: var(--font-medium);
        }

        .role-badge-buyer {
            background: rgba(76, 175, 80, 0.9);
            color: var(--white);
        }

        .role-badge-seller {
            background: rgba(255, 107, 0, 0.9);
            color: var(--white);
        }

        .role-badge-admin {
            background: rgba(156, 39, 176, 0.9);
            color: var(--white);
        }

        .member-since {
            background: rgba(0, 0, 0, 0.2);
            padding: 4px 12px;
            border-radius: var(--radius-round);
            font-size: var(--font-sm);
        }

        /* Role Tabs */
        .profile-tabs {
            display: flex;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-xl);
            border-bottom: 2px solid var(--border-light);
            padding-bottom: var(--spacing-sm);
            flex-wrap: wrap;
        }

        .profile-tab {
            padding: 10px 24px;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: var(--font-medium);
            color: var(--gray-medium);
            border-radius: var(--radius-md) var(--radius-md) 0 0;
            transition: all var(--transition-fast);
            font-size: var(--font-md);
        }

        .profile-tab:hover {
            color: var(--dark-bg);
            background: var(--gray-bg-light);
        }

        .profile-tab.active {
            color: var(--primary-color);
            background: var(--primary-fade);
            border-bottom: 3px solid var(--primary-color);
        }

        .profile-tab .tab-badge {
            display: inline-block;
            background: var(--gray-bg);
            color: var(--gray-dark);
            border-radius: var(--radius-round);
            padding: 1px 8px;
            font-size: var(--font-xs);
            margin-left: 6px;
        }

        .profile-tab.active .tab-badge {
            background: var(--primary-color);
            color: var(--white);
        }

        /* Tab Content */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Messages */
        .success-message,
        .error-message {
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            text-align: center;
            display: none;
        }

        .success-message {
            background: var(--success-light);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .error-message {
            background: var(--error-light);
            color: var(--error);
            border-left: 4px solid var(--error);
        }

        /* Profile Content - Two Columns */
        .profile-content {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
        }

        /* Cards */
        .profile-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
        }

        .profile-card h3 {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-lg);
            color: var(--dark-bg);
            padding-bottom: var(--spacing-sm);
            border-bottom: 2px solid var(--primary-color);
        }

        /* Stats List */
        .stats-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid var(--border-light);
        }

        .stat-label {
            font-weight: var(--font-medium);
            color: var(--gray-dark);
        }

        .stat-value {
            color: var(--gray-medium);
        }

        .stat-value.highlight {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            color: var(--primary-color);
        }

        .stat-divider {
            height: 1px;
            background: var(--border-light);
            margin: var(--spacing-sm) 0;
        }

        /* Edit Form */
        .profile-edit-form {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xs);
        }

        .form-group label {
            font-weight: var(--font-medium);
            color: var(--gray-dark);
            font-size: var(--font-sm);
        }

        .form-group input,
        .form-group select {
            padding: 10px 12px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: var(--font-md);
            transition: all var(--transition-fast);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 0, 0.1);
        }

        .form-group input:disabled {
            background: var(--gray-bg);
            color: var(--gray-light);
            cursor: not-allowed;
        }

        .form-group small {
            color: var(--gray-light);
            font-size: var(--font-xs);
        }

        .form-actions {
            display: flex;
            gap: var(--spacing-md);
            margin-top: var(--spacing-md);
            flex-wrap: wrap;
        }

        .save-btn {
            flex: 1;
            padding: 12px;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-weight: var(--font-bold);
            cursor: pointer;
            transition: all var(--transition-fast);
            min-width: 120px;
        }

        .save-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .change-password-btn {
            flex: 1;
            padding: 12px;
            background: var(--white);
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            border-radius: var(--radius-md);
            font-weight: var(--font-bold);
            text-align: center;
            text-decoration: none;
            transition: all var(--transition-fast);
            display: inline-block;
            min-width: 120px;
        }

        .change-password-btn:hover {
            background: var(--primary-fade);
            transform: translateY(-2px);
        }

        /* Quick Actions Grid */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: var(--spacing-md);
        }

        .quick-action-card {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-md);
            background: var(--gray-bg-light);
            border-radius: var(--radius-md);
            text-decoration: none;
            transition: all var(--transition-fast);
            border: 1px solid transparent;
        }

        .quick-action-card:hover {
            background: var(--primary-fade);
            border-color: var(--primary-light);
            transform: translateX(4px);
        }

        .quick-action-icon {
            width: 40px;
            height: 40px;
            background: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quick-action-icon img {
            filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(1577%) hue-rotate(350deg) brightness(102%) contrast(101%);
            width: 20px;
            height: 20px;
        }

        .quick-action-info h4 {
            font-size: var(--font-sm);
            font-weight: var(--font-semibold);
            color: var(--dark-bg);
            margin: 0;
        }

        .quick-action-info p {
            font-size: var(--font-xs);
            color: var(--gray-medium);
            margin: 0;
        }

        /* Danger Zone */
        .danger-zone {
            margin-top: var(--spacing-xl);
        }

        .danger-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--error-light);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            border: 1px solid var(--error);
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }

        .danger-info h4 {
            color: var(--error);
            font-size: var(--font-lg);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-xs);
        }

        .danger-info p {
            color: var(--gray-dark);
            font-size: var(--font-sm);
        }

        .delete-account-btn {
            padding: 10px 24px;
            background: var(--error);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-weight: var(--font-bold);
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .delete-account-btn:hover {
            background: var(--error-dark);
            transform: translateY(-2px);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: var(--z-modal);
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--radius-lg);
            max-width: 500px;
            width: 90%;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-md) var(--spacing-lg);
            border-bottom: 1px solid var(--border-light);
        }

        .modal-header h1 {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            margin: 0;
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--gray-light);
            line-height: 1;
            padding: 0;
            width: 30px;
            height: 30px;
        }

        .btn-close:hover {
            color: var(--error);
        }

        .password-field-wrapper {
            position: relative;
        }

        .password-field-wrapper input {
            width: 100%;
            padding-right: 40px;
        }

        .password-toggle-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
        }

        .warning-text {
            color: var(--error);
            font-weight: var(--font-medium);
            margin-top: var(--spacing-sm);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .profile-content {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg);
            }
        }

        @media (max-width: 768px) {
            .profile-container {
                padding: var(--spacing-lg);
                margin-top: 60px;
            }

            .profile-user-header {
                flex-direction: column;
                text-align: center;
                padding: var(--spacing-xl) var(--spacing-lg);
            }

            .profile-user-meta {
                justify-content: center;
            }

            .profile-tabs {
                justify-content: center;
            }

            .danger-card {
                flex-direction: column;
                text-align: center;
            }

            .form-actions {
                flex-direction: column;
            }

            .save-btn,
            .change-password-btn {
                width: 100%;
            }

            .quick-actions-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .profile-container {
                padding: var(--spacing-md);
            }

            .profile-user-avatar {
                width: 100px;
                height: 100px;
            }

            .profile-user-info h1 {
                font-size: var(--font-2xl);
            }

            .stat-value.highlight {
                font-size: var(--font-lg);
            }

            .profile-tabs {
                gap: var(--spacing-xs);
            }

            .profile-tab {
                padding: 8px 14px;
                font-size: var(--font-sm);
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main class="profile-container">
        <?php include 'includes/breadcrumb.php'; ?>
        <?php include 'includes/flash-message.php'; ?>

        <!-- User Profile Header -->
        <div class="profile-user-header">
            <div class="profile-user-avatar">
                <img src="<?php echo $profile_image; ?>" alt="Profile Avatar" id="profile-avatar">
                <label for="profile-image-upload" class="avatar-upload-btn" title="Change profile picture">
                    <img src="<?php echo $baseUrl; ?>images/icons/camera-svgrepo-com.svg" alt="Upload">
                </label>
            </div>
            <div class="profile-user-info">
                <h1><?php echo htmlspecialchars($full_name); ?></h1>
                <div class="profile-user-meta">
                    <?php if ($hasBuyerRole): ?>
                        <span class="role-badge role-badge-buyer">Buyer</span>
                    <?php endif; ?>
                    <?php if ($hasSellerRole): ?>
                        <span class="role-badge role-badge-seller">Seller</span>
                    <?php endif; ?>
                    <?php if ($hasAdminRole): ?>
                        <span class="role-badge role-badge-admin">Admin</span>
                    <?php endif; ?>
                    <span class="member-since">Member since <?php echo date('d M Y', strtotime($created_at)); ?></span>
                </div>
            </div>
        </div>

        <!-- Hidden file input for profile image -->
        <form id="profile-image-form" style="display: none;">
            <input type="file" name="profile_image" id="profile-image-upload" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
        </form>

        <!-- Role Tabs (only show if user has multiple roles) -->
        <?php if ($roleCount > 1): ?>
            <div class="profile-tabs">
                <?php if ($hasBuyerRole): ?>
                    <button class="profile-tab <?php echo $defaultTab === 'buyer' ? 'active' : ''; ?>" data-tab="buyer">
                        <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="16" height="16" style="vertical-align:middle;margin-right:4px;filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(1577%) hue-rotate(350deg) brightness(102%) contrast(101%);">
                        Buyer
                        <span class="tab-badge">Orders</span>
                    </button>
                <?php endif; ?>
                <?php if ($hasSellerRole): ?>
                    <button class="profile-tab <?php echo $defaultTab === 'seller' ? 'active' : ''; ?>" data-tab="seller">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="16" height="16" style="vertical-align:middle;margin-right:4px;filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(1577%) hue-rotate(350deg) brightness(102%) contrast(101%);">
                        Seller
                        <span class="tab-badge">Store</span>
                    </button>
                <?php endif; ?>
                <?php if ($hasAdminRole): ?>
                    <button class="profile-tab <?php echo $defaultTab === 'admin' ? 'active' : ''; ?>" data-tab="admin">
                        <img src="<?php echo $baseUrl; ?>images/icons/dashboard-svgrepo-com.svg" width="16" height="16" style="vertical-align:middle;margin-right:4px;filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(1577%) hue-rotate(350deg) brightness(102%) contrast(101%);">
                        Admin
                        <span class="tab-badge">System</span>
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ============================================================
        TAB: BUYER CONTENT
        ============================================================ -->
        <div id="tab-buyer" class="tab-content <?php echo $defaultTab === 'buyer' ? 'active' : ''; ?>">

            <!-- Stats Card -->
            <div class="profile-content">
                <div class="profile-card">
                    <h3>Buyer Statistics</h3>
                    <div class="stats-list">
                        <div class="stat-row">
                            <span class="stat-label">Email Address</span>
                            <span class="stat-value"><?php echo htmlspecialchars($email); ?></span>
                        </div>
                        <?php if (!empty($location)): ?>
                            <div class="stat-row">
                                <span class="stat-label">Location</span>
                                <span class="stat-value"><?php echo htmlspecialchars($location); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($phone)): ?>
                            <div class="stat-row">
                                <span class="stat-label">Phone Number</span>
                                <span class="stat-value"><?php echo htmlspecialchars($phone); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="stat-divider"></div>
                        <div class="stat-row">
                            <span class="stat-label">Orders Placed</span>
                            <span class="stat-value highlight" id="stat-orders">-</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Total Spent</span>
                            <span class="stat-value highlight" id="stat-spent">-</span>
                        </div>
                        <div class="stat-row" id="pending-row" style="display: none;">
                            <span class="stat-label">Pending Orders</span>
                            <span class="stat-value highlight" style="color: var(--warning);" id="stat-pending">-</span>
                        </div>
                        <div class="stat-row" id="reviews-row" style="display: none;">
                            <span class="stat-label">Reviews Written</span>
                            <span class="stat-value" id="stat-reviews">-</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions for Buyers -->
                <div class="profile-card">
                    <h3>Quick Actions</h3>
                    <div class="quick-actions-grid">
                        <a href="<?php echo $baseUrl; ?>my-orders.php" class="quick-action-card">
                            <div class="quick-action-icon">
                                <img src="<?php echo $baseUrl; ?>images/icons/document-svgrepo-com.svg" alt="Orders">
                            </div>
                            <div class="quick-action-info">
                                <h4>My Orders</h4>
                                <p>View order history</p>
                            </div>
                        </a>
                        <a href="<?php echo $baseUrl; ?>cart.php" class="quick-action-card">
                            <div class="quick-action-icon">
                                <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Cart">
                            </div>
                            <div class="quick-action-info">
                                <h4>My Cart</h4>
                                <p>View items in cart</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================
        TAB: SELLER CONTENT
        ============================================================ -->
        <div id="tab-seller" class="tab-content <?php echo $defaultTab === 'seller' ? 'active' : ''; ?>">

            <!-- Top Row: Stats and Quick Actions -->
            <div class="profile-content">
                <div class="profile-card">
                    <h3>Seller Statistics</h3>
                    <div class="stats-list">
                        <div class="stat-row">
                            <span class="stat-label">Products Listed</span>
                            <span class="stat-value highlight" id="stat-products">-</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Completed Sales</span>
                            <span class="stat-value highlight" id="stat-sales">-</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Total Revenue</span>
                            <span class="stat-value highlight" id="stat-revenue">-</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Seller Rating</span>
                            <span class="stat-value highlight" id="stat-rating">-</span>
                        </div>
                    </div>
                </div>

                <div class="profile-card">
                    <h3>Quick Actions</h3>
                    <div class="quick-actions-grid">
                        <a href="<?php echo $baseUrl; ?>admin/add-product.php" class="quick-action-card">
                            <div class="quick-action-icon">
                                <img src="<?php echo $baseUrl; ?>images/icons/add-svgrepo-com.svg" alt="Add Product">
                            </div>
                            <div class="quick-action-info">
                                <h4>Add Product</h4>
                                <p>List a new product</p>
                            </div>
                        </a>
                        <a href="<?php echo $baseUrl; ?>admin/my-products.php" class="quick-action-card">
                            <div class="quick-action-icon">
                                <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products">
                            </div>
                            <div class="quick-action-info">
                                <h4>My Products</h4>
                                <p>Manage listings</p>
                            </div>
                        </a>
                        <a href="<?php echo $baseUrl; ?>admin/seller-orders.php" class="quick-action-card">
                            <div class="quick-action-icon">
                                <img src="<?php echo $baseUrl; ?>images/icons/clipboard-svgrepo-com.svg" alt="Orders">
                            </div>
                            <div class="quick-action-info">
                                <h4>Manage Orders</h4>
                                <p>Process and ship orders</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Verification Component (Full Width) -->
            <?php include 'includes/verification-component.php'; ?>

        </div>

        <!-- ============================================================
        TAB: ADMIN CONTENT
        ============================================================ -->
        <?php if ($hasAdminRole): ?>
            <div id="tab-admin" class="tab-content <?php echo $defaultTab === 'admin' ? 'active' : ''; ?>">

                <div class="profile-content">
                    <div class="profile-card">
                        <h3>Admin Dashboard Quick Links</h3>
                        <div class="quick-actions-grid">
                            <a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php" class="quick-action-card">
                                <div class="quick-action-icon">
                                    <img src="<?php echo $baseUrl; ?>images/icons/dashboard-svgrepo-com.svg" alt="Dashboard">
                                </div>
                                <div class="quick-action-info">
                                    <h4>Dashboard</h4>
                                    <p>View system overview</p>
                                </div>
                            </a>
                            <a href="<?php echo $baseUrl; ?>admin/users.php" class="quick-action-card">
                                <div class="quick-action-icon">
                                    <img src="<?php echo $baseUrl; ?>images/icons/users-svgrepo-com.svg" alt="Users">
                                </div>
                                <div class="quick-action-info">
                                    <h4>Manage Users</h4>
                                    <p>View and manage all users</p>
                                </div>
                            </a>
                            <a href="<?php echo $baseUrl; ?>admin/all-orders.php" class="quick-action-card">
                                <div class="quick-action-icon">
                                    <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Orders">
                                </div>
                                <div class="quick-action-info">
                                    <h4>All Orders</h4>
                                    <p>View platform orders</p>
                                </div>
                            </a>
                            <a href="<?php echo $baseUrl; ?>admin/flagged-listings.php" class="quick-action-card">
                                <div class="quick-action-icon">
                                    <img src="<?php echo $baseUrl; ?>images/icons/warning-svgrepo-com.svg" alt="Reports">
                                </div>
                                <div class="quick-action-info">
                                    <h4>Flagged Reports</h4>
                                    <p>Review reported content</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ============================================================
        COMMON: EDIT PROFILE (Appears on all tabs)
        ============================================================ -->
        <div class="profile-content">
            <div class="profile-card">
                <h3>Edit Profile</h3>
                <form id="profile-edit-form" class="profile-edit-form">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($email); ?>" disabled>
                        <small>Email cannot be changed</small>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="e.g., 071 234 5678">
                        <small>Optional but recommended for order updates</small>
                    </div>

                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>" placeholder="e.g., Johannesburg, South Africa">
                        <small>Your city or region</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="save-btn">Save Changes</button>
                        <button type="button" class="change-password-btn" onclick="openChangePasswordModal()">Change Password</button>
                    </div>
                </form>
            </div>

            <div class="profile-card">
                <h3>Account Information</h3>
                <div class="stats-list">
                    <div class="stat-row">
                        <span class="stat-label">Member Since</span>
                        <span class="stat-value"><?php echo date('d M Y', strtotime($created_at)); ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Account Status</span>
                        <span class="stat-value" style="color: var(--success);">Active</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Roles</span>
                        <span class="stat-value">
                            <?php
                            $roleLabels = [];
                            if ($hasBuyerRole) $roleLabels[] = 'Buyer';
                            if ($hasSellerRole) $roleLabels[] = 'Seller';
                            if ($hasAdminRole) $roleLabels[] = 'Admin';
                            echo implode(' • ', $roleLabels);
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danger Zone - Delete Account -->
        <div class="danger-zone">
            <div class="danger-card">
                <div class="danger-info">
                    <h4>Delete Account</h4>
                    <p>Once you delete your account, there is no going back. All your data will be permanently removed.</p>
                </div>
                <button class="delete-account-btn" onclick="showDeleteModal()">Delete Account</button>
            </div>
        </div>

        <!-- Delete Account Confirmation Modal -->
        <div id="delete-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h1>Delete Account</h1>
                    <button class="btn-close" onclick="closeDeleteModal()">&times;</button>
                </div>
                <form id="delete-account-form" style="padding: var(--spacing-lg);">
                    <p>Are you sure you want to delete your account?</p>
                    <p class="warning-text">This action cannot be undone. All your data will be permanently removed.</p>
                    <div class="form-group">
                        <label for="delete-password">Enter your password to confirm</label>
                        <div class="password-field-wrapper">
                            <input type="password" id="delete-password" name="password" required>
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('delete-password', this)">
                                <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="18" height="18" alt="Show password">
                            </button>
                        </div>
                    </div>
                    <div class="form-actions" style="margin-top: var(--spacing-lg);">
                        <button type="submit" class="delete-confirm-btn">Confirm Delete</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password Modal -->
        <div id="change-password-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h1>Change Password</h1>
                    <button class="btn-close" onclick="closeChangePasswordModal()">&times;</button>
                </div>
                <form id="change-password-form" style="padding: var(--spacing-lg);">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <div class="password-field-wrapper">
                            <input type="password" id="current_password" name="current_password" required>
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('current_password', this)">
                                <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="18" height="18">
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="password-field-wrapper">
                            <input type="password" id="new_password" name="new_password" required>
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('new_password', this)">
                                <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="18" height="18">
                            </button>
                        </div>
                        <small>Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="password-field-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password', this)">
                                <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="18" height="18">
                            </button>
                        </div>
                    </div>

                    <div class="form-actions" style="margin-top: var(--spacing-lg);">
                        <button type="button" class="delete-cancel-btn" onclick="closeChangePasswordModal()">Cancel</button>
                        <button type="submit" class="save-btn">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';
        var currentUserId = <?php echo $user_id; ?>;
        var currentUser = <?php echo isset($currentUser) ? json_encode([
                                'hasRole' => function ($role) use ($currentUser) {
                                    return $currentUser->hasRole($role);
                                }
                            ]) : 'null'; ?>;

        // ============================================================
        // DOM CACHE
        // ============================================================
        var $deleteModal = $('#delete-modal');
        var $changePasswordModal = $('#change-password-modal');
        var $deletePassword = $('#delete-password');
        var $currentPassword = $('#current_password');
        var $newPassword = $('#new_password');
        var $confirmPassword = $('#confirm_password');
        var $profileAvatar = $('#profile-avatar');
        var $profileImageUpload = $('#profile-image-upload');
        var $avatarUploadBtn = $('.avatar-upload-btn');
        var $fullName = $('#full_name');
        var $phone = $('#phone');
        var $location = $('#location');

        // ============================================================
        // MODAL CONTROLS
        // ============================================================

        window.showDeleteModal = function() {
            $deleteModal.addClass('active');
        };

        window.closeDeleteModal = function() {
            $deleteModal.removeClass('active');
            $deletePassword.val('');
        };

        window.openChangePasswordModal = function() {
            $changePasswordModal.addClass('active');
            $currentPassword.val('');
            $newPassword.val('');
            $confirmPassword.val('');
        };

        window.closeChangePasswordModal = function() {
            $changePasswordModal.removeClass('active');
        };

        // ============================================================
        // PASSWORD TOGGLE (GLOBAL FOR ONCLICK)
        // ============================================================

        window.togglePassword = function(fieldId, button) {
            var $input = $('#' + fieldId);
            var $img = $(button).find('img');

            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $img.attr('src', baseUrl + 'images/icons/eye-close-svgrepo-com.svg');
                $img.attr('alt', 'Hide password');
            } else {
                $input.attr('type', 'password');
                $img.attr('src', baseUrl + 'images/icons/eye-open-svgrepo-com.svg');
                $img.attr('alt', 'Show password');
            }
        };

        // ============================================================
        // TOAST HELPER
        // ============================================================

        function showMessage(message, isError) {
            var $msg = isError ? $('#error-message') : $('#flash-message');
            if (!$msg.length) {
                if (typeof showToast === 'function') {
                    showToast(message, isError ? 'error' : 'success');
                }
                return;
            }
            $msg.text(message).show();
            setTimeout(function() {
                $msg.fadeOut(500);
            }, 5000);
        }

        // ============================================================
        // TAB SWITCHING
        // ============================================================

        function switchTab(tab) {
            // Update URL hash
            if (history.pushState) {
                history.pushState(null, null, '#' + tab);
            }
            // Save to session storage
            sessionStorage.setItem('activeProfileTab', tab);

            // Update tab buttons
            $('.profile-tab').removeClass('active');
            $('.profile-tab[data-tab="' + tab + '"]').addClass('active');

            // Update tab content
            $('.tab-content').removeClass('active');
            $('#tab-' + tab).addClass('active');
        }

        function activateTab() {
            var tab = null;
            var validTabs = ['buyer', 'seller', 'admin'];

            // 1. Check URL hash first (highest priority)
            var hash = window.location.hash.replace('#', '');
            if (hash && validTabs.includes(hash)) {
                tab = hash;
            }

            // 2. Check session storage if no hash
            if (!tab) {
                var saved = sessionStorage.getItem('activeProfileTab');
                if (saved && validTabs.includes(saved)) {
                    tab = saved;
                }
            }

            // 3. Check if tab exists in DOM
            if (tab && $('.profile-tab[data-tab="' + tab + '"]').length) {
                switchTab(tab);
                return;
            }

            // 4. Default to PHP default
            var defaultTab = '<?php echo $defaultTab; ?>';
            if ($('.profile-tab[data-tab="' + defaultTab + '"]').length) {
                switchTab(defaultTab);
            }
        }

        $(document).on('click', '.profile-tab', function() {
            var tab = $(this).data('tab');
            switchTab(tab);
        });

        $(window).on('hashchange', function() {
            var hash = window.location.hash.replace('#', '');
            if (hash) {
                switchTab(hash);
            }
        });

        $(function() {
            activateTab();
        });
        // ============================================================
        // LOAD STATS FOR EACH ROLE
        // ============================================================

        function loadUserStats() {
            // Buyer stats
            var $statOrders = $('#stat-orders');
            var $statSpent = $('#stat-spent');
            var $statPending = $('#stat-pending');
            var $statReviews = $('#stat-reviews');

            if ($statOrders.length) {
                $statOrders.text('Loading...');
                $statSpent.text('Loading...');
            }

            // Seller stats
            var $statProducts = $('#stat-products');
            var $statSales = $('#stat-sales');
            var $statRevenue = $('#stat-revenue');
            var $statRating = $('#stat-rating');

            if ($statProducts.length) {
                $statProducts.text('Loading...');
                $statSales.text('Loading...');
                $statRevenue.text('Loading...');
                $statRating.text('Loading...');
            }

            $.ajax({
                url: baseUrl + 'php/endpoints/users/get-user-stats.php?user_id=' + currentUserId,
                type: 'GET',
                dataType: 'json',
                timeout: 10000,
                success: function(data) {
                    if (data.success) {
                        // Buyer stats
                        if ($statOrders.length) {
                            $statOrders.text(data.total_orders || 0);
                            $statSpent.text('R ' + (data.total_spent || 0).toFixed(2));
                            if (data.pending_orders && data.pending_orders > 0) {
                                $statPending.text(data.pending_orders);
                                $('#pending-row').show();
                            } else {
                                $('#pending-row').hide();
                            }
                            if (data.reviews_written && data.reviews_written > 0) {
                                $statReviews.text(data.reviews_written);
                                $('#reviews-row').show();
                            } else {
                                $('#reviews-row').hide();
                            }
                        }

                        // Seller stats
                        if ($statProducts.length) {
                            $statProducts.text(data.total_products || 0);
                            $statSales.text(data.completed_orders || 0);
                            $statRevenue.text('R ' + (data.total_revenue || 0).toFixed(2));
                            $statRating.text(data.avg_rating ? data.avg_rating.toFixed(1) + '/5' : 'No reviews yet');
                        }

                        // Verification status
                        var $verificationStatus = $('#verification-status-text');
                        if ($verificationStatus.length) {
                            var statusText = data.is_verified ? '✅ Verified Seller' :
                                (data.has_document ? '⏳ Pending Review' : '❌ Not Verified');
                            var statusColor = data.is_verified ? 'var(--success)' :
                                (data.has_document ? 'var(--info)' : 'var(--warning)');
                            $verificationStatus.html('<span style="color: ' + statusColor + ';">' + statusText + '</span>');
                        }
                    }
                },
                error: function() {
                    if ($('#stat-orders').length) {
                        $('#stat-orders').text('-');
                        $('#stat-spent').text('-');
                    }
                    if ($('#stat-products').length) {
                        $('#stat-products').text('-');
                        $('#stat-sales').text('-');
                        $('#stat-revenue').text('-');
                        $('#stat-rating').text('No reviews yet');
                    }
                }
            });
        }

        // ============================================================
        // PROFILE IMAGE UPLOAD
        // ============================================================

        $profileImageUpload.on('change', function() {
            var file = this.files[0];
            if (!file) return;

            var validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                showMessage('Invalid file type. Please upload a JPG, PNG, GIF, or WebP image.', true);
                this.value = '';
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                showMessage('File is too large. Maximum size is 2MB.', true);
                this.value = '';
                return;
            }

            var reader = new FileReader();
            reader.onload = function(e) {
                $profileAvatar.attr('src', e.target.result);
            };
            reader.readAsDataURL(file);

            var formData = new FormData();
            formData.append('action', 'upload_image');
            formData.append('profile_image', file);

            $avatarUploadBtn.html('<span style="font-size:14px; color: white;">...</span>');

            $.ajax({
                url: baseUrl + 'php/endpoints/users/update-profile.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                timeout: 30000,
                success: function(data) {
                    $avatarUploadBtn.html('<img src="' + baseUrl + 'images/icons/camera-svgrepo-com.svg" alt="Upload">');
                    if (data.success) {
                        showMessage(data.message, false);
                    } else {
                        showMessage(data.message || 'Failed to upload image.', true);
                    }
                },
                error: function() {
                    $avatarUploadBtn.html('<img src="' + baseUrl + 'images/icons/camera-svgrepo-com.svg" alt="Upload">');
                    showMessage('Error uploading image. Please try again.', true);
                }
            });
        });

        $avatarUploadBtn.on('click', function(e) {
            e.preventDefault();
            $profileImageUpload.click();
        });

        // ============================================================
        // PROFILE EDIT FORM
        // ============================================================

        $('#profile-edit-form').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);
            formData.append('action', 'update_profile');

            var $submitBtn = $(this).find('.save-btn');
            var originalText = $submitBtn.text();
            $submitBtn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: baseUrl + 'php/endpoints/users/update-profile.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                timeout: 15000,
                success: function(data) {
                    $submitBtn.prop('disabled', false).text(originalText);
                    if (data.success) {
                        showMessage(data.message, false);
                        $('.profile-user-info h1').text($fullName.val());
                    } else {
                        showMessage(data.message || 'Failed to update profile.', true);
                    }
                },
                error: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                    showMessage('Error updating profile. Please try again.', true);
                }
            });
        });

        // ============================================================
        // CHANGE PASSWORD
        // ============================================================

        $('#change-password-form').on('submit', function(e) {
            e.preventDefault();

            var currentPassword = $currentPassword.val();
            var newPassword = $newPassword.val();
            var confirmPassword = $confirmPassword.val();

            $('.form-group').removeClass('error');

            if (newPassword.length < 6) {
                showMessage('New password must be at least 6 characters.', true);
                $newPassword.closest('.form-group').addClass('error');
                return;
            }

            if (newPassword !== confirmPassword) {
                showMessage('New passwords do not match.', true);
                $confirmPassword.closest('.form-group').addClass('error');
                return;
            }

            var $submitBtn = $(this).find('.save-btn');
            var originalText = $submitBtn.text();
            $submitBtn.prop('disabled', true).text('Updating...');

            $.ajax({
                url: baseUrl + 'php/endpoints/users/change-password.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword
                }),
                dataType: 'json',
                timeout: 15000,
                success: function(data) {
                    $submitBtn.prop('disabled', false).text(originalText);
                    if (data.success) {
                        showMessage('Password changed successfully!', false);
                        closeChangePasswordModal();
                    } else {
                        showMessage(data.message || 'Failed to change password.', true);
                    }
                },
                error: function(xhr) {
                    $submitBtn.prop('disabled', false).text(originalText);
                    if (xhr.status === 401) {
                        showMessage('Current password is incorrect.', true);
                    } else {
                        showMessage('Error changing password. Please try again.', true);
                    }
                }
            });
        });

        // ============================================================
        // DELETE ACCOUNT
        // ============================================================

        $('#delete-account-form').on('submit', function(e) {
            e.preventDefault();

            var password = $deletePassword.val();

            if (!password) {
                showMessage('Please enter your password.', true);
                return;
            }

            var $submitBtn = $(this).find('.delete-confirm-btn');
            var originalText = $submitBtn.text();
            $submitBtn.prop('disabled', true).text('Processing...');

            $.ajax({
                url: baseUrl + 'php/endpoints/users/delete-account.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    password: password
                }),
                dataType: 'json',
                timeout: 15000,
                success: function(data) {
                    if (data.success) {
                        showMessage('Your account has been deleted.', false);
                        setTimeout(function() {
                            window.location.href = baseUrl;
                        }, 2000);
                    } else {
                        $submitBtn.prop('disabled', false).text(originalText);
                        showMessage(data.message || 'Failed to delete account.', true);
                    }
                },
                error: function(xhr) {
                    $submitBtn.prop('disabled', false).text(originalText);
                    if (xhr.status === 401) {
                        showMessage('Invalid password. Please try again.', true);
                    } else {
                        showMessage('Error deleting account. Please try again.', true);
                    }
                }
            });
        });

        // ============================================================
        // DOCUMENT READY
        // ============================================================

        $(function() {
            loadUserStats();
        });
    </script>

</body>

</html>