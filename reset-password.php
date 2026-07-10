<?php
/*
 * ConsuTrade - Reset Password Page
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';

if ($isLoggedIn) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$token = $_GET['token'] ?? '';
$validToken = !empty($token);
$page_js = 'reset-password.js';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main class="forgot-password-page">
        <div class="forgot-password-container">
            <h1>Reset Password</h1>

            <?php if (!$validToken): ?>
                <div class="error-message" style="display: block; text-align: center;">
                    Invalid or expired reset link. Please request a new one.
                </div>
                <p class="form-footer-link">
                    <a href="<?php echo $baseUrl; ?>forgot-password.php">Request new reset link</a>
                </p>
            <?php else: ?>
                <p>Enter your new password below.</p>

                <div id="resetMessage" class="form-message" style="display: none;"></div>

                <form id="resetPasswordForm" class="forgot-password-form">
                    <input type="hidden" id="resetToken" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="input-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter new password" required minlength="8">
                        <small>Minimum 8 characters</small>
                    </div>

                    <div class="input-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                    </div>

                    <button type="submit" class="submit-btn">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

</body>

</html>