<?php
/*
 * ConsuTrade - Forgot Password Page
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';

if ($isLoggedIn) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$prefillEmail = isset($_GET['email']) ? htmlspecialchars(trim($_GET['email'])) : '';

$page_js = 'forgot-password.js';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main class="forgot-password-page">
        <div class="forgot-password-container">
            <h1>Forgot Password</h1>
            <p>Enter your email address and we'll send you a link to reset your password.</p>

            <div id="forgotMessage" class="form-message" style="display: none;"></div>

            <form id="forgotPasswordForm" class="forgot-password-form">
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email address" value="<?php echo $prefillEmail; ?>" required>
                </div>

                <button type="submit" class="submit-btn">Send Reset Link</button>
            </form>

            <p class="form-footer-link">
                Remember your password? <a href="#" id="loginInsteadLink">Login here</a>
            </p>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

</body>

</html>