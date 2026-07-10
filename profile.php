<?php
/*
 * ConsuTrade - Unified User Profile
 * Author: Kamogelo Phale
 * 
 * Single profile page that adapts to user's roles
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';

$breadcrumbItems = [
    ['label' => 'My Profile']
];

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

$hasBuyerRole = $currentUser->hasRole('buyer');
$hasSellerRole = $currentUser->hasRole('seller');
$hasAdminRole = $currentUser->hasRole('admin');
$activeRole = $auth->getActiveRole();

$roleCount = 0;
if ($hasBuyerRole) $roleCount++;
if ($hasSellerRole) $roleCount++;
if ($hasAdminRole) $roleCount++;

if ($roleCount === 1) {
    if ($hasBuyerRole) $defaultTab = 'buyer';
    elseif ($hasSellerRole) $defaultTab = 'seller';
    elseif ($hasAdminRole) $defaultTab = 'admin';
} else {
    $defaultTab = $activeRole ?? 'buyer';
}

$page_js = 'profile.js';
$load_verification_js = true;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <style>
        .profile-container {
            width: 100%;
            padding: var(--spacing-xl);
            min-height: calc(100vh - 200px);
        }

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

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .profile-content {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
        }

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
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main class="profile-container">
        <?php include 'includes/breadcrumb.php'; ?>
        <?php include 'includes/flash-message.php'; ?>

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

        <form id="profile-image-form" style="display: none;">
            <input type="file" name="profile_image" id="profile-image-upload" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
        </form>

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

        <!-- Buyer Tab -->
        <div id="tab-buyer" class="tab-content <?php echo $defaultTab === 'buyer' ? 'active' : ''; ?>">
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

        <!-- Seller Tab -->
        <div id="tab-seller" class="tab-content <?php echo $defaultTab === 'seller' ? 'active' : ''; ?>">
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
            <?php include 'includes/verification-component.php'; ?>
        </div>

        <!-- Admin Tab -->
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

        <!-- Edit Profile -->
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

        <!-- Danger Zone -->
        <div class="danger-zone">
            <div class="danger-card">
                <div class="danger-info">
                    <h4>Delete Account</h4>
                    <p>Once you delete your account, there is no going back. All your data will be permanently removed.</p>
                </div>
                <button class="delete-account-btn" onclick="showDeleteModal()">Delete Account</button>
            </div>
        </div>

        <!-- Delete Account Modal -->
        <div id="delete-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h1>Delete Account</h1>
                    <button class="btn-close" onclick="closeDeleteModal()">&times;</button>
                </div>
                <form id="delete-account-form" style="padding: var(--spacing-lg);">
                    <p>Are you sure you want to delete your account?</p>
                    <p class="warning-text">This action cannot be undone.</p>
                    <div class="form-group">
                        <label for="delete-password">Enter your password to confirm</label>
                        <div class="password-field-wrapper">
                            <input type="password" id="delete-password" name="password" required>
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('delete-password', this)">
                                <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="18" height="18" alt="Show">
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

    <script>
        var profileUserId = <?php echo $user_id; ?>;
        var profileDefaultTab = '<?php echo $defaultTab; ?>';
    </script>
    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

</body>

</html>