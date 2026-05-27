<?php
/*
 * ConsuTrade - User Profile Page (Buyer)
 * Author: Kamogelo Phale
 * 
 * This page allows buyers to view and edit their profile information
 */

require_once __DIR__ . '/init.php';

$breadcrumbItems = [
    ['label' => 'My Profile']
];

$baseUrl = getBaseUrl();

// Check if user is logged in using centralized auth
if (!$is_logged_in) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Redirect sellers to their own profile page
if (($current_user['role'] ?? '') === 'seller') {
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
    
    <!-- Master Stylesheet -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    
    <style>
        /* ========== PROFILE PAGE SPECIFIC STYLES ========== */
        .profile-container { width: 100%; max-width: 100%; padding: var(--spacing-xl); min-height: calc(100vh - 200px); }
        
        /* User Header */
        .profile-user-header { display: flex; align-items: center; gap: var(--spacing-xl); background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); border-radius: var(--radius-lg); padding: var(--spacing-2xl) var(--spacing-xl); margin-bottom: var(--spacing-xl); color: var(--white); }
        .profile-user-avatar { position: relative; width: 120px; height: 120px; flex-shrink: 0; }
        .profile-user-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 4px solid var(--white); box-shadow: var(--shadow-md); }
        .avatar-upload-btn { position: absolute; bottom: 5px; right: 5px; background: var(--primary-color); border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all var(--transition-fast); border: 2px solid var(--white); }
        .avatar-upload-btn:hover { transform: scale(1.1); background: var(--primary-dark); }
        .avatar-upload-btn img { width: 16px; height: 16px; filter: brightness(0) invert(1); margin: 0; border: none; }
        .profile-user-info { flex: 1; }
        .profile-user-info h1 { font-size: var(--font-3xl); font-weight: var(--font-bold); margin-bottom: var(--spacing-sm); color: var(--white); }
        .profile-user-meta { display: flex; align-items: center; gap: var(--spacing-md); flex-wrap: wrap; }
        .role-badge { padding: 4px 12px; border-radius: var(--radius-round); font-size: var(--font-sm); font-weight: var(--font-medium); }
        .role-buyer { background: rgba(76, 175, 80, 0.9); color: var(--white); }
        .member-since { background: rgba(0,0,0,0.2); padding: 4px 12px; border-radius: var(--radius-round); font-size: var(--font-sm); }
        
        /* Messages */
        .success-message, .error-message { padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg); text-align: center; }
        .success-message { background: var(--success-light); color: var(--success); border-left: 4px solid var(--success); }
        .error-message { background: var(--error-light); color: var(--error); border-left: 4px solid var(--error); }
        
        /* Profile Content - Two Columns */
        .profile-content { display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--spacing-xl); margin-bottom: var(--spacing-xl); }
        
        /* Stats Card */
        .profile-stats-card, .profile-edit-card { background: var(--white); border-radius: var(--radius-lg); padding: var(--spacing-xl); box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); }
        .profile-stats-card h3, .profile-edit-card h3 { font-size: var(--font-xl); font-weight: var(--font-bold); margin-bottom: var(--spacing-lg); color: var(--dark-bg); padding-bottom: var(--spacing-sm); border-bottom: 2px solid var(--primary-color); }
        .stats-list { display: flex; flex-direction: column; gap: var(--spacing-md); }
        .stat-row { display: flex; justify-content: space-between; align-items: center; padding: var(--spacing-sm) 0; border-bottom: 1px solid var(--border-light); }
        .stat-label { font-weight: var(--font-medium); color: var(--gray-dark); }
        .stat-value { color: var(--gray-medium); }
        .stat-value.highlight { font-size: var(--font-xl); font-weight: var(--font-bold); color: var(--primary-color); }
        .stat-divider { height: 1px; background: var(--border-light); margin: var(--spacing-sm) 0; }
        
        /* Edit Form */
        .profile-edit-form { display: flex; flex-direction: column; gap: var(--spacing-md); }
        .form-group { display: flex; flex-direction: column; gap: var(--spacing-xs); }
        .form-group label { font-weight: var(--font-medium); color: var(--gray-dark); font-size: var(--font-sm); }
        .form-group input { padding: 10px 12px; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: var(--font-md); transition: all var(--transition-fast); }
        .form-group input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(255, 107, 0, 0.1); }
        .form-group input:disabled { background: var(--gray-bg); color: var(--gray-light); cursor: not-allowed; }
        .form-group small { color: var(--gray-light); font-size: var(--font-xs); }
        .form-actions { display: flex; gap: var(--spacing-md); margin-top: var(--spacing-md); }
        .save-btn { flex: 1; padding: 12px; background: var(--primary-color); color: var(--white); border: none; border-radius: var(--radius-md); font-weight: var(--font-bold); cursor: pointer; transition: all var(--transition-fast); }
        .save-btn:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .change-password-btn { flex: 1; padding: 12px; background: var(--white); color: var(--primary-color); border: 2px solid var(--primary-color); border-radius: var(--radius-md); font-weight: var(--font-bold); text-align: center; text-decoration: none; transition: all var(--transition-fast); }
        .change-password-btn:hover { background: var(--primary-fade); transform: translateY(-2px); }
        
        /* Danger Zone */
        .danger-zone { margin-top: var(--spacing-xl); }
        .danger-card { display: flex; justify-content: space-between; align-items: center; background: var(--error-light); border-radius: var(--radius-lg); padding: var(--spacing-lg); border: 1px solid var(--error); flex-wrap: wrap; gap: var(--spacing-md); }
        .danger-info h4 { color: var(--error); font-size: var(--font-lg); font-weight: var(--font-bold); margin-bottom: var(--spacing-xs); }
        .danger-info p { color: var(--gray-dark); font-size: var(--font-sm); }
        .delete-account-btn { padding: 10px 24px; background: var(--error); color: var(--white); border: none; border-radius: var(--radius-md); font-weight: var(--font-bold); cursor: pointer; transition: all var(--transition-fast); }
        .delete-account-btn:hover { background: var(--error-dark); transform: translateY(-2px); }
        
        /* Responsive */
        @media (max-width: 992px) {
            .profile-content { grid-template-columns: 1fr; gap: var(--spacing-lg); }
        }
        @media (max-width: 768px) {
            .profile-container { padding: var(--spacing-lg); }
            .profile-user-header { flex-direction: column; text-align: center; padding: var(--spacing-xl) var(--spacing-lg); }
            .profile-user-meta { justify-content: center; }
            .danger-card { flex-direction: column; text-align: center; }
            .form-actions { flex-direction: column; }
        }
        @media (max-width: 480px) {
            .profile-container { padding: var(--spacing-md); }
            .profile-user-avatar { width: 100px; height: 100px; }
            .profile-user-info h1 { font-size: var(--font-2xl); }
            .stat-value.highlight { font-size: var(--font-lg); }
        }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="profile-container">
    <?php include 'includes/breadcrumb.php'; ?>

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
                <span class="role-badge role-buyer">Buyer</span>
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

