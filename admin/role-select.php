<?php
/*
 * ConsuTrade - Role Selection Page
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__) . '/init.php';

// Only show if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$roles = $auth->getAvailableRoles();
$adminRoles = array_intersect($roles, ['admin', 'seller']);

// If user only has one admin/seller role, redirect directly
if (count($adminRoles) === 1) {
    $role = array_values($adminRoles)[0];
    $redirect = $role === 'admin'
        ? 'admin-dashboard.php'
        : 'seller-dashboard.php';
    header('Location: ' . $redirect);
    exit;
}

// If user has no admin/seller roles, redirect to main site
if (empty($adminRoles)) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Show role selection
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Role - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--dark-bg) 0%, #2d2d2d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-md);
        }

        .role-select-container {
            width: 100%;
            max-width: 500px;
        }

        .role-select-box {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-xl);
            text-align: center;
        }

        .role-select-box h1 {
            font-size: var(--font-2xl);
            color: var(--dark-bg);
            margin-bottom: var(--spacing-sm);
        }

        .role-select-box h1 span {
            color: var(--primary-color);
        }

        .role-select-box p {
            color: var(--gray-medium);
            margin-bottom: var(--spacing-xl);
        }

        .role-options {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }

        .role-option {
            display: flex;
            align-items: center;
            padding: var(--spacing-lg);
            border: 2px solid var(--border-light);
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--dark-bg);
        }

        .role-option:hover {
            border-color: var(--primary-color);
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .role-option .role-icon {
            font-size: var(--font-3xl);
            margin-right: var(--spacing-md);
        }

        .role-option .role-info {
            text-align: left;
        }

        .role-option .role-info .role-title {
            font-weight: var(--font-bold);
            font-size: var(--font-lg);
        }

        .role-option .role-info .role-desc {
            color: var(--gray-medium);
            font-size: var(--font-sm);
        }

        .role-option .role-arrow {
            margin-left: auto;
            color: var(--gray-medium);
        }

        .back-link {
            display: inline-block;
            margin-top: var(--spacing-lg);
            color: var(--gray-medium);
            text-decoration: none;
            font-size: var(--font-sm);
        }

        .back-link:hover {
            color: var(--primary-color);
        }
    </style>
</head>

<body>
    <div class="role-select-container">
        <div class="role-select-box">
            <h1>Consu<span>Trade</span></h1>
            <p>You have multiple roles. Choose how you want to access the dashboard:</p>

            <div class="role-options">
                <?php if (in_array('admin', $adminRoles)): ?>
                    <a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php" class="role-option"
                        onclick="selectRole(event, 'admin')">
                        <span class="role-icon">🛡️</span>
                        <div class="role-info">
                            <div class="role-title">Administrator</div>
                            <div class="role-desc">Full system access, manage users, products, and reports</div>
                        </div>
                        <span class="role-arrow">→</span>
                    </a>
                <?php endif; ?>

                <?php if (in_array('seller', $adminRoles)): ?>
                    <a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php" class="role-option"
                        onclick="selectRole(event, 'seller')">
                        <span class="role-icon">🏪</span>
                        <div class="role-info">
                            <div class="role-title">Seller</div>
                            <div class="role-desc">Manage your products, orders, and store settings</div>
                        </div>
                        <span class="role-arrow">→</span>
                    </a>
                <?php endif; ?>
            </div>

            <a href="<?php echo $baseUrl; ?>index.php" class="back-link">← Back to Homepage</a>
        </div>
    </div>

    <script>
        function selectRole(event, role) {
            event.preventDefault();

            fetch(baseUrl + 'php/endpoints/auth/switch-role.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        role: role
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        alert(data.message || 'Failed to switch role. Please try again.');
                    }
                })
                .catch(error => {
                    alert('An error occurred. Please try again.');
                });
        }
    </script>
</body>

</html>