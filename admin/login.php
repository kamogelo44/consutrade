<?php
/*
 * ConsuTrade - Admin Login
 * Author: Kamogelo Phale
 * 
 * Separate login page for admin access only
 */

require_once dirname(__DIR__) . '/php/helpers.php';

session_start();

$baseUrl = getBaseUrl();

// If already logged in as admin, redirect to dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: admin-dashboard.php');
    exit();
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once dirname(__DIR__) . '/php/config.php';
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // Query only for admin users
        $sql = "SELECT user_id, full_name, email, password, role FROM users WHERE email = ? AND role = 'admin'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['admin_logged_in'] = true;
                
                header('Location: admin-dashboard.php');
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password. Admin access only.';
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/admin-login.css">
</head>
<body class="admin-login-body">
    <div class="admin-login-container">
        <div class="admin-login-box">
            <div class="admin-login-header">
                <h1>Consu<span>Trade</span></h1>
                <p>Administrator Access Only</p>
            </div>
            
            <?php if ($error): ?>
                <div class="admin-login-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form action="" method="post" class="admin-login-form">
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="admin@consutrade.com" required autofocus>
                </div>
                
                <div class="input-group password-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                            <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="20" height="20" alt="Show password">
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="admin-login-btn">Login to Admin Panel</button>
            </form>
        </div>
    </div>
    
    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
</body>
</html>