<?php include 'includes/footer.php'; ?>

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
    function showMessage(message, isError) {
        if (isError) {
            $('#error-message').text(message).show();
            setTimeout(function() { $('#error-message').fadeOut(); }, 5000);
        } else {
            $('#flash-message').text(message).show();
            setTimeout(function() { $('#flash-message').fadeOut(); }, 5000);
        }
    }
    
    function loadUserStats() {
        $.ajax({
            url: baseUrl + 'php/endpoints/get-user-stats.php?user_id=' + currentUserId,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $('#stat-orders').text(data.total_orders || 0);
                    $('#stat-spent').text('R ' + (data.total_spent || 0).toFixed(2));
                    if (data.pending_orders > 0) {
                        $('#stat-pending').text(data.pending_orders);
                        $('#pending-row').show();
                    }
                    if (data.reviews_written > 0) {
                        $('#stat-reviews').text(data.reviews_written);
                        $('#reviews-row').show();
                    }
                } else {
                    $('#stat-orders').text('0');
                    $('#stat-spent').text('R 0.00');
                }
            },
            error: function() {
                $('#stat-orders').text('0');
                $('#stat-spent').text('R 0.00');
            }
        });
    }
    
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
                url: baseUrl + 'php/endpoints/update-profile.php',
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
    
    $('.avatar-upload-btn').on('click', function(e) {
        e.preventDefault();
        $('#profile-image-upload').click();
    });
    
    $('#profile-edit-form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'update_profile');
        
        $.ajax({
            url: baseUrl + 'php/endpoints/update-profile.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showMessage(data.message, false);
                    $('.profile-user-info h1').text($('#full_name').val());
                } else {
                    showMessage(data.message, true);
                }
            },
            error: function() {
                showMessage('Error updating profile. Please try again.', true);
            }
        });
    });
    
    $('#delete-account-form').on('submit', function(e) {
        e.preventDefault();
        var password = $('#delete-password').val();
        if (!password) {
            alert('Please enter your password');
            return;
        }
        if (confirm('WARNING: This will permanently delete your account and all your data. Are you absolutely sure?')) {
            $.ajax({
                url: baseUrl + 'php/endpoints/delete-account.php',
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
    
    loadUserStats();
});
</script>

</body>
</html>