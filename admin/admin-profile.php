<?php
/*
 * ConsuTrade - Admin Profile Page
 * Author: Kamogelo Phale
 * 
 * This page allows admin users to view and edit their profile information
 */

require_once dirname(__DIR__) . '/init.php';

$baseUrl = getBaseUrl();

// Check if user is logged in and is admin using centralized auth
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $current_user_id;
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    
    // Clean phone number
    $clean_phone = !empty($phone) ? preg_replace('/[^0-9]/', '', $phone) : '';
    if (!empty($clean_phone) && !preg_match('/^0[0-9]{9,10}$/', $clean_phone)) {
        $error_message = 'Please enter a valid South African phone number (e.g., 0712345678)';
    } else {
        $update_sql = "UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('ssi', $full_name, $clean_phone, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $success_message = 'Profile updated successfully!';
        } else {
            $error_message = 'Failed to update profile. Please try again.';
        }
        $update_stmt->close();
    }
}

// Get user data using helper
$user = getUserById($conn, $user_id);

if (!$user) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Set active state for sidebar
$active_profile = 'active';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/admin-header.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/admin-profile.css">
</head>
<body>
    <?php include 'admin-header.php'; ?>

    <main class="admin-main-content">
        <div class="admin-profile-container">
            <div class="admin-profile-header">
                <h1>Admin Profile</h1>
                <p>Manage your account information</p>
            </div>

            <?php if ($success_message): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="admin-profile-layout">
                <!-- Left Column - Profile Info -->
                <div class="admin-profile-left">
                    <div class="admin-profile-avatar">
                        <img src="<?php echo getUserProfileImage($user['profile_image'] ?? null); ?>" alt="Profile Avatar">
                        <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <span class="admin-role-badge">Administrator</span>
                    </div>
                    
                    <div class="admin-profile-stats">
                        <div class="stat-item">
                            <span class="stat-label">Member Since</span>
                            <span class="stat-value"><?php echo formatDate($user['created_at']); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Email</span>
                            <span class="stat-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <?php if ($user['phone']): ?>
                        <div class="stat-item">
                            <span class="stat-label">Phone</span>
                            <span class="stat-value"><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="stat-item">
                            <span class="stat-label">Role</span>
                            <span class="stat-value"><?php echo ucfirst($user['role']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Edit Form -->
                <div class="admin-profile-right">
                    <form method="POST" action="" class="admin-profile-form">
                        <h3>Edit Profile Information</h3>
                        
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            <small>Email cannot be changed</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number (Optional)</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="e.g., 071 234 5678">
                            <small>Optional but recommended for contact</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="save-btn">Save Changes</button>
                            <a href="<?php echo $baseUrl; ?>admin/change-password.php" class="change-password-btn">Change Password</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Admin Quick Links -->
            <div class="admin-quick-links">
                <h3>Admin Quick Links</h3>
                <div class="quick-links-grid">
                    <a href="users.php" class="quick-link-card">
                        <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" width="24px" height="24px" alt="Users">
                        <span>Manage Users</span>
                    </a>
                    <a href="all-products.php" class="quick-link-card">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="24px" height="24px" alt="Products">
                        <span>All Products</span>
                    </a>
                    <a href="all-orders.php" class="quick-link-card">
                        <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="24px" height="24px" alt="Orders">
                        <span>All Orders</span>
                    </a>
                    <a href="admin-dashboard.php" class="quick-link-card">
                        <img src="<?php echo $baseUrl; ?>images/icons/dashboard-svgrepo-com.svg" width="24px" height="24px" alt="Dashboard">
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
    <script src="<?php echo $baseUrl; ?>admin/js/admin.js"></script>
</body>
</html>