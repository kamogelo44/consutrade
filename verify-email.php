<?php
/*
 * ConsuTrade - Email Verification Page
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';

$token = $_GET['token'] ?? '';
$verified = false;
$message = '';

if (!empty($token)) {
    $result = $auth->verifyEmail($token);
    $verified = $result['success'];
    $message = $result['message'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main style="max-width: 500px; margin: 80px auto; padding: var(--spacing-xl); text-align: center;">
        <?php if ($verified): ?>
            <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="64" height="64" alt="Verified" style="filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(1577%) hue-rotate(350deg); margin-bottom: var(--spacing-lg);">
            <h1 style="font-size: var(--font-2xl); color: var(--dark-bg); margin-bottom: var(--spacing-sm);">Email Verified</h1>
            <p style="color: var(--gray-medium); margin-bottom: var(--spacing-xl);"><?php echo htmlspecialchars($message); ?></p>
            <a href="<?php echo $baseUrl; ?>index.php" class="view-all-btn" style="display: inline-block;">Login to your account</a>
        <?php elseif (!empty($token)): ?>
            <img src="<?php echo $baseUrl; ?>images/icons/error-svgrepo-com.svg" width="64" height="64" alt="Error" style="opacity: 0.5; margin-bottom: var(--spacing-lg);">
            <h1 style="font-size: var(--font-2xl); color: var(--dark-bg); margin-bottom: var(--spacing-sm);">Verification Failed</h1>
            <p style="color: var(--gray-medium); margin-bottom: var(--spacing-xl);"><?php echo htmlspecialchars($message); ?></p>
            <a href="<?php echo $baseUrl; ?>index.php" class="view-all-btn" style="display: inline-block;">Go to homepage</a>
        <?php else: ?>
            <h1 style="font-size: var(--font-2xl); color: var(--dark-bg); margin-bottom: var(--spacing-sm);">Invalid Link</h1>
            <p style="color: var(--gray-medium);">No verification token provided.</p>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>