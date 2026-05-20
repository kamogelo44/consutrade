<?php
/*
 * ConsuTrade - Admin/Seller Login
 * Author: Kamogelo Phale
 * 
 * Login page for administrators and sellers to access dashboards
 */

require_once dirname(__DIR__) . '/init.php';

$baseUrl = getBaseUrl();
$error = '';
$role_type = '';

// If already logged in as admin or seller, redirect to appropriate dashboard
if (isAdminLoggedIn()) {
    header('Location: admin-dashboard.php');
    exit;
} elseif (isSellerLoggedIn()) {
    header('Location: seller-dashboard.php');
    exit;
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role_type = $_POST['role_type'] ?? 'admin';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        if ($role_type === 'admin') {
            // Authenticate admin
            $sql = "SELECT user_id, full_name, email, password, role FROM users WHERE email = ? AND role = 'admin'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    session_destroy();
                    session_start();
                    loginAdmin($user['user_id'], $user['full_name'], $user['email'], $user['role']);
                    header('Location: admin-dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid email or password.';
                }
            } else {
                $error = 'Invalid email or password.';
            }
            $stmt->close();
        } else {
            // Authenticate seller
            $sql = "SELECT user_id, full_name, email, password, role FROM users WHERE email = ? AND role = 'seller'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    session_destroy();
                    session_start();
                    loginSeller($user['user_id'], $user['full_name'], $user['email'], $user['role']);
                    header('Location: seller-dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid email or password.';
                }
            } else {
                $error = 'Invalid email or password.';
            }
            $stmt->close();
        }
    }
}
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
        /* Login Page Specific Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--dark-bg) 0%, #2d2d2d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, sans-serif;
            padding: var(--spacing-md);
        }
        
        /* Login Container */
        .login-container {
            width: 100%;
            max-width: 450px;
        }
        
        /* Login Box */
        .login-box {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-xl);
        }
        
        /* Login Header */
        .login-header {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }
        
        .login-header h1 {
            font-size: var(--font-3xl);
            font-weight: var(--font-bold);
            color: var(--dark-bg);
            margin-bottom: var(--spacing-xs);
        }
        
        .login-header h1 span {
            color: var(--primary-color);
        }
        
        .login-header p {
            color: var(--gray-medium);
            font-size: var(--font-md);
        }
        
        /* Error Message */
        .login-error {
            background: var(--error-light);
            color: var(--error);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            text-align: center;
            font-size: var(--font-sm);
            border-left: 4px solid var(--error);
        }
        
        /* Form Styles */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }
        
        .input-group {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xs);
        }
        
        .input-group label {
            font-weight: var(--font-semibold);
            color: var(--dark-bg);
            font-size: var(--font-sm);
        }
        
        .input-group select,
        .input-group input {
            padding: 12px var(--spacing-md);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: var(--font-md);
            transition: all var(--transition-fast);
            background: var(--white);
        }
        
        .input-group select:focus,
        .input-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.1);
        }
        
        /* Password Field with Toggle */
        .password-field-wrapper {
            position: relative;
        }
        
        .password-field-wrapper input {
            width: 100%;
            padding-right: 45px;
        }
        
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
            justify-content: center;
            opacity: 0.6;
            transition: opacity var(--transition-fast);
        }
        
        .toggle-password:hover {
            opacity: 1;
        }
        
        .toggle-password img {
            width: 18px;
            height: 18px;
        }
        
        /* Login Button */
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
        
        /* Footer Links */
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
        
        .login-footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        /* Responsive */
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
            
            <?php if ($error): ?>
                <div class="login-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form action="" method="post" class="login-form">
                <div class="input-group">
                    <label for="role_type">Login As</label>
                    <select id="role_type" name="role_type" required>
                        <option value="admin" <?php echo $role_type === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                        <option value="seller" <?php echo $role_type === 'seller' ? 'selected' : ''; ?>>Seller</option>
                    </select>
                </div>
                
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autocomplete="email" autofocus>
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
</body>
</html>