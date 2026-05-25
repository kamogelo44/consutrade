<?php
/*
 * ConsuTrade - Seller Profile Page
 * Author: Kamogelo Phale
 * 
 * Allows sellers to view and edit their profile information
 */

require_once dirname(__DIR__) . '/init.php';

if (!$auth->isSellerLoggedIn()) {
    header('Location: login.php');
    exit;
}

$baseUrl = getBaseUrl();
$user_id = $current_user_id;
$user = getUserById($conn, $user_id);
$profile_image = getUserProfileImage($user['profile_image'] ?? null);

// Get verification status
$verSql = "SELECT * FROM seller_verification WHERE seller_id = ?";
$verStmt = $conn->prepare($verSql);
$verStmt->bind_param('i', $user_id);
$verStmt->execute();
$verResult = $verStmt->get_result();
$verification = $verResult->fetch_assoc();
$verStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Profile - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-clean.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar-clean.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
    <script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
    <script>var baseUrl = '<?php echo $baseUrl; ?>';</script>
    <style>
        .page-header { margin-bottom: var(--spacing-xl); }
        .page-header h1 { font-size: var(--font-2xl); font-weight: var(--font-bold); margin-bottom: var(--spacing-xs); }
        .page-header p { color: var(--gray-medium); }
        .profile-header-card { background: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--border-light); padding: var(--spacing-xl); margin-bottom: var(--spacing-xl); }
        .profile-avatar-section { display: flex; align-items: center; gap: var(--spacing-xl); flex-wrap: wrap; }
        .profile-avatar-container { position: relative; width: 120px; height: 120px; }
        .profile-avatar-large { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; background: var(--primary-fade); border: 3px solid var(--primary-color); }
        .avatar-upload-btn { position: absolute; bottom: 5px; right: 5px; background: var(--primary-color); border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all var(--transition-fast); }
        .avatar-upload-btn:hover { background: var(--primary-dark); transform: scale(1.05); }
        .avatar-upload-btn img { width: 16px; height: 16px; filter: brightness(0) invert(1); }
        .profile-header-info h2 { font-size: var(--font-2xl); font-weight: var(--font-bold); margin-bottom: var(--spacing-sm); }
        .profile-badges { display: flex; gap: var(--spacing-sm); flex-wrap: wrap; margin-bottom: var(--spacing-sm); }
        .role-badge { padding: 4px 12px; border-radius: var(--radius-round); font-size: var(--font-xs); font-weight: var(--font-medium); }
        .seller-badge { background: var(--primary-fade); color: var(--primary-color); }
        .verification-badge { padding: 4px 12px; border-radius: var(--radius-round); font-size: var(--font-xs); font-weight: var(--font-medium); }
        .verification-badge.verified { background: var(--success-light); color: var(--success); }
        .verification-badge.not-verified { background: var(--warning-light); color: var(--warning); }
        .member-since { font-size: var(--font-xs); color: var(--gray-medium); display: inline-flex; align-items: center; }
        .profile-email { color: var(--gray-medium); font-size: var(--font-md); }
        .flash-message, .error-message { padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg); display: none; }
        .flash-message { background: var(--success-light); color: var(--success); border-left: 4px solid var(--success); }
        .error-message { background: var(--error-light); color: var(--error); border-left: 4px solid var(--error); }
        .profile-two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-xl); margin-bottom: var(--spacing-xl); }
        .profile-stats-card, .profile-edit-card { background: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--border-light); padding: var(--spacing-xl); }
        .profile-stats-card h3, .profile-edit-card h3 { font-size: var(--font-lg); font-weight: var(--font-semibold); margin-bottom: var(--spacing-lg); padding-bottom: var(--spacing-sm); border-bottom: 2px solid var(--primary-color); }
        .stats-list { display: flex; flex-direction: column; gap: var(--spacing-md); }
        .stat-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spacing-sm); }
        .stat-label { font-weight: var(--font-semibold); color: var(--gray-dark); }
        .stat-value { color: var(--gray-medium); }
        .stat-value.highlight { font-weight: var(--font-bold); color: var(--primary-color); font-size: var(--font-lg); }
        .stat-divider { height: 1px; background: var(--border-light); margin: var(--spacing-sm) 0; }
        .profile-edit-form .form-group { margin-bottom: var(--spacing-lg); }
        .profile-edit-form label { display: block; font-weight: var(--font-semibold); margin-bottom: var(--spacing-sm); color: var(--dark-bg); }
        .profile-edit-form input { width: 100%; padding: 10px 12px; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: var(--font-md); transition: all var(--transition-fast); }
        .profile-edit-form input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(255, 107, 0, 0.1); }
        .profile-edit-form input:disabled { background: var(--gray-bg-light); cursor: not-allowed; }
        .profile-edit-form small { display: block; margin-top: var(--spacing-xs); font-size: var(--font-xs); color: var(--gray-medium); }
        .form-actions { display: flex; gap: var(--spacing-sm); margin-top: var(--spacing-xl); flex-wrap: wrap; }
        .save-btn, .change-password-btn { padding: 10px 24px; border-radius: var(--radius-md); cursor: pointer; font-weight: var(--font-medium); transition: all var(--transition-fast); text-decoration: none; display: inline-block; text-align: center; }
        .save-btn { background: var(--primary-color); color: white; border: none; }
        .save-btn:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .change-password-btn { background: var(--gray-bg-light); color: var(--gray-dark); border: 1px solid var(--border-light); }
        .change-password-btn:hover { background: var(--gray-lighter); }
        .quick-actions-section { background: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--border-light); padding: var(--spacing-xl); }
        .quick-actions-section h3 { font-size: var(--font-lg); font-weight: var(--font-semibold); margin-bottom: var(--spacing-lg); padding-bottom: var(--spacing-sm); border-bottom: 2px solid var(--primary-color); }
        .quick-actions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: var(--spacing-md); }
        .quick-action-card { display: flex; align-items: center; gap: var(--spacing-md); padding: var(--spacing-md); background: var(--gray-bg-light); border-radius: var(--radius-md); text-decoration: none; transition: all var(--transition-fast); border: 1px solid transparent; }
        .quick-action-card:hover { background: var(--primary-fade); border-color: var(--primary-light); transform: translateX(4px); }
        .quick-action-icon { width: 48px; height: 48px; background: var(--white); border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .quick-action-icon img { width: 24px; height: 24px; filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(1577%) hue-rotate(350deg); }
        .quick-action-info { flex: 1; }
        .quick-action-info h4 { font-size: var(--font-base); font-weight: var(--font-semibold); color: var(--dark-bg); margin-bottom: var(--spacing-xs); }
        .quick-action-info p { font-size: var(--font-xs); color: var(--gray-medium); margin: 0; }
        .quick-action-arrow { font-size: var(--font-xl); color: var(--gray-light); transition: all var(--transition-fast); }
        .quick-action-card:hover .quick-action-arrow { transform: translateX(4px); color: var(--primary-color); }
        .verification-status-card { background: var(--gray-bg-light); border-radius: var(--radius-md); padding: var(--spacing-lg); }
        .verification-checks { display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md); margin-bottom: var(--spacing-lg); }
        .check-item { display: flex; justify-content: space-between; align-items: center; padding: var(--spacing-sm) var(--spacing-md); background: var(--white); border-radius: var(--radius-sm); }
        .check-label { font-weight: var(--font-medium); color: var(--gray-dark); }
        .check-status { font-weight: var(--font-bold); font-size: var(--font-lg); }
        .check-status.verified { color: var(--success); }
        .check-status.not-verified { color: var(--gray-light); }
        .current-document { margin-bottom: var(--spacing-lg); padding: var(--spacing-md); background: var(--white); border-radius: var(--radius-sm); }
        .text-success { color: var(--success); }
        .text-warning { color: var(--warning); }
        .upload-document-section h4 { font-size: var(--font-base); margin-bottom: var(--spacing-sm); }
        .upload-document-section p { font-size: var(--font-sm); color: var(--gray-medium); margin-bottom: var(--spacing-md); }
        .upload-document-section select, .upload-document-section input[type="file"] { width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: var(--font-md); margin-bottom: var(--spacing-sm); }
        @media (max-width: 1024px) { .profile-two-columns { grid-template-columns: 1fr; gap: var(--spacing-lg); } .quick-actions-grid { grid-template-columns: 1fr; } .verification-checks { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .profile-avatar-section { flex-direction: column; text-align: center; } .profile-badges { justify-content: center; } .form-actions { flex-direction: column; } .save-btn, .change-password-btn { width: 100%; } .stat-row { flex-direction: column; align-items: flex-start; } }
        @media (max-width: 480px) { .profile-header-card, .profile-stats-card, .profile-edit-card, .quick-actions-section { padding: var(--spacing-md); } .profile-header-info h2 { font-size: var(--font-xl); } }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<main class="seller-main-content">
    <div class="dashboard-content">
        <div class="page-header">
            <h1>My Profile</h1>
            <p>View and manage your seller account information</p>
        </div>

        <div id="flashMessage" class="flash-message" style="display: none;"></div>
        <div id="errorMessage" class="error-message" style="display: none;"></div>

        <div class="profile-header-card">
            <div class="profile-avatar-section">
                <div class="profile-avatar-container">
                    <img src="<?php echo $profile_image; ?>" alt="Profile Avatar" id="profileAvatar" class="profile-avatar-large" onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
                    <label for="profileImageUpload" class="avatar-upload-btn" title="Change profile picture">
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

        <form id="profileImageForm" style="display: none;">
            <input type="file" name="profile_image" id="profileImageUpload" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
        </form>

        <div class="profile-two-columns">
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
                        <span class="stat-value highlight" id="statProducts">-</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Orders Completed</span>
                        <span class="stat-value highlight" id="statSales">-</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Total Revenue</span>
                        <span class="stat-value highlight" id="statRevenue">-</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Seller Rating</span>
                        <span class="stat-value highlight" id="statRating">-</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-row">
                        <span class="stat-label">Account Status</span>
                        <span class="stat-value">
                            <?php if ($user['id_verified'] == 1): ?>
                                <span style="color: var(--success);">Verified</span>
                            <?php else: ?>
                                <span style="color: var(--warning);">Pending Verification</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="profile-edit-card">
                <h3>Edit Profile Information</h3>
                <form id="profileEditForm" class="profile-edit-form">
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
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

        <!-- Verification Section -->
        <div class="quick-actions-section" style="margin-top: var(--spacing-xl);">
            <h3>Seller Verification</h3>
            <div class="verification-status-card">
                <div class="verification-checks">
                    <div class="check-item">
                        <span class="check-label">Email Verified</span>
                        <span class="check-status <?php echo ($verification && $verification['email_verified']) ? 'verified' : 'not-verified'; ?>">
                            <?php echo ($verification && $verification['email_verified']) ? '✓' : '✗'; ?>
                        </span>
                    </div>
                    <div class="check-item">
                        <span class="check-label">Phone Verified</span>
                        <span class="check-status <?php echo ($verification && $verification['phone_verified']) ? 'verified' : 'not-verified'; ?>">
                            <?php echo ($verification && $verification['phone_verified']) ? '✓' : '✗'; ?>
                        </span>
                    </div>
                    <div class="check-item">
                        <span class="check-label">Document Verified</span>
                        <span class="check-status <?php echo ($verification && $verification['document_verified']) ? 'verified' : 'not-verified'; ?>">
                            <?php echo ($verification && $verification['document_verified']) ? '✓' : '✗'; ?>
                        </span>
                    </div>
                    <div class="check-item">
                        <span class="check-label">Location Verified</span>
                        <span class="check-status <?php echo ($verification && $verification['location_verified']) ? 'verified' : 'not-verified'; ?>">
                            <?php echo ($verification && $verification['location_verified']) ? '✓' : '✗'; ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($verification && $verification['document_path']): ?>
                    <div class="current-document">
                        <p><strong>Document Uploaded:</strong> <?php echo htmlspecialchars($verification['document_type'] ?? 'ID Document'); ?></p>
                        <p class="document-status <?php echo $verification['document_verified'] ? 'text-success' : 'text-warning'; ?>">
                            Status: <?php echo $verification['document_verified'] ? 'Verified' : 'Pending Review'; ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <div class="upload-document-section">
                    <h4>Upload Verification Document</h4>
                    <p>Upload your ID, business registration, or proof of address.</p>
                    <form id="verificationForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <select name="document_type" id="documentType" required>
                                <option value="">Select Document Type</option>
                                <option value="id">ID Document</option>
                                <option value="business_reg">Business Registration</option>
                                <option value="proof_address">Proof of Address</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <input type="file" name="document" id="verificationDoc" accept="image/jpeg,image/jpg,image/png,application/pdf" required>
                            <small>Accepted: JPG, PNG, PDF (max 5MB)</small>
                        </div>
                        <button type="submit" class="save-btn">Upload Document</button>
                    </form>
                    <div id="verificationMessage" style="margin-top: var(--spacing-md); display: none;"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
$(function() {
    function showMessage(message, isError) {
        if (isError) {
            $('#errorMessage').text(message).show();
            setTimeout(function() { $('#errorMessage').fadeOut(); }, 5000);
        } else {
            $('#flashMessage').text(message).show();
            setTimeout(function() { $('#flashMessage').fadeOut(); }, 5000);
        }
    }
    
    function loadSellerStats() {
        $.ajax({
            url: baseUrl + 'php/get-user-stats.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $('#statProducts').text(data.total_products || 0);
                    $('#statSales').text(data.total_sales || 0);
                    $('#statRevenue').text('R ' + (data.total_revenue || 0).toFixed(2));
                    if (data.total_reviews > 0) {
                        $('#statRating').html(data.avg_rating + ' / 5 <span style="font-size: 12px; color: var(--gray-light);">(' + data.total_reviews + ' reviews)</span>');
                    } else {
                        $('#statRating').html('No reviews yet');
                    }
                }
            },
            error: function() {
                $('#statProducts').text('0');
                $('#statSales').text('0');
                $('#statRevenue').text('R 0.00');
                $('#statRating').text('No reviews yet');
            }
        });
    }
    
    $('#profileImageUpload').on('change', function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) { $('#profileAvatar').attr('src', e.target.result); };
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
                    if (data.success) { showMessage(data.message, false); }
                    else { showMessage(data.message, true); setTimeout(function() { location.reload(); }, 2000); }
                },
                error: function() { showMessage('Error uploading image.', true); }
            });
        }
    });
    
    $('.avatar-upload-btn').on('click', function(e) {
        e.preventDefault();
        $('#profileImageUpload').click();
    });
    
    $('#profileEditForm').on('submit', function(e) {
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
                if (data.success) { showMessage(data.message, false); }
                else { showMessage(data.message, true); }
            },
            error: function() { showMessage('Error updating profile.', true); }
        });
    });
    
    // Verification document upload
    $('#verificationForm').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        var $msg = $('#verificationMessage');
        
        $.ajax({
            url: baseUrl + 'php/endpoints/upload-verification.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(data) {
                $msg.show().removeClass('flash-message error-message');
                if (data.success) {
                    $msg.addClass('flash-message').text(data.message);
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    $msg.addClass('error-message').text(data.message);
                }
            },
            error: function() {
                $msg.show().addClass('error-message').text('Could not upload document.');
            }
        });
    });
    
    loadSellerStats();
});
</script>

</body>
</html>