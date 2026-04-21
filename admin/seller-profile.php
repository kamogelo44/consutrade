<?php
/*
 * ConsuTrade - Seller Profile Page
 * Author: Kamogelo Phale
 * 
 * This page allows sellers to view and edit their profile information
 * from within the admin dashboard context
 */

session_start();

$baseUrl = "/www/consutrade/";

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Check if user is a seller
if ($_SESSION['role'] !== 'seller') {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/php/config.php';

// Set current page for active sidebar link
$current_page = 'profile';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get user data
$sql = "SELECT full_name, email, role, location, phone, created_at, id_verified, profile_image 
        FROM users 
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Set profile image path
if (!empty($user['profile_image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $user['profile_image'])) {
    $profile_image = $baseUrl . $user['profile_image'];
} else {
    $profile_image = $baseUrl . 'images/icons/profile-svgrepo-com.svg';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Profile - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/seller-dashboard.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/seller-profile.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
</head>
<body class="seller-dashboard-page">

<!-- Mobile Toggle Button for sidebar -->
<button class="seller-mobile-toggle" id="sellerHamburger">
    <span></span>
    <span></span>
    <span></span>
</button>

<!-- Main Dashboard Wrapper -->
<div class="seller-dashboard">
    <!-- Sidebar -->
    <aside class="seller-sidebar" id="sellerSideMenu">
        <div class="seller-sidebar-header">
            <div class="seller-sidebar-logo">
                <a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php">Consu<span>Trade</span></a>
            </div>
            <button class="seller-sidebar-close" id="sellerSidebarClose">
                <span></span>
                <span></span>
            </button>
        </div>
        
        <nav class="seller-sidebar-nav">
            <ul>
                <li>
                    <a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php" class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                        <img src="<?php echo $baseUrl; ?>images/icons/dashboard-svgrepo-com.svg" width="20px" height="20px" alt="Dashboard" onerror="this.style.display='none'">
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $baseUrl; ?>admin/my-products.php" class="<?php echo $current_page === 'products' ? 'active' : ''; ?>">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="20px" height="20px" alt="Products" onerror="this.style.display='none'">
                        <span>My Products</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $baseUrl; ?>admin/my-orders.php" class="<?php echo $current_page === 'orders' ? 'active' : ''; ?>">
                        <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="20px" height="20px" alt="Orders" onerror="this.style.display='none'">
                        <span>My Orders</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $baseUrl; ?>admin/add-product.php" class="<?php echo $current_page === 'add-product' ? 'active' : ''; ?>">
                        <img src="<?php echo $baseUrl; ?>images/icons/add-svgrepo-com.svg" width="20px" height="20px" alt="Add Product" onerror="this.style.display='none'">
                        <span>Add Product</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="seller-sidebar-footer">
            <a href="<?php echo $baseUrl; ?>admin/seller-profile.php" class="seller-sidebar-link <?php echo $current_page === 'profile' ? 'active' : ''; ?>">
                <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="Profile" width="20px" height="20px" onerror="this.style.display='none'">
                <span>My Profile</span>
            </a>
            <a href="<?php echo $baseUrl; ?>php/logout.php" class="seller-sidebar-link logout">
                <img src="<?php echo $baseUrl; ?>images/icons/logout-svgrepo-com.svg" alt="Logout" width="20px" height="20px" onerror="this.style.display='none'">
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Overlay for mobile -->
    <div class="seller-menu-overlay" id="sellerMenuOverlay"></div>

    <!-- Main Content -->
    <main class="seller-main-content">
        <!-- Page Header -->
        <div class="dashboard-header">
            <h1>My Profile</h1>
            <p>View and manage your seller account information</p>
        </div>

        <!-- Flash Messages -->
        <div id="flash-message" class="flash-message" style="display: none;"></div>
        <div id="error-message" class="error-message" style="display: none;"></div>

        <!-- Profile Content -->
        <div class="profile-content-wrapper">
            <!-- User Profile Header Card -->
            <div class="profile-header-card">
                <div class="profile-avatar-section">
                    <div class="profile-avatar-container">
                        <img src="<?php echo $profile_image; ?>" alt="Profile Avatar" id="profile-avatar" class="profile-avatar-large">
                        <label for="profile-image-upload" class="avatar-upload-btn" title="Change profile picture">
                            <img src="<?php echo $baseUrl; ?>images/icons/camera-svgrepo-com.svg" alt="Upload">
                        </label>
                    </div>
                    <div class="profile-header-info">
                        <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <div class="profile-badges">
                            <span class="role-badge seller-badge">Seller</span>
                            <?php if ($user['id_verified'] == 1): ?>
                                <span class="verification-badge verified">Verified Seller</span>
                            <?php else: ?>
                                <span class="verification-badge not-verified">Not Verified</span>
                            <?php endif; ?>
                            <span class="member-since">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Hidden file input for profile image -->
            <form id="profile-image-form" style="display: none;">
                <input type="file" name="profile_image" id="profile-image-upload" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
            </form>

            <!-- Two Column Layout -->
            <div class="profile-two-columns">
                <!-- Left Column - Seller Statistics -->
                <div class="profile-stats-card">
                    <h3>Seller Statistics</h3>
                    <div class="stats-list">
                        <div class="stat-row">
                            <span class="stat-label">Location</span>
                            <span class="stat-value"><?php echo htmlspecialchars($user['location'] ?? 'Not specified'); ?></span>
                        </div>
                        
                        <div class="stat-row">
                            <span class="stat-label">Phone Number</span>
                            <span class="stat-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not specified'); ?></span>
                        </div>
                        
                        <div class="stat-divider"></div>
                        
                        <div class="stat-row">
                            <span class="stat-label">Products Listed</span>
                            <span class="stat-value highlight" id="stat-products">-</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Orders Completed</span>
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
                        
                        <div class="stat-divider"></div>
                        
                        <div class="stat-row">
                            <span class="stat-label">Account Status</span>
                            <span class="stat-value">
                                <?php if ($user['id_verified'] == 1): ?>
                                    <span style="color: var(--success);">✓ Verified</span>
                                <?php else: ?>
                                    <span style="color: var(--warning);">⚠ Pending Verification</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Edit Form -->
                <div class="profile-edit-card">
                    <h3>Edit Profile Information</h3>
                    <form id="profile-edit-form" class="profile-edit-form">
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
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="e.g., 071 234 5678">
                            <small>Optional but recommended for order updates</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Location (City, Province)</label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" placeholder="e.g., Johannesburg, Gauteng">
                            <small>Your location helps buyers find your products</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="save-btn">Save Changes</button>
                            <a href="<?php echo $baseUrl; ?>change-password.php" class="change-password-btn">Change Password</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Quick Actions Section -->
            <div class="quick-actions-section">
                <h3>Quick Actions</h3>
                <div class="quick-actions-grid">
                    <a href="<?php echo $baseUrl; ?>admin/add-product.php" class="quick-action-card">
                        <div class="quick-action-icon">
                            <img src="<?php echo $baseUrl; ?>images/icons/add-svgrepo-com.svg" alt="Add Product">
                        </div>
                        <div class="quick-action-info">
                            <h4>Add New Product</h4>
                            <p>List a new product for sale</p>
                        </div>
                        <span class="quick-action-arrow">→</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>admin/my-products.php" class="quick-action-card">
                        <div class="quick-action-icon">
                            <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="My Products">
                        </div>
                        <div class="quick-action-info">
                            <h4>Manage Products</h4>
                            <p>Edit or remove existing products</p>
                        </div>
                        <span class="quick-action-arrow">→</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>admin/my-orders.php" class="quick-action-card">
                        <div class="quick-action-icon">
                            <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="My Orders">
                        </div>
                        <div class="quick-action-info">
                            <h4>View Orders</h4>
                            <p>Track and manage customer orders</p>
                        </div>
                        <span class="quick-action-arrow">→</span>
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="<?php echo $baseUrl; ?>js/main.js"></script>
<script>
var baseUrl = '<?php echo $baseUrl; ?>';

$(document).ready(function() {
    // Show flash message
    function showMessage(message, isError) {
        if (isError) {
            $('#error-message').text(message).show();
            setTimeout(function() {
                $('#error-message').fadeOut();
            }, 5000);
        } else {
            $('#flash-message').text(message).show();
            setTimeout(function() {
                $('#flash-message').fadeOut();
            }, 5000);
        }
    }
    
    // Load seller statistics via AJAX
    function loadSellerStats() {
        $.ajax({
            url: baseUrl + 'php/get-user-stats.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    updateStatsDisplay(data);
                } else {
                    console.log('Failed to load stats:', data);
                    setFallbackStats();
                }
            },
            error: function(xhr, status, error) {
                console.log('Error loading stats:', error);
                setFallbackStats();
            }
        });
    }
    
    function setFallbackStats() {
        $('#stat-products').text('0');
        $('#stat-sales').text('0');
        $('#stat-revenue').text('R 0.00');
        $('#stat-rating').text('No reviews yet');
    }
    
    function updateStatsDisplay(stats) {
        $('#stat-products').text(stats.total_products || 0);
        $('#stat-sales').text(stats.total_sales || 0);
        $('#stat-revenue').text('R ' + (stats.total_revenue || 0).toFixed(2));
        if (stats.total_reviews > 0) {
            $('#stat-rating').html(stats.avg_rating + ' / 5 <span style="font-size: 12px; color: var(--gray-light);">(' + stats.total_reviews + ' reviews)</span>');
        } else {
            $('#stat-rating').html('No reviews yet');
        }
    }
    
    // Profile image upload
    $('#profile-image-upload').on('change', function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#profile-avatar').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
            
            var formData = new FormData();
            formData.append('action', 'upload_image');
            formData.append('profile_image', file);
            
            $.ajax({
                url: baseUrl + 'php/profile-handler.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        showMessage(data.message, false);
                    } else {
                        showMessage(data.message, true);
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                },
                error: function() {
                    showMessage('Error uploading image. Please try again.', true);
                }
            });
        }
    });
    
    // Trigger file input when clicking avatar upload button
    $('.avatar-upload-btn').on('click', function(e) {
        e.preventDefault();
        $('#profile-image-upload').click();
    });
    
    // Profile edit form submission
    $('#profile-edit-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'update_profile');
        
        $.ajax({
            url: baseUrl + 'php/profile-handler.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showMessage(data.message, false);
                    var newName = $('#full_name').val();
                    $('.profile-header-info h2').text(newName);
                } else {
                    showMessage(data.message, true);
                }
            },
            error: function() {
                showMessage('Error updating profile. Please try again.', true);
            }
        });
    });
    
    // Load stats when page loads
    loadSellerStats();
});
</script>

</body>
</html>