<?php
/*
 * ConsuTrade - User Profile Page (Buyer)
 * Author: Kamogelo Phale
 * 
 * This page allows buyers to view and edit their profile information
 */

require_once __DIR__ . '/init.php';

$baseUrl = getBaseUrl();

// Check if user is logged in using centralized auth
if (!$is_logged_in) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Redirect sellers to their own profile page
if ($current_user['role'] === 'seller') {
    header('Location: ' . $baseUrl . 'admin/seller-profile.php');
    exit;
}

$user_id = $current_user_id;

// Get user data using helper
$user = getUserById($conn, $user_id);

if (!$user) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Set profile image path using helper
$profile_image = getUserProfileImage($user['profile_image'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    
    <!-- Master Stylesheet (includes all CSS) -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">

</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="profile-container">
    <!-- Breadcrumb Navigation -->
    <div class="breadcrumb">
        <a href="<?php echo $baseUrl; ?>index.php">Home</a>
        <span class="breadcrumb-separator">›</span>
        <span class="current-page">My Profile</span>
    </div>

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
                <span class="role-badge role-buyer">
                    Buyer
                </span>
                <span class="member-since">Member since <?php echo formatDate($user['created_at']); ?></span>
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
            <h3>Buyer Statistics</h3>
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
                
                <div class="form-actions">
                    <button type="submit" class="save-btn">Save Changes</button>
                    <a href="<?php echo $baseUrl; ?>change-password.php" class="change-password-btn">Change Password</a>
                </div>
            </form>
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
                <button class="btn-close" onclick="closeDeleteModal()"></button>
            </div>
            <form id="delete-account-form">
                <p>Are you sure you want to delete your account?</p>
                <p class="warning-text">This action cannot be undone. All your data will be permanently removed.</p>
                <div class="input-group">
                    <label for="delete-password">Enter your password to confirm</label>
                    <div class="password-field-wrapper">
                        <input type="password" id="delete-password" name="password" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('delete-password', this)">
                            <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="18" height="18">
                        </button>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="delete-cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="delete-confirm-btn">Confirm Delete</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
/*
 * ConsuTrade - Profile Page Functionality
 * Author: Kamogelo Phale
 */
var baseUrl = '<?php echo $baseUrl; ?>';
var currentUserId = <?php echo $current_user_id; ?>;

function showDeleteModal() {
    openModal($('#delete-modal'));
}

function closeDeleteModal() {
    closeModal($('#delete-modal'));
}

$(function() {
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
    
    // Load buyer statistics via AJAX
    function loadUserStats() {
        $.ajax({
            url: baseUrl + 'php/get-user-stats.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    updateStatsDisplay(data);
                }
            },
            error: function() {
                $('#stat-orders').text('0');
                $('#stat-spent').text('R 0.00');
            }
        });
    }
    
    function updateStatsDisplay(stats) {
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
                    showMessage(data.message, false);
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
    
    // Delete account form submission
    $('#delete-account-form').on('submit', function(e) {
        e.preventDefault();
        
        var password = $('#delete-password').val();
        
        if (!password) {
            alert('Please enter your password');
            return;
        }
        
        if (confirm('WARNING: This will permanently delete your account and all your data. Are you absolutely sure?')) {
            $.ajax({
                url: baseUrl + 'php/delete-account.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ password: password }),
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        alert('Your account has been deleted. You will be redirected to the homepage.');
                        window.location.href = baseUrl;
                    } else {
                        alert('Error: ' + data.message);
                    }
                },
                error: function() {
                    alert('Something went wrong. Please try again.');
                }
            });
        }
    });
    
    // Load stats when page loads
    loadUserStats();
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>