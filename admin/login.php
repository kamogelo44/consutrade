<?php
/*
 * ConsuTrade - Admin/Seller Login Page
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__) . '/init.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    $roles = $auth->getAvailableRoles();
    $adminRoles = array_intersect($roles, ['admin', 'seller']);

    if (count($adminRoles) > 1) {
        header('Location: role-select.php');
        exit;
    } elseif (in_array('admin', $adminRoles)) {
        header('Location: admin-dashboard.php');
        exit;
    } elseif (in_array('seller', $adminRoles)) {
        header('Location: seller-dashboard.php');
        exit;
    } else {
        header('Location: ' . $baseUrl . 'index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Login - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script>
        var baseUrl = '<?php echo rtrim($baseUrl, '/') . '/'; ?>';
    </script>
    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--dark-bg) 0%, #2d2d2d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-md);
        }

        .login-container {
            width: 100%;
            max-width: 450px;
        }

        .login-box {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-xl);
        }

        .login-header {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }

        .login-header h1 {
            font-size: var(--font-3xl);
            font-weight: var(--font-bold);
            color: var(--dark-bg);
        }

        .login-header h1 span {
            color: var(--primary-color);
        }

        .login-header p {
            color: var(--gray-medium);
            font-size: var(--font-md);
        }

        .login-footer {
            text-align: center;
            margin-top: var(--spacing-xl);
            padding-top: var(--spacing-lg);
            border-top: 1px solid var(--border-light);
        }

        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: var(--font-sm);
        }

        .login-footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-box {
                padding: var(--spacing-lg);
            }

            .login-header h1 {
                font-size: var(--font-2xl);
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>Consu<span>Trade</span></h1>
                <p>Dashboard Access</p>
            </div>
            <div id="login-error-container" class="error-container" style="display: none;"></div>
            <form id="login-form" class="login-form">
                <div class="input-group">
                    <label for="login-email">Email Address</label>
                    <input type="email" id="login-email" name="email" placeholder="Enter your email" required autocomplete="email" autofocus>
                </div>
                <div class="input-group">
                    <label for="login-password">Password</label>
                    <div class="password-field-wrapper">
                        <input type="password" id="login-password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('login-password', this)">
                            <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="18" height="18" alt="Show password">
                        </button>
                    </div>
                </div>
                <button type="submit" class="submit-btn">Login to Dashboard</button>
            </form>
            <div class="login-footer">
                <a href="<?php echo $baseUrl; ?>index.php">← Back to Homepage</a>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#login-form').on('submit', function(e) {
                e.preventDefault();

                var formData = {
                    email: $('#login-email').val(),
                    password: $('#login-password').val(),
                    context: 'admin'
                };

                $.ajax({
                    url: baseUrl + 'php/endpoints/auth/login.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.redirect;
                        } else {
                            $('#login-error-container').show().html('<p style="color: #dc3545;">' + response.message + '</p>');
                        }
                    },
                    error: function() {
                        $('#login-error-container').show().html('<p style="color: #dc3545;">An error occurred. Please try again.</p>');
                    }
                });
            });
        });
    </script>
</body>

</html>