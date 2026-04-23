<?php
/*
 * ConsuTrade - User Profile Page
 * Author: Kamogelo Phale
 * 
 * This page allows users to view and edit their profile information
 */

session_start();

require_once 'php/config.php';
require_once 'php/helpers.php';

$baseUrl = getBaseUrl();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

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

// Set profile image path - using helper
$profile_image = getUserProfileImage($user['profile_image'] ?? null);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/header.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/animations.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/footer.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/profile.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/login-signup.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="profile-container">
    <!-- User Profile Header -->
    <div class="profile-user-header">
        <div class="profile-user-avatar">
            <img src="<?php echo $profile_image; ?>" alt="Profile Avatar" id="profile-avatar">
            <label for="profile-image-upload" class="avatar-upload-btn" title="Change profile picture">
                <img src="<?php echo $baseUrl; ?>images/icons/camera-svgrepo-com.svg" alt="Upload">
            </label>
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
    
    <!-- Hidden file input for profile image -->
    <form id="profile-image-form" style="display: none;">
        <input type="file" name="profile_image" id="profile-image-upload" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
    </form>

    <!-- Flash Messages -->
    <div id="flash-message" class="success-message" style="display: none;"></div>
    <div id="error-message" class="error-message" style="display: none;"></div>

    <!-- Profile Content -->
    <div class="profile-content">
        <!-- Left Column - Profile Stats -->
        <div class="profile-stats-card">
            <h3><?php echo $role === 'seller' ? 'Seller Statistics' : 'Buyer Statistics'; ?></h3>
            <div class="stats-list">
                <div class="stat-row">
                    <span class="stat-label">Email Address</span>
                    <span class="stat-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                
                <?php if ($user['location']): ?>
                <div class="stat-row">
                    <span class="stat-label">Location</span>
                    <span class="stat-value"><?php echo htmlspecialchars($user['location']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($user['phone']): ?>
                <div class="stat-row">
                    <span class="stat-label">Phone Number</span>
                    <span class="stat-value"><?php echo htmlspecialchars($user['phone']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($role === 'seller'): ?>
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
                <?php else: ?>
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
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column - Edit Form -->
        <div class="profile-edit-card">
            <h3>Edit Profile</h3>
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
                
                <?php if ($user['role'] === 'seller'): ?>
                <div class="form-group">
                    <label for="location">Location (City, Province)</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" placeholder="e.g., Johannesburg, Gauteng">
                    <small>Your location helps buyers find your products</small>
                </div>
                <?php endif; ?>
                
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

<script>
var baseUrl = '<?php echo $baseUrl; ?>';
var userRole = '<?php echo $role; ?>';

$(document).ready(function() {
    // Show flash message
    function showMessage(message, isError = false) {
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
    
    // Load user statistics via AJAX
    function loadUserStats() {
        $.ajax({
            url: baseUrl + 'php/get-user-stats.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    updateStatsDisplay(data.stats);
                } else {
                    console.log('Failed to load stats');
                }
            },
            error: function() {
                console.log('Error loading stats');
                // Set fallback values
                if (userRole === 'seller') {
                    $('#stat-products').text('0');
                    $('#stat-sales').text('0');
                    $('#stat-revenue').text('R 0.00');
                    $('#stat-rating').text('No reviews yet');
                } else {
                    $('#stat-orders').text('0');
                    $('#stat-spent').text('R 0.00');
                }
            }
        });
    }
    
    function updateStatsDisplay(stats) {
        if (userRole === 'seller') {
            $('#stat-products').text(stats.total_products || 0);
            $('#stat-sales').text(stats.total_sales || 0);
            $('#stat-revenue').text('R ' + (stats.total_revenue || 0).toFixed(2));
            if (stats.total_reviews > 0) {
                $('#stat-rating').html(stats.avg_rating + ' / 5 <span style="font-size: 12px; color: var(--gray-light);">(' + stats.total_reviews + ' reviews)</span>');
            } else {
                $('#stat-rating').html('No reviews yet');
            }
        } else {
            $('#stat-orders').text(stats.total_orders || 0);
            $('#stat-spent').text('R ' + (stats.total_spent || 0).toFixed(2));
            if (stats.pending_orders > 0) {
                $('#stat-pending').text(stats.pending_orders);
                $('#pending-row').show();
            }
            if (stats.reviews_written > 0) {
                $('#stat-reviews').text(stats.reviews_written);
                $('#reviews-row').show();
            }
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
                        showMessage(data.message);
                    } else {
                        showMessage(data.message, true);
                        location.reload();
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
                    showMessage(data.message);
                    var newName = $('#full_name').val();
                    $('.profile-user-info h1').text(newName);
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
    loadUserStats();
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>