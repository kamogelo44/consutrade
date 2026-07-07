<?php
/*
 * ConsuTrade - Seller Verification Settings
 * This is now just a wrapper for the verification component
 */

require_once dirname(__DIR__) . '/init.php';
include dirname(__DIR__) . '/includes/session-vars.php';

// Check maintenance mode (one line!)
checkMaintenanceMode();

if (!$auth->hasRole('seller')) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$breadcrumbItems = [
    ['url' => $baseUrl . 'profile.php', 'label' => 'My Profile'],
    ['label' => 'Seller Verification']
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Verification - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <style>
        .verification-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--spacing-2xl) var(--spacing-xl);
            min-height: calc(100vh - 200px);
        }

        .verification-container .back-link {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: var(--spacing-md);
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
        }

        .verification-container .back-link:hover {
            transform: translateX(-4px);
            text-decoration: underline;
        }

        .verification-container .back-link img {
            width: 16px;
            height: 16px;
            filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(1577%) hue-rotate(350deg);
        }

        @media (max-width: 768px) {
            .verification-container {
                padding: var(--spacing-md);
                margin-top: 60px;
            }
        }
    </style>
</head>

<body>

    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <?php include dirname(__DIR__) . '/includes/breadcrumb.php'; ?>

    <main class="verification-container">
        <a href="<?php echo $baseUrl; ?>profile.php" class="back-link">
            <img src="<?php echo $baseUrl; ?>images/icons/left-arrow-2-svgrepo-com.svg" alt="Back">
            Back to Profile
        </a>

        <?php include dirname(__DIR__) . '/includes/verification-component.php'; ?>
    </main>

    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
</body>

</html>