<?php
/*
 * ConsuTrade - User Profile Page
 * Author: Kamogelo Phale
 * 
 * This page allows users to view and edit their profile information
 */

session_start();

$baseUrl = "/www/consutrade/";

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

require_once 'php/config.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $location = trim($_POST['location']);
    $phone = trim($_POST['phone']);
    
    $update_sql = "UPDATE users SET full_name = ?, location = ?, phone = ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('sssi', $full_name, $location, $phone, $user_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['full_name'] = $full_name;
        $_SESSION['location'] = $location;
        $success_message = 'Profile updated successfully!';
    } else {
        $error_message = 'Failed to update profile. Please try again.';
    }
    $update_stmt->close();
}

// Get user data with verification status
$sql = "SELECT full_name, email, role, location, phone, created_at, id_verified 
        FROM users 
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get seller stats if user is a seller
$total_products = 0;
$total_sales = 0;
if ($role === 'seller') {
    $product_sql = "SELECT COUNT(*) as count FROM products WHERE seller_id = ?";
    $product_stmt = $conn->prepare($product_sql);
    $product_stmt->bind_param('i', $user_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    if ($product_row = $product_result->fetch_assoc()) {
        $total_products = $product_row['count'];
    }
    $product_stmt->close();
    
    $sales_sql = "SELECT COUNT(*) as count FROM orders WHERE seller_id = ? AND status = 'completed'";
    $sales_stmt = $conn->prepare($sales_sql);
    $sales_stmt->bind_param('i', $user_id);
    $sales_stmt->execute();
    $sales_result = $sales_stmt->get_result();
    if ($sales_row = $sales_result->fetch_assoc()) {
        $total_sales = $sales_row['count'];
    }
    $sales_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ConsuTrade</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/login-signup.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="profile-container">
        <!-- User Profile Header -->
        <div class="profile-user-header">
            <div class="profile-user-avatar">
                <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="Profile Avatar">
            </div>
            <div class="profile-user-info">
                <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                <div class="profile-user-meta">
                    <span class="role-badge role-<?php echo $user['role']; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                    <?php if ($user['role'] === 'seller'): ?>
                        <?php if ($user['id_verified'] == 1): ?>
                            <span class="verification-badge verified">Verified Seller</span>
                        <?php else: ?>
                            <span class="verification-badge not-verified">Not Verified</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <span class="member-since">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if ($success_message): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Profile Content -->
        <div class="profile-content">
            <!-- Left Column - Profile Stats -->
            <div class="profile-stats-card">
                <h3>Account Information</h3>
                <div class="stats-list">
                    <div class="stat-row">
                        <span class="stat-label">Email Address</span>
                        <span class="stat-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <?php if ($user['role'] === 'seller'): ?>
                    <div class="stat-row">
                        <span class="stat-label">Location</span>
                        <span class="stat-value"><?php echo htmlspecialchars($user['location'] ?: 'Not set'); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($user['phone']): ?>
                    <div class="stat-row">
                        <span class="stat-label">Phone Number</span>
                        <span class="stat-value"><?php echo htmlspecialchars($user['phone']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column - Edit Form -->
            <div class="profile-edit-card">
                <h3>Edit Profile</h3>
                <form method="POST" action="" class="profile-edit-form">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        <small>Email cannot be changed</small>
                    </div>
                    
                    <?php if ($user['role'] === 'seller'): ?>
                    <div class="form-group">
                        <label for="location">Location (City, Province)</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user['location']); ?>" placeholder="e.g., Johannesburg, Soweto">
                        <small>Your location helps buyers find your products</small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="e.g., 071 234 5678">
                        <small>Optional but recommended for order updates</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="save-btn">Save Changes</button>
                        <a href="<?php echo $baseUrl; ?>change-password.php" class="change-password-btn">Change Password</a>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($user['role'] === 'seller'): ?>
        <!-- Seller Dashboard Links -->
        <div class="seller-dashboard-links">
            <h3>Seller Dashboard</h3>
            <div class="seller-links-grid">
                <a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php" class="seller-link-card">
                    <div class="seller-link-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/dashboard-svgrepo-com.svg" alt="Dashboard">
                    </div>
                    <div class="seller-link-info">
                        <h4>Dashboard</h4>
                        <p>View your sales overview</p>
                    </div>
                    <span class="seller-link-arrow">→</span>
                </a>
                <a href="<?php echo $baseUrl; ?>admin/my-products.php" class="seller-link-card">
                    <div class="seller-link-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products">
                    </div>
                    <div class="seller-link-info">
                        <h4>My Products</h4>
                        <p>Manage your product listings</p>
                    </div>
                    <span class="seller-link-arrow">→</span>
                </a>
                <a href="<?php echo $baseUrl; ?>admin/my-orders.php" class="seller-link-card">
                    <div class="seller-link-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Orders">
                    </div>
                    <div class="seller-link-info">
                        <h4>My Orders</h4>
                        <p>Track and manage orders</p>
                    </div>
                    <span class="seller-link-arrow">→</span>
                </a>
                <a href="<?php echo $baseUrl; ?>admin/add-product.php" class="seller-link-card">
                    <div class="seller-link-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/add-svgrepo-com.svg" alt="Add Product">
                    </div>
                    <div class="seller-link-info">
                        <h4>Add Product</h4>
                        <p>List a new product for sale</p>
                    </div>
                    <span class="seller-link-arrow">→</span>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>
    <script src="js/main.js"></script>
</body>
</html>