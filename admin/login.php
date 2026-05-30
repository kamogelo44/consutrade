<?php
/*
 * ConsuTrade - Admin/Seller Login Page
 * Author: Kamogelo Phale
 * 
 * Login page for administrators and sellers to access dashboards
 */

require_once dirname(__DIR__) . '/init.php';

$baseUrl = getBaseUrl();

// If already logged in, redirect to appropriate dashboard
if ($auth->isAdminLoggedIn()) {
    header('Location: admin-dashboard.php');
    exit;
} elseif ($auth->isSellerLoggedIn()) {
    header('Location: seller-dashboard.php');
    exit;
}

// Retrieve any errors from session (fallback for non-AJAX)
$error = $_SESSION['login_error'] ?? '';
$saved_email = $_SESSION['login_email'] ?? '';
unset($_SESSION['login_error'], $_SESSION['login_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Login - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--dark-bg) 0%, #2d2d2d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, sans-serif;
            padding: var(--spacing-md);
        }
        .login-container { width: 100%; max-width: 450px; }
        .login-box {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-xl);
        }
        .login-header { text-align: center; margin-bottom: var(--spacing-xl); }
        .login-header h1 { font-size: var(--font-3xl); font-weight: var(--font-bold); color: var(--dark-bg); margin-bottom: var(--spacing-xs); }
        .login-header h1 span { color: var(--primary-color); }
        .login-header p { color: var(--gray-medium); font-size: var(--font-md); }
        .login-error {
            background: var(--error-light);
            color: var(--error);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            text-align: center;
            font-size: var(--font-sm);
            border-left: 4px solid var(--error);
            display: none;
        }
        .login-form { display: flex; flex-direction: column; gap: var(--spacing-lg); }
        .input-group { display: flex; flex-direction: column; gap: var(--spacing-xs); }
        .input-group label { font-weight: var(--font-semibold); color: var(--dark-bg); font-size: var(--font-sm); }
        .input-group select, .input-group input {
            padding: 12px var(--spacing-md);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: var(--font-md);
            transition: all var(--transition-fast);
            background: var(--white);
        }
        .input-group select:focus, .input-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.1);
        }
        .password-field-wrapper { position: relative; }
        .password-field-wrapper input { width: 100%; padding-right: 45px; }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            opacity: 0.6;
            transition: opacity var(--transition-fast);
        }
        .toggle-password:hover { opacity: 1; }
        .toggle-password img { width: 18px; height: 18px; }
        .login-btn {
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 14px;
            border-radius: var(--radius-md);
            font-size: var(--font-base);
            font-weight: var(--font-bold);
            cursor: pointer;
            transition: all var(--transition-fast);
            margin-top: var(--spacing-sm);
        }
        .login-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 0, 0.3);
        }
        .login-btn:disabled {
            background: var(--gray-light);
            cursor: not-allowed;
            transform: none;
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
            transition: color var(--transition-fast);
        }
        .login-footer a:hover { color: var(--primary-dark); text-decoration: underline; }
        @media (max-width: 480px) {
            .login-box { padding: var(--spacing-lg); }
            .login-header h1 { font-size: var(--font-2xl); }
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
            
            <div id="login-error" class="login-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            
            <form id="dashboard-login-form" class="login-form">
                <div class="input-group">
                    <label for="role_type">Login As</label>
                    <select id="role_type" name="role_type" required>
                        <option value="admin">Administrator</option>
                        <option value="seller">Seller</option>
                    </select>
                </div>
                
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($saved_email); ?>" required autocomplete="email" autofocus>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="password-field-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                            <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="18" height="18" alt="Show password">
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="login-btn">Login to Dashboard</button>
            </form>
            
            <div class="login-footer">
                <a href="<?php echo $baseUrl; ?>index.php">← Back to Homepage</a>
            </div>
        </div>
    </div>

    <script>
    // Set baseUrl for admin login page (since footer.php is not included)
    var baseUrl = '<?php echo rtrim($baseUrl, '/') . '/'; ?>';
    </script>

    <script>
    // Dashboard Login AJAX Handler
    $(function() {
        $('#dashboard-login-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            var $submitBtn = $(this).find('button[type="submit"]');
            var originalText = $submitBtn.text();
            var $errorDiv = $('#login-error');
            
            $errorDiv.hide().empty();
            $submitBtn.prop('disabled', true).text('Logging in...');
            
            $.ajax({
                url: baseUrl + 'php/endpoints/login.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.redirect;
                    } else {
                        $errorDiv.show().text(response.message);
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', error);
                    $errorDiv.show().text('Something went wrong. Please try again.');
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
    });
    </script>
</body>
</html>