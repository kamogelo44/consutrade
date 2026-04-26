<?php
/*
 * ConsuTrade - Seller Profile Page
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__) . '/php/helpers.php';

// Check if seller is logged in
if (!isSellerLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Start seller session
startSession('seller');

$baseUrl = getBaseUrl();

// Get database connection
require_once dirname(__DIR__) . '/php/config.php';

$user_id = $_SESSION['user_id'];

// Get user data using helper
$user = getUserById($conn, $user_id);

if (!$user) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Set profile image path using helper
$profile_image = getUserProfileImage($user['profile_image']);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Profile - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/seller-profile.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';
    </script>
</head>
<body class="seller-dashboard-page">

<?php include 'includes/sidebar.php'; ?>

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
                            <span class="member-since">Member since <?php echo formatDate($user['created_at']); ?></span>
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
                    <a href="add-product.php" class="quick-action-card">
                        <div class="quick-action-icon">
                            <img src="<?php echo $baseUrl; ?>images/icons/add-svgrepo-com.svg" alt="Add Product">
                        </div>
                        <div class="quick-action-info">
                            <h4>Add New Product</h4>
                            <p>List a new product for sale</p>
                        </div>
                        <span class="quick-action-arrow">→</span>
                    </a>
                    <a href="my-products.php" class="quick-action-card">
                        <div class="quick-action-icon">
                            <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="My Products">
                        </div>
                        <div class="quick-action-info">
                            <h4>Manage Products</h4>
                            <p>Edit or remove existing products</p>
                        </div>
                        <span class="quick-action-arrow">→</span>
                    </a>
                    <a href="my-orders.php" class="quick-action-card">
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
<script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
<script>
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