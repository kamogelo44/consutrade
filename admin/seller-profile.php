<?php
/*
 * ConsuTrade - Seller Profile Page
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__) . '/init.php';

if (!$auth->isSeller()) {
    header('Location: login.php');
    exit;
}

$user_id = $currentUser->getUserId();
$user_name = $currentUser->getFullName();
$user_email = $currentUser->getEmail();
$user_phone = $currentUser->getPhone();
$user_location = $currentUser->getLocation();
$user_created_at = $currentUser->getCreatedAt();
$profile_image = $currentUser->getProfileImageUrl();
$is_verified = $currentUser->isIdVerified();
$verification = $currentUser->viewVerificationStatus();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Profile - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/admin.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
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
                        <h2><?php echo htmlspecialchars($user_name); ?></h2>
                        <div class="profile-badges">
                            <span class="role-badge seller-badge">Seller</span>
                            <?php if ($is_verified == 1): ?>
                                <span class="verification-badge verified">Verified Seller</span>
                            <?php else: ?>
                                <span class="verification-badge not-verified">Not Verified</span>
                            <?php endif; ?>
                            <span class="member-since">Member since <?php echo date($user_created_at); ?></span>
                        </div>
                        <p class="profile-email"><?php echo htmlspecialchars($user_email); ?></p>
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
                            <span class="stat-value"><?php echo htmlspecialchars($user_location ?? 'Not specified'); ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Phone Number</span>
                            <span class="stat-value"><?php echo htmlspecialchars($user_phone ?? 'Not specified'); ?></span>
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
                                <?php if ($is_verified == 1): ?>
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
                            <input type="text" id="fullName" name="full_name" value="<?php echo htmlspecialchars($user_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($user_email); ?>" disabled>
                            <small>Email cannot be changed</small>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_phone ?? ''); ?>" placeholder="e.g., 071 234 5678">
                            <small>Optional but recommended for order updates</small>
                        </div>
                        <div class="form-group">
                            <label for="location">Location (City, Province)</label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user_location ?? ''); ?>" placeholder="e.g., Johannesburg, Gauteng">
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
                    setTimeout(function() {
                        $('#errorMessage').fadeOut();
                    }, 5000);
                } else {
                    $('#flashMessage').text(message).show();
                    setTimeout(function() {
                        $('#flashMessage').fadeOut();
                    }, 5000);
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
                    reader.onload = function(e) {
                        $('#profileAvatar').attr('src', e.target.result);
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
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            }
                        },
                        error: function() {
                            showMessage('Error uploading image.', true);
                        }
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
                        if (data.success) {
                            showMessage(data.message, false);
                        } else {
                            showMessage(data.message, true);
                        }
                    },
                    error: function() {
                        showMessage('Error updating profile.', true);
                    }
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
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
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