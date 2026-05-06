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
                    // Clear any existing session first
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        session_destroy();
                    }
                    // Use admin-specific login function
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
                    // Clear any existing session first
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        session_destroy();
                    }
                    // Use seller-specific login function
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
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/login-signup.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/admin-login.css">
</head>
<body class="admin-login-body">
    <div class="admin-login-container">
        <div class="admin-login-box">
            <div class="admin-login-header">
                <h1>Consu<span>Trade</span></h1>
                <p>Dashboard Access</p>
            </div>
            
            <?php if ($error): ?>
                <div class="admin-login-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form action="" method="post" class="admin-login-form">
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
                
                <div class="input-group password-group">
                    <label for="password">Password</label>
                    <div class="password-field-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                            <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="18" height="18" alt="Show password">
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="admin-login-btn">Login to Dashboard</button>
            </form>
        </div>
    </div>
    
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
</body>
</html>