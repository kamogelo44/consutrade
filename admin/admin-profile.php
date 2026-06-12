<?php
/*
 * ConsuTrade - Admin Profile Page
 * Author: Kamogelo Phale
 * 
 * Allows admin users to view and edit their profile information
 */

require_once dirname(__DIR__) . '/init.php';

if (!$auth->isAdmin()) {
    header('Location: login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);

    // Validate phone number (South African format)
    $clean_phone = !empty($phone) ? preg_replace('/[^0-9]/', '', $phone) : '';
    if (!empty($clean_phone) && !preg_match('/^0[0-9]{9,10}$/', $clean_phone)) {
        $_SESSION['error'] = 'Please enter a valid South African phone number (e.g., 0712345678)';
    } else {
        // Use UserRepository instead of raw SQL
        $updateData = [
            'full_name' => $full_name,
            'phone' => $clean_phone
        ];

        $result = $userRepo->updateProfile($currentUser->getUserId(), $updateData);

        if ($result) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['success'] = 'Profile updated successfully.';

            // Refresh user object in session
            $updatedUser = $userRepo->findById($currentUser->getUserId());
            $_SESSION['user_object'] = serialize($updatedUser);

            // Refresh currentUser for this page
            $currentUser = $updatedUser;
        } else {
            $_SESSION['error'] = 'Could not update profile. Please try again.';
        }
    }

    // Redirect to avoid form resubmission on refresh
    header('Location: ' . $baseUrl . 'admin/admin-profile.php');
    exit;
}

// Get fresh user data
$user_name = $currentUser->getFullName();
$user_email = $currentUser->getEmail();
$user_phone = $currentUser->getPhone();
$user_created_at = $currentUser->getCreatedAt();
$profile_image = $currentUser->getProfileImageUrl();
$user_role = $currentUser->getRole();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Admin Profile - ConsuTrade</title>

    <!-- CSS Imports -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-layout.css">

    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script src="<?php echo $baseUrl; ?>js/main.js"></script>

    <style>
        /* Only page-specific styles that don't exist elsewhere */
        .profile-layout {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
        }

        .profile-left {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            padding: var(--spacing-xl);
            text-align: center;
        }

        .profile-avatar {
            margin-bottom: var(--spacing-lg);
        }

        .profile-avatar img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            background: var(--primary-fade);
        }

        .profile-left h2 {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-xs);
        }

        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
            background: var(--error-light);
            color: var(--error);
            margin-bottom: var(--spacing-lg);
        }

        .stats-list {
            text-align: left;
            border-top: 1px solid var(--border-light);
            padding-top: var(--spacing-lg);
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid var(--border-light);
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-label {
            font-weight: var(--font-semibold);
            color: var(--gray-dark);
            font-size: var(--font-sm);
        }

        .stat-value {
            color: var(--gray-medium);
            font-size: var(--font-sm);
        }

        .profile-right {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            padding: var(--spacing-xl);
        }

        .profile-right h3 {
            font-size: var(--font-lg);
            font-weight: var(--font-semibold);
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-sm);
            border-bottom: 2px solid var(--primary-color);
        }

        .profile-form .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .profile-form label {
            display: block;
            font-weight: var(--font-semibold);
            margin-bottom: var(--spacing-sm);
            color: var(--dark-bg);
        }

        .profile-form input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: var(--font-md);
            transition: all var(--transition-fast);
        }

        .profile-form input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 0, 0.1);
        }

        .profile-form input:disabled {
            background: var(--gray-bg-light);
            cursor: not-allowed;
        }

        .profile-form small {
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
            color: white;
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

        .quick-links-section {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            padding: var(--spacing-xl);
            margin-top: var(--spacing-xl);
        }

        .quick-links-section h3 {
            font-size: var(--font-lg);
            font-weight: var(--font-semibold);
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-sm);
            border-bottom: 2px solid var(--primary-color);
        }

        .quick-links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: var(--spacing-md);
        }

        .quick-link-card {
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

        .quick-link-card:hover {
            background: var(--primary-fade);
            border-color: var(--primary-light);
            transform: translateX(4px);
        }

        .quick-link-card img {
            width: 24px;
            height: 24px;
            filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(1577%) hue-rotate(350deg);
        }

        .quick-link-card span {
            color: var(--gray-dark);
            font-size: var(--font-sm);
            font-weight: var(--font-medium);
        }

        .quick-link-card:hover span {
            color: var(--primary-color);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .profile-layout {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg);
            }
        }

        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
            }

            .save-btn,
            .change-password-btn {
                width: 100%;
                text-align: center;
            }

            .quick-links-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .page-header h1 {
                font-size: var(--font-xl);
            }

            .profile-left,
            .profile-right,
            .quick-links-section {
                padding: var(--spacing-md);
            }

            .stat-item {
                flex-direction: column;
                gap: var(--spacing-xs);
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main-content">
        <div class="dashboard-content">
            <div class="page-header">
                <h1>Admin Profile</h1>
                <p>Manage your account information</p>
            </div>

            <!-- Flash messages using global component -->
            <?php include dirname(__DIR__) . '/includes/flash-message.php'; ?>

            <div class="profile-layout">
                <div class="profile-left">
                    <div class="profile-avatar">
                        <img src="<?php echo $profile_image; ?>" alt="Profile Avatar" onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
                    </div>
                    <h2><?php echo htmlspecialchars($user_name); ?></h2>
                    <span class="role-badge">Administrator</span>

                    <div class="stats-list">
                        <div class="stat-item">
                            <span class="stat-label">Member Since</span>
                            <span class="stat-value"><?php echo date('d M Y', strtotime($user_created_at)); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Email</span>
                            <span class="stat-value"><?php echo htmlspecialchars($user_email); ?></span>
                        </div>
                        <?php if ($user_phone): ?>
                            <div class="stat-item">
                                <span class="stat-label">Phone</span>
                                <span class="stat-value"><?php echo htmlspecialchars($user_phone); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="stat-item">
                            <span class="stat-label">Role</span>
                            <span class="stat-value"><?php echo ucfirst($user_role); ?></span>
                        </div>
                    </div>
                </div>

                <div class="profile-right">
                    <h3>Edit Profile Information</h3>
                    <form method="POST" action="" class="profile-form">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_name); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($user_email); ?>" disabled>
                            <small>Email cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number (Optional)</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_phone ?? ''); ?>" placeholder="e.g., 071 234 5678">
                            <small>Optional but recommended for contact</small>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="save-btn">Save Changes</button>
                            <a href="<?php echo $baseUrl; ?>change-password.php" class="change-password-btn">Change Password</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="quick-links-section">
                <h3>Quick Links</h3>
                <div class="quick-links-grid">
                    <a href="users.php" class="quick-link-card">
                        <img src="<?php echo $baseUrl; ?>images/icons/users-svgrepo-com.svg" alt="Users">
                        <span>Manage Users</span>
                    </a>
                    <a href="all-products.php" class="quick-link-card">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products">
                        <span>All Products</span>
                    </a>
                    <a href="all-orders.php" class="quick-link-card">
                        <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Orders">
                        <span>All Orders</span>
                    </a>
                    <a href="admin-dashboard.php" class="quick-link-card">
                        <img src="<?php echo $baseUrl; ?>images/icons/dashboard-svgrepo-com.svg" alt="Dashboard">
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>
        </div>
    </main>

</body>

</html>