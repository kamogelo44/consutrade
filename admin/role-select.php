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
            background: var(--primary-fade);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .role-option .role-icon {
            width: 48px;
            height: 48px;
            margin-right: var(--spacing-md);
            flex-shrink: 0;
        }

        .role-option .role-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(1577%) hue-rotate(350deg) brightness(102%) contrast(101%);
        }

        .role-option .role-info {
            text-align: left;
            flex: 1;
        }

        .role-option .role-info .role-title {
            font-weight: var(--font-bold);
            font-size: var(--font-lg);
            color: var(--dark-bg);
        }

        .role-option .role-info .role-desc {
            color: var(--gray-medium);
            font-size: var(--font-sm);
        }

        .role-option .role-arrow {
            font-size: var(--font-xl);
            color: var(--gray-medium);
            transition: all var(--transition-fast);
        }

        .role-option:hover .role-arrow {
            transform: translateX(4px);
            color: var(--primary-color);
        }

        .back-link {
            display: inline-block;
            margin-top: var(--spacing-lg);
            color: var(--gray-medium);
            text-decoration: none;
            font-size: var(--font-sm);
            transition: all var(--transition-fast);
        }

        .back-link:hover {
            color: var(--primary-color);
            transform: translateX(-4px);
        }

        .back-link img {
            width: 14px;
            height: 14px;
            vertical-align: middle;
            margin-right: 4px;
            filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(1577%) hue-rotate(350deg) brightness(102%) contrast(101%);
        }

        @media (max-width: 480px) {
            .role-select-box {
                padding: var(--spacing-lg);
            }

            .role-option {
                padding: var(--spacing-md);
                flex-wrap: wrap;
            }

            .role-option .role-icon {
                width: 36px;
                height: 36px;
            }

            .role-option .role-info .role-title {
                font-size: var(--font-base);
            }

            .role-option .role-info .role-desc {
                font-size: var(--font-xs);
            }
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
                    <a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php" class="role-option" data-role="admin">
                        <span class="role-icon">
                            <img src="<?php echo $baseUrl; ?>images/icons/dashboard-svgrepo-com.svg" alt="Admin">
                        </span>
                        <div class="role-info">
                            <div class="role-title">Administrator</div>
                            <div class="role-desc">Full system access, manage users, products, and reports</div>
                        </div>
                        <span class="role-arrow">→</span>
                    </a>
                <?php endif; ?>

                <?php if (in_array('seller', $adminRoles)): ?>
                    <a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php" class="role-option" data-role="seller">
                        <span class="role-icon">
                            <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Seller">
                        </span>
                        <div class="role-info">
                            <div class="role-title">Seller</div>
                            <div class="role-desc">Manage your products, orders, and store settings</div>
                        </div>
                        <span class="role-arrow">→</span>
                    </a>
                <?php endif; ?>
            </div>

            <a href="<?php echo $baseUrl; ?>index.php" class="back-link">
                <img src="<?php echo $baseUrl; ?>images/icons/left-arrow-2-svgrepo-com.svg" alt="Back">
                Back to Homepage
            </a>
        </div>
    </div>

    <script>
        var baseUrl = '<?php echo rtrim($baseUrl, '/') . '/'; ?>';

        // Handle role selection with data attributes
        document.addEventListener('DOMContentLoaded', function() {
            var roleOptions = document.querySelectorAll('.role-option');

            roleOptions.forEach(function(option) {
                option.addEventListener('click', function(e) {
                    e.preventDefault();

                    var role = this.getAttribute('data-role');
                    var href = this.getAttribute('href');

                    // Show loading state
                    var originalText = this.querySelector('.role-title').textContent;
                    this.style.opacity = '0.6';
                    this.style.pointerEvents = 'none';

                    fetch(baseUrl + 'php/endpoints/auth/switch-role.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                role: role
                            })
                        })
                        .then(function(response) {
                            return response.json();
                        })
                        .then(function(data) {
                            if (data.success) {
                                window.location.href = data.redirect;
                            } else {
                                alert(data.message || 'Failed to switch role. Please try again.');
                                // Reset loading state
                                option.style.opacity = '1';
                                option.style.pointerEvents = 'auto';
                            }
                        })
                        .catch(function(error) {
                            alert('An error occurred. Please try again.');
                            option.style.opacity = '1';
                            option.style.pointerEvents = 'auto';
                        });
                });
            });
        });
    </script>
</body>

</html>