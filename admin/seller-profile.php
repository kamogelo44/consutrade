<?php
/*
 * ConsuTrade - Seller Profile Page
 * Author: Kamogelo Phale
 * 
 * Displays seller profile with verification document upload (3 states):
 * - State 1: No document uploaded
 * - State 2: Document pending review
 * - State 3: Document verified
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
$is_verified = $currentUser->isVerified();

// Get verification data from SellerVerification domain object
$verificationObj = $currentUser->getVerification();
$hasDocument = false;
$documentType = '';
$documentVerified = false;
$documentPath = '';
$submittedAt = '';

if ($verificationObj) {
    $hasDocument = true;
    $documentType = $verificationObj->getDocumentType();
    $documentVerified = $verificationObj->isVerified();
    $documentPath = $verificationObj->getDocumentPath();
    $submittedAt = $verificationObj->getSubmittedAt();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Seller Profile - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-layout.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <style>
        /* ========== PROFILE PAGE STYLES ========== */
        .seller-main-content {
            margin-left: 280px;
            padding: var(--spacing-xl);
            min-height: 100vh;
            background: var(--gray-bg);
            transition: margin-left var(--transition-normal);
        }

        .dashboard-content {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: var(--spacing-xl);
        }

        .page-header h1 {
            font-size: var(--font-2xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-xs);
            color: var(--dark-bg);
        }

        .page-header p {
            color: var(--gray-medium);
        }

        /* Profile Header Card */
        .profile-header-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
        }

        .profile-avatar-section {
            display: flex;
            align-items: center;
            gap: var(--spacing-xl);
            flex-wrap: wrap;
        }

        .profile-avatar-container {
            position: relative;
            width: 120px;
            height: 120px;
        }

        .profile-avatar-large {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            background: var(--primary-fade);
            border: 3px solid var(--primary-color);
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
        }

        .avatar-upload-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        .avatar-upload-btn img {
            width: 16px;
            height: 16px;
            filter: brightness(0) invert(1);
        }

        .profile-header-info h2 {
            font-size: var(--font-2xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-sm);
        }

        .profile-badges {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
            margin-bottom: var(--spacing-sm);
        }

        .role-badge {
            padding: 4px 12px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
        }

        .seller-badge {
            background: var(--primary-fade);
            color: var(--primary-color);
        }

        .verification-badge.verified {
            background: var(--success-light);
            color: var(--success);
            padding: 4px 12px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
        }

        .verification-badge.not-verified {
            background: var(--warning-light);
            color: var(--warning);
            padding: 4px 12px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
        }

        .verification-badge.pending {
            background: var(--info-light);
            color: var(--info);
            padding: 4px 12px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
        }

        .member-since {
            font-size: var(--font-xs);
            color: var(--gray-medium);
        }

        .profile-email {
            color: var(--gray-medium);
            font-size: var(--font-md);
        }

        /* Two Column Layout */
        .profile-two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
        }

        .profile-stats-card,
        .profile-edit-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            padding: var(--spacing-xl);
        }

        .profile-stats-card h3,
        .profile-edit-card h3 {
            font-size: var(--font-lg);
            font-weight: var(--font-semibold);
            margin-bottom: var(--spacing-lg);
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
            flex-wrap: wrap;
            gap: var(--spacing-sm);
        }

        .stat-label {
            font-weight: var(--font-semibold);
            color: var(--gray-dark);
        }

        .stat-value {
            color: var(--gray-medium);
        }

        .stat-value.highlight {
            font-weight: var(--font-bold);
            color: var(--primary-color);
            font-size: var(--font-lg);
        }

        .stat-divider {
            height: 1px;
            background: var(--border-light);
            margin: var(--spacing-sm) 0;
        }

        /* Edit Form */
        .profile-edit-form .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .profile-edit-form label {
            display: block;
            font-weight: var(--font-semibold);
            margin-bottom: var(--spacing-sm);
            color: var(--dark-bg);
        }

        .profile-edit-form input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: var(--font-md);
            transition: all var(--transition-fast);
        }

        .profile-edit-form input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 0, 0.1);
        }

        .profile-edit-form input:disabled {
            background: var(--gray-bg-light);
            cursor: not-allowed;
        }

        .profile-edit-form small {
            display: block;
            margin-top: var(--spacing-xs);
            font-size: var(--font-xs);
            color: var(--gray-medium);
        }

        .form-actions {
            display: flex;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-xl);
            flex-wrap: wrap;
        }

        .save-btn {
            background: var(--primary-color);
            color: var(--white);
            padding: 10px 24px;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
        }

        .save-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .change-password-btn {
            background: var(--gray-bg-light);
            color: var(--gray-dark);
            padding: 10px 24px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
            text-decoration: none;
            display: inline-block;
        }

        .change-password-btn:hover {
            background: var(--gray-lighter);
        }

        /* Quick Actions */
        .quick-actions-section {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            padding: var(--spacing-xl);
            margin-top: var(--spacing-xl);
        }

        .quick-actions-section h3 {
            font-size: var(--font-lg);
            font-weight: var(--font-semibold);
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-sm);
            border-bottom: 2px solid var(--primary-color);
        }

        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
            width: 48px;
            height: 48px;
            background: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quick-action-icon img {
            width: 24px;
            height: 24px;
            filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(1577%) hue-rotate(350deg);
        }

        .quick-action-info h4 {
            font-size: var(--font-base);
            font-weight: var(--font-semibold);
            color: var(--dark-bg);
            margin-bottom: var(--spacing-xs);
        }

        .quick-action-info p {
            font-size: var(--font-xs);
            color: var(--gray-medium);
            margin: 0;
        }

        .quick-action-arrow {
            font-size: var(--font-xl);
            color: var(--gray-light);
            transition: all var(--transition-fast);
        }

        .quick-action-card:hover .quick-action-arrow {
            transform: translateX(4px);
            color: var(--primary-color);
        }

        /* ============================================================
       VERIFICATION 3 STATES
       ============================================================ */

        .verification-status-card {
            background: var(--gray-bg-light);
            border-radius: var(--radius-md);
            padding: var(--spacing-lg);
        }

        .verification-state {
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            background: var(--white);
        }

        .verification-state.verified {
            border-left: 4px solid var(--success);
        }

        .verification-state.pending {
            border-left: 4px solid var(--info);
        }

        .verification-state.no-document {
            border-left: 4px solid var(--warning);
        }

        .state-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-md);
            padding-bottom: var(--spacing-sm);
            border-bottom: 1px solid var(--border-light);
            flex-wrap: wrap;
            gap: var(--spacing-sm);
        }

        .state-header h4 {
            font-size: var(--font-base);
            font-weight: var(--font-semibold);
            margin: 0;
        }

        .state-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
        }

        .state-badge.verified {
            background: var(--success-light);
            color: var(--success);
        }

        .state-badge.pending {
            background: var(--info-light);
            color: var(--info);
        }

        .state-badge.not-verified {
            background: var(--warning-light);
            color: var(--warning);
        }

        .state-badge img {
            vertical-align: middle;
        }

        .state-content {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }

        /* ============================================================
       DOCUMENT FILE DISPLAY
       ============================================================ */

        .document-info p {
            margin: var(--spacing-xs) 0;
        }

        .document-file {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: var(--spacing-sm) var(--spacing-md);
            background: var(--gray-bg-light);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-light);
            margin: var(--spacing-sm) 0;
            flex-wrap: wrap;
        }

        .document-file .file-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: var(--font-medium);
            word-break: break-all;
        }

        .document-file .file-link:hover {
            text-decoration: underline;
        }

        .document-file .file-size {
            font-size: var(--font-xs);
            color: var(--gray-medium);
        }

        /* ============================================================
       DOCUMENT ACTIONS
       ============================================================ */

        .document-actions {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
            margin-top: var(--spacing-sm);
        }

        .document-actions button {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 16px;
            border-radius: var(--radius-sm);
            font-size: var(--font-xs);
            cursor: pointer;
            border: 1px solid var(--border-light);
            background: var(--white);
            transition: all var(--transition-fast);
            font-weight: var(--font-medium);
        }

        .document-actions button img {
            vertical-align: middle;
        }

        .document-actions .preview-btn {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .document-actions .preview-btn:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        .document-actions .preview-btn:hover img {
            filter: brightness(0) invert(1);
        }

        .document-actions .replace-btn {
            border-color: var(--info);
            color: var(--info);
        }

        .document-actions .replace-btn:hover {
            background: var(--info);
            color: var(--white);
        }

        .document-actions .replace-btn:hover img {
            filter: brightness(0) invert(1);
        }

        .document-actions .delete-btn {
            border-color: var(--error);
            color: var(--error);
        }

        .document-actions .delete-btn:hover {
            background: var(--error);
            color: var(--white);
        }

        .document-actions .delete-btn:hover img {
            filter: brightness(0) invert(1);
        }

        /* ============================================================
       VERIFICATION STATUS
       ============================================================ */

        .verification-status {
            margin-top: var(--spacing-sm);
            padding-top: var(--spacing-sm);
            border-top: 1px solid var(--border-light);
        }

        .verification-status p {
            margin: var(--spacing-xs) 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .text-success {
            color: var(--success);
        }

        .text-warning {
            color: var(--warning);
        }

        .text-info {
            color: var(--info);
        }

        .text-muted {
            color: var(--gray-medium);
        }

        /* ============================================================
       UPLOAD AREA (State 1 - No Document)
       ============================================================ */

        .upload-area {
            margin-top: var(--spacing-md);
        }

        .drop-zone {
            border: 2px dashed var(--border-light);
            border-radius: var(--radius-md);
            padding: var(--spacing-xl);
            text-align: center;
            cursor: pointer;
            transition: all var(--transition-fast);
            margin-bottom: var(--spacing-md);
        }

        .drop-zone:hover {
            border-color: var(--primary-color);
            background: var(--primary-fade);
        }

        .drop-zone.dragover {
            border-color: var(--primary-color);
            background: var(--primary-fade);
        }

        .drop-zone .file-types {
            font-size: var(--font-xs);
            color: var(--gray-medium);
            margin-top: var(--spacing-xs);
        }

        .file-name-display {
            color: var(--primary-color);
            font-weight: bold;
            margin-top: var(--spacing-sm);
            font-size: var(--font-sm);
        }

        .drop-zone .upload-icon {
            opacity: 0.5;
            margin-bottom: 8px;
        }

        .help-text {
            font-size: var(--font-xs);
            color: var(--gray-medium);
            margin-top: var(--spacing-sm);
            font-style: italic;
        }

        /* ============================================================
       RESPONSIVE
       ============================================================ */

        @media (max-width: 1024px) {
            .seller-main-content {
                margin-left: 0;
                width: 100%;
                padding: var(--spacing-md);
                padding-top: 70px;
            }

            .profile-two-columns {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg);
            }

            .quick-actions-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .seller-main-content {
                padding: var(--spacing-md);
                padding-top: 70px;
            }

            .profile-avatar-section {
                flex-direction: column;
                text-align: center;
            }

            .profile-badges {
                justify-content: center;
            }

            .form-actions {
                flex-direction: column;
            }

            .save-btn,
            .change-password-btn {
                width: 100%;
                text-align: center;
            }

            .stat-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .state-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .document-actions {
                flex-direction: column;
                width: 100%;
            }

            .document-actions button {
                width: 100%;
                justify-content: center;
            }

            .document-file {
                flex-wrap: wrap;
            }

            .drop-zone {
                padding: var(--spacing-lg);
            }
        }

        @media (max-width: 480px) {
            .seller-main-content {
                padding: var(--spacing-sm);
                padding-top: 60px;
            }

            .page-header h1 {
                font-size: var(--font-xl);
            }

            .profile-header-card,
            .profile-stats-card,
            .profile-edit-card,
            .quick-actions-section {
                padding: var(--spacing-md);
            }

            .profile-header-info h2 {
                font-size: var(--font-xl);
            }

            .verification-state {
                padding: var(--spacing-md);
            }

            .document-actions button {
                font-size: var(--font-xs);
                padding: 6px 12px;
            }

            .document-file {
                padding: var(--spacing-xs) var(--spacing-sm);
            }
        }
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

            <!-- ============================================================
            PROFILE HEADER
            ============================================================ -->

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
                            <?php if ($is_verified): ?>
                                <span class="verification-badge verified">Verified Seller</span>
                            <?php elseif ($hasDocument && !$documentVerified): ?>
                                <span class="verification-badge pending">Pending Review</span>
                            <?php else: ?>
                                <span class="verification-badge not-verified">Not Verified</span>
                            <?php endif; ?>
                            <span class="member-since">Member since <?php echo date('d M Y', strtotime($user_created_at)); ?></span>
                        </div>
                        <p class="profile-email"><?php echo htmlspecialchars($user_email); ?></p>
                    </div>
                </div>
            </div>

            <form id="profileImageForm" style="display: none;">
                <input type="file" name="profile_image" id="profileImageUpload" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
            </form>

            <!-- ============================================================
            TWO COLUMN: STATS + EDIT PROFILE
            ============================================================ -->

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
                            <span class="stat-label">Completed Orders</span>
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
                                <?php if ($is_verified): ?>
                                    <span style="color: var(--success);">Verified</span>
                                <?php elseif ($hasDocument && !$documentVerified): ?>
                                    <span style="color: var(--info);">Pending Review</span>
                                <?php else: ?>
                                    <span style="color: var(--warning);">Not Verified</span>
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

            <!-- ============================================================
            QUICK ACTIONS
            ============================================================ -->

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
                    <a href="seller-orders.php" class="quick-action-card">
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

            <!-- VERIFICATION SECTION - 3 STATES -->

            <!-- VERIFICATION SECTION - 3 STATES -->

            <div class="quick-actions-section">
                <h3>Seller Verification</h3>
                <div class="verification-status-card">

                    <?php if ($hasDocument && $documentVerified): ?>
                        <!--STATE 3: VERIFIED -->
                        <div class="verification-state verified">
                            <div class="state-header">
                                <h4>Verification Document</h4>
                                <span class="state-badge verified">
                                    <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="14" height="14" alt="Verified">
                                    Verified
                                </span>
                            </div>
                            <div class="state-content">
                                <div class="document-info">
                                    <p><strong>Document:</strong> <?php echo ucfirst(str_replace('_', ' ', $documentType)); ?></p>

                                    <?php if (!empty($documentPath)):
                                        $docUrl = $baseUrl . $documentPath;
                                        $filename = basename($documentPath);
                                    ?>
                                        <div class="document-file">
                                            <img src="<?php echo $baseUrl; ?>images/icons/document-svgrepo-com.svg" width="24" height="24" style="vertical-align: middle; margin-right: 8px;" alt="Document">
                                            <a href="<?php echo $docUrl; ?>" target="_blank" class="file-link">
                                                <?php echo htmlspecialchars($filename); ?>
                                            </a>
                                            <span class="file-size">(<?php
                                                                        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $documentPath;
                                                                        if (file_exists($filePath)) {
                                                                            $size = filesize($filePath);
                                                                            if ($size < 1024) {
                                                                                echo $size . ' B';
                                                                            } elseif ($size < 1048576) {
                                                                                echo round($size / 1024, 1) . ' KB';
                                                                            } else {
                                                                                echo round($size / 1048576, 1) . ' MB';
                                                                            }
                                                                        } else {
                                                                            echo 'File not found';
                                                                        }
                                                                        ?>)
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="document-actions">
                                        <?php if (!empty($documentPath)): ?>
                                            <button class="preview-btn" onclick="window.open('<?php echo $docUrl; ?>', '_blank')">
                                                <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="14" height="14" style="vertical-align: middle; margin-right: 4px;" alt="View">
                                                View Document
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="verification-status">
                                    <p class="text-success">
                                        <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16" height="16" style="vertical-align: middle; margin-right: 6px;" alt="Verified">
                                        <strong>Status:</strong> Verified
                                    </p>
                                    <p><strong>Verified on:</strong> <?php echo date('d M Y, h:i A', strtotime($verificationObj->getReviewedAt() ?? 'now')); ?></p>
                                    <p class="text-success">
                                        <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16" height="16" style="vertical-align: middle; margin-right: 6px;" alt="Verified">
                                        Your account has been successfully verified!
                                    </p>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($hasDocument && !$documentVerified): ?>
                        <!-- STATE 2: PENDING REVIEW -->
                        <div class="verification-state pending">
                            <div class="state-header">
                                <h4>Verification Document</h4>
                                <span class="state-badge pending">
                                    <img src="<?php echo $baseUrl; ?>images/icons/clock-svgrepo-com.svg" width="14" height="14" alt="Pending">
                                    Pending Review
                                </span>
                            </div>
                            <div class="state-content">
                                <div class="document-info">
                                    <p><strong>Document:</strong> <?php echo ucfirst(str_replace('_', ' ', $documentType)); ?></p>

                                    <?php if (!empty($documentPath)):
                                        $docUrl = $baseUrl . $documentPath;
                                        $filename = basename($documentPath);
                                    ?>
                                        <div class="document-file">
                                            <img src="<?php echo $baseUrl; ?>images/icons/document-svgrepo-com.svg" width="24" height="24" style="vertical-align: middle; margin-right: 8px;" alt="Document">
                                            <a href="<?php echo $docUrl; ?>" target="_blank" class="file-link">
                                                <?php echo htmlspecialchars($filename); ?>
                                            </a>
                                            <span class="file-size">(<?php
                                                                        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $documentPath;
                                                                        if (file_exists($filePath)) {
                                                                            $size = filesize($filePath);
                                                                            if ($size < 1024) {
                                                                                echo $size . ' B';
                                                                            } elseif ($size < 1048576) {
                                                                                echo round($size / 1024, 1) . ' KB';
                                                                            } else {
                                                                                echo round($size / 1048576, 1) . ' MB';
                                                                            }
                                                                        } else {
                                                                            echo 'File not found';
                                                                        }
                                                                        ?>)
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="document-actions">
                                        <?php if (!empty($documentPath)): ?>
                                            <button class="preview-btn" onclick="window.open('<?php echo $docUrl; ?>', '_blank')">
                                                <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="14" height="14" style="vertical-align: middle; margin-right: 4px;" alt="View">
                                                View Document
                                            </button>
                                        <?php endif; ?>
                                        <button class="replace-btn" onclick="document.getElementById('replaceDocInput').click()">
                                            <img src="<?php echo $baseUrl; ?>images/icons/add-svgrepo-com.svg" width="14" height="14" style="vertical-align: middle; margin-right: 4px;" alt="Replace">
                                            Replace
                                        </button>
                                        <button class="delete-btn" onclick="deleteDocument()">
                                            <img src="<?php echo $baseUrl; ?>images/icons/delete-svgrepo-com.svg" width="14" height="14" style="vertical-align: middle; margin-right: 4px;" alt="Delete">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                                <div class="verification-status">
                                    <p class="text-warning">
                                        <img src="<?php echo $baseUrl; ?>images/icons/clock-svgrepo-com.svg" width="16" height="16" style="vertical-align: middle; margin-right: 6px;" alt="Pending">
                                        <strong>Status:</strong> Pending Review
                                    </p>
                                    <p><strong>Submitted:</strong> <?php echo date('d M Y, h:i A', strtotime($submittedAt)); ?></p>
                                    <p class="text-info">
                                        <img src="<?php echo $baseUrl; ?>images/icons/clock-svgrepo-com.svg" width="16" height="16" style="vertical-align: middle; margin-right: 6px;" alt="Pending">
                                        Your document is being reviewed by an admin. You will be notified once verified.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden replace file input -->
                        <input type="file" id="replaceDocInput" accept="image/jpeg,image/jpg,image/png,application/pdf" style="display:none;">

                    <?php else: ?>
                        <!-- STATE 1: NO DOCUMENT -->
                        <div class="verification-state no-document">
                            <div class="state-header">
                                <h4>Verification Document</h4>
                                <span class="state-badge not-verified">
                                    <img src="<?php echo $baseUrl; ?>images/icons/not-verified-svgrepo-com.svg" width="14" height="14" alt="Not Verified">
                                    Not Verified
                                </span>
                            </div>
                            <div class="state-content">
                                <p class="text-muted">Upload your ID, business registration, or proof of address.</p>

                                <div class="upload-area">
                                    <!-- Drop Zone -->
                                    <div class="drop-zone" id="dropZone">
                                        <img src="<?php echo $baseUrl; ?>images/icons/document-svgrepo-com.svg" width="32" height="32" class="upload-icon" alt="Upload">
                                        <p><strong>Drop your document here or click to upload</strong></p>
                                        <p class="file-types">JPG, PNG, PDF — Max 5MB</p>
                                        <p class="file-name-display" id="fileNameDisplay"></p>
                                    </div>

                                    <!-- Upload Form -->
                                    <form id="verificationForm" enctype="multipart/form-data">
                                        <div class="form-group">
                                            <select name="document_type" id="documentType" required>
                                                <option value="">Select Document Type</option>
                                                <option value="id">South African ID Document</option>
                                                <option value="proof_address">Proof of Address</option>
                                                <option value="other">Other Supporting Document</option>
                                            </select>
                                        </div>
                                        <div class="form-group" style="display: none;">
                                            <input type="file" name="document" id="verificationDoc" accept="image/jpeg,image/jpg,image/png,application/pdf" required>
                                        </div>
                                        <button type="submit" class="save-btn" id="uploadBtn">
                                            <img src="<?php echo $baseUrl; ?>images/icons/upload-svgrepo-com.svg" width="16" height="16" style="vertical-align: middle; margin-right: 6px;" alt="Upload">
                                            Upload Document
                                        </button>
                                    </form>

                                    <p class="help-text">Your document will be reviewed by an admin within 24–48 hours.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Verification Message -->
                    <div id="verificationMessage" style="margin-top: var(--spacing-md); display: none;"></div>
                </div>
            </div>
        </div>
    </main>

    <!-- ============================================================
    JAVASCRIPT
    ============================================================ -->

    <script>
        // ============================================================
        // DOM CACHE
        // ============================================================

        /**
         * DOM element references for seller profile page.
         * All elements are cached once and reused throughout the page.
         */
        var $profileAvatar = null,
            $profileImageUpload = null,
            $avatarUploadBtn = null,
            $profileImageForm = null,
            $fullName = null,
            $phone = null,
            $location = null,
            $profileEditForm = null,
            $verificationForm = null,
            $verificationDoc = null,
            $documentType = null,
            $verificationMessage = null,
            $statProducts = null,
            $statSales = null,
            $statRevenue = null,
            $statRating = null,
            $sellerSideMenu = null,
            $sellerMenuOverlay = null,
            $dropZone = null,
            $fileNameDisplay = null;

        /**
         * Caches all DOM elements used on the seller profile page.
         */
        function cacheElements() {
            $profileAvatar = $('#profileAvatar');
            $profileImageUpload = $('#profileImageUpload');
            $avatarUploadBtn = $('.avatar-upload-btn');
            $profileImageForm = $('#profileImageForm');
            $fullName = $('#fullName');
            $phone = $('#phone');
            $location = $('#location');
            $profileEditForm = $('#profileEditForm');
            $verificationForm = $('#verificationForm');
            $verificationDoc = $('#verificationDoc');
            $documentType = $('#documentType');
            $verificationMessage = $('#verificationMessage');
            $statProducts = $('#statProducts');
            $statSales = $('#statSales');
            $statRevenue = $('#statRevenue');
            $statRating = $('#statRating');
            $sellerSideMenu = $('#sellerSideMenu');
            $sellerMenuOverlay = $('#sellerMenuOverlay');
            $dropZone = $('#dropZone');
            $fileNameDisplay = $('#fileNameDisplay');
        }

        // ============================================================
        // PROFILE STATS
        // ============================================================

        /**
         * Loads seller statistics from the server.
         */
        function loadProfileStats() {
            $.ajax({
                url: baseUrl + 'php/endpoints/users/get-user-stats.php?seller_id=' + currentUserId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        if ($statProducts && $statProducts.length) $statProducts.text(data.total_products || 0);
                        if ($statSales && $statSales.length) $statSales.text(data.completed_orders || 0);
                        if ($statRevenue && $statRevenue.length) $statRevenue.text('R ' + (data.total_revenue || 0)
                            .toFixed(2));
                        if ($statRating && $statRating.length) {
                            var ratingText = data.avg_rating ? data.avg_rating.toFixed(1) + '/5' : 'No reviews yet';
                            $statRating.text(ratingText);
                        }
                    }
                },
                error: function() {
                    if ($statProducts && $statProducts.length) $statProducts.text('0');
                    if ($statSales && $statSales.length) $statSales.text('0');
                    if ($statRevenue && $statRevenue.length) $statRevenue.text('R 0.00');
                    if ($statRating && $statRating.length) $statRating.text('No reviews yet');
                }
            });
        }

        // ============================================================
        // PROFILE IMAGE UPLOAD
        // ============================================================

        /**
         * Handles profile image upload via AJAX.
         */
        function handleProfileImageUpload() {
            if (!$profileImageUpload || !$profileImageUpload.length) return;

            $profileImageUpload.on('change', function() {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        if ($profileAvatar && $profileAvatar.length) {
                            $profileAvatar.attr('src', e.target.result);
                        }
                    };
                    reader.readAsDataURL(file);

                    var formData = new FormData();
                    formData.append('action', 'upload_image');
                    formData.append('profile_image', file);

                    $.ajax({
                        url: baseUrl + 'php/endpoints/users/update-profile.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(data) {
                            if (data.success) {
                                showSuccessToast(data.message);
                            } else {
                                showErrorToast(data.message);
                                if ($profileAvatar && $profileAvatar.length) {
                                    $profileAvatar.attr('src', baseUrl + 'images/icons/profile-svgrepo-com.svg');
                                }
                            }
                        },
                        error: function() {
                            showErrorToast('Error uploading image.');
                            if ($profileAvatar && $profileAvatar.length) {
                                $profileAvatar.attr('src', baseUrl + 'images/icons/profile-svgrepo-com.svg');
                            }
                        }
                    });
                }
            });
        }

        /**
         * Triggers the file input when the avatar upload button is clicked.
         */
        function handleAvatarUploadClick() {
            if (!$avatarUploadBtn || !$avatarUploadBtn.length) return;
            if (!$profileImageUpload || !$profileImageUpload.length) return;

            $avatarUploadBtn.on('click', function(e) {
                e.preventDefault();
                $profileImageUpload.click();
            });
        }

        // ============================================================
        // PROFILE EDIT FORM
        // ============================================================

        /**
         * Handles the profile edit form submission via AJAX.
         */
        function handleProfileEdit() {
            if (!$profileEditForm || !$profileEditForm.length) return;

            $profileEditForm.on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                formData.append('action', 'update_profile');

                $.ajax({
                    url: baseUrl + 'php/endpoints/users/update-profile.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            showSuccessToast(data.message);
                        } else {
                            showErrorToast(data.message);
                        }
                    },
                    error: function() {
                        showErrorToast('Error updating profile.');
                    }
                });
            });
        }

        // ============================================================
        // VERIFICATION UPLOAD
        // ============================================================

        /**
         * Sets up the document upload drop zone and file input.
         */
        function setupDocumentUpload() {
            if (!$dropZone || !$dropZone.length) return;
            if (!$verificationDoc || !$verificationDoc.length) return;

            var dropZoneEl = $dropZone[0];
            var fileInputEl = $verificationDoc[0];

            // Click drop zone to trigger file input
            $dropZone.on('click', function() {
                $verificationDoc.click();
            });

            // Handle file selection
            $verificationDoc.on('change', function(e) {
                var file = this.files[0];
                if (file) {
                    // Show file name in drop zone
                    $fileNameDisplay.text('📄 ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)');
                    $dropZone.css('border-color', 'var(--success)');
                    $dropZone.css('background', 'var(--success-light)');

                    // Auto-submit the form
                    setTimeout(function() {
                        $verificationForm.submit();
                    }, 500);
                }
            });

            // Handle drag and drop
            $dropZone.on('dragover', function(e) {
                e.preventDefault();
                $(this).css('border-color', 'var(--primary-color)');
                $(this).css('background', 'var(--primary-fade)');
            });

            $dropZone.on('dragleave', function(e) {
                e.preventDefault();
                $(this).css('border-color', 'var(--border-light)');
                $(this).css('background', 'transparent');
            });

            $dropZone.on('drop', function(e) {
                e.preventDefault();
                $(this).css('border-color', 'var(--border-light)');
                $(this).css('background', 'transparent');

                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    $verificationDoc[0].files = files;
                    $verificationDoc.trigger('change');
                }
            });
        }

        /**
         * Handles the verification document upload form via AJAX.
         */
        function handleVerificationUpload() {
            if (!$verificationForm || !$verificationForm.length) return;

            $verificationForm.on('submit', function(e) {
                e.preventDefault();

                var file = $verificationDoc[0].files[0];
                if (!file) {
                    showErrorToast('Please select a document to upload.');
                    return;
                }

                var formData = new FormData(this);

                // Disable button and show loading
                var $btn = $(this).find('.save-btn');
                var originalText = $btn.text();
                $btn.prop('disabled', true).text('Uploading...');

                $.ajax({
                    url: baseUrl + 'php/endpoints/users/upload-verification.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(data) {
                        if ($verificationMessage && $verificationMessage.length) {
                            $verificationMessage.show();
                            if (data.success) {
                                $verificationMessage.html('<p style="color: var(--success);">' + data.message +
                                    '</p>');
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else {
                                $verificationMessage.html('<p style="color: var(--error);">' + data.message +
                                    '</p>');
                            }
                        }
                    },
                    error: function() {
                        if ($verificationMessage && $verificationMessage.length) {
                            $verificationMessage.show();
                            $verificationMessage.html('<p style="color: var(--error);">Could not upload document.</p>');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text(originalText);
                    }
                });
            });
        }

        // ============================================================
        // VERIFICATION ACTIONS
        // ============================================================

        /**
         * Deletes the uploaded verification document.
         */
        function deleteDocument() {
            if (confirm('Are you sure you want to delete your verification document?')) {
                $.ajax({
                    url: baseUrl + 'php/endpoints/users/delete-verification.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({}),
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            showSuccessToast('Document deleted successfully.');
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            showErrorToast(data.message);
                        }
                    },
                    error: function() {
                        showErrorToast('Could not delete document.');
                    }
                });
            }
        }

        // ============================================================
        // SIDEBAR HANDLING
        // ============================================================

        /**
         * Closes the sidebar when interacting with modals or action buttons.
         */
        $(document).on('click',
            '[data-modal-open], .view-details-btn, .process-btn, .ship-btn, .complete-btn, .cancel-btn, .delete-btn, .edit-btn',
            function() {
                if ($sellerSideMenu && $sellerSideMenu.length && $sellerSideMenu.hasClass('active')) {
                    $sellerSideMenu.data('was-open', true);
                    $sellerSideMenu.removeClass('active');
                    if ($sellerMenuOverlay && $sellerMenuOverlay.length) {
                        $sellerMenuOverlay.removeClass('active');
                    }
                }
            });

        /**
         * Reopens the sidebar after a modal closes if it was open before.
         */
        $(document).on('click', '.modal-close, .btn-close, .order-modal-close', function() {
            if ($sellerSideMenu && $sellerSideMenu.length && $sellerSideMenu.data('was-open') === true) {
                $sellerSideMenu.addClass('active');
                if ($sellerMenuOverlay && $sellerMenuOverlay.length) {
                    $sellerMenuOverlay.addClass('active');
                }
                $sellerSideMenu.removeData('was-open');
            }
        });

        // ============================================================
        // DOCUMENT READY
        // ============================================================

        $(document).ready(function() {
            // Cache all DOM elements
            cacheElements();

            // Load profile stats
            loadProfileStats();

            // Profile image upload
            handleProfileImageUpload();
            handleAvatarUploadClick();

            // Profile edit form
            handleProfileEdit();

            // Verification upload
            handleVerificationUpload();
            setupDocumentUpload();
        });
    </script>

</body>

</html>