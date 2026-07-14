<?php
/*
 * ConsuTrade - Public Seller Profile Page
 * Author: Kamogelo Phale
 * 
 * Displays public seller profile with products and reviews
 * All dynamic data loaded via AJAX
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
include __DIR__ . '/includes/functions.php';

$seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;

if ($seller_id <= 0) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Get seller data for the page title and name
$seller = $userRepo->findById($seller_id);

if ($seller && $seller->hasRole('seller')) {
    $sellerName = $seller->getFullName();
    $sellerLocation = $seller->getLocation() ?: '';
    $profileImage = $seller->getProfileImageUrl();
    $isVerified = $seller->isVerified();
} else {
    $sellerName = 'Seller';
    $sellerLocation = '';
    $profileImage = $baseUrl . 'images/icons/profile-svgrepo-com.svg';
    $isVerified = false;
}

$breadcrumbItems = [
    ['label' => htmlspecialchars($sellerName)]
];

$page_js = 'seller-profile.js';
$load_products_js = true;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($sellerName); ?> - <?php t('seller'); ?> | ConsuTrade</title>
    <meta name="description" content="<?php t('seller_profile_description'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main class="sp-wrap">
        <!-- Breadcrumb -->
        <nav class="sp-breadcrumb">
            <a href="<?php echo $baseUrl; ?>index.php"><?php t('home'); ?></a>
            <span class="sp-crumb-sep">›</span>
            <a href="<?php echo $baseUrl; ?>product-listings.php"><?php t('products'); ?></a>
            <span class="sp-crumb-sep">›</span>
            <span class="sp-crumb-current"><?php echo htmlspecialchars($sellerName); ?></span>
        </nav>

        <?php include 'includes/flash-message.php'; ?>

        <!-- STOREFRONT - Skeleton -->
        <div id="sp-storefront-container">
            <div class="sp-storefront">
                <div class="sp-cover"></div>
                <div class="sp-storefront-body">
                    <div class="sp-avatar-wrap skeleton" style="border-radius:50%;width:88px;height:88px;"></div>
                    <div class="sp-storefront-info">
                        <div class="skeleton skeleton-text" style="width:260px;height:28px;"></div>
                        <div class="skeleton skeleton-text" style="width:180px;height:14px;margin-top:10px;"></div>
                        <div class="skeleton skeleton-text" style="width:120px;height:12px;margin-top:6px;"></div>
                    </div>
                    <div class="sp-storefront-stat">
                        <div class="skeleton skeleton-text" style="width:60px;height:36px;"></div>
                        <div class="skeleton skeleton-text" style="width:50px;height:12px;margin-top:6px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- QUICK STATS - Skeleton -->
        <div id="sp-stats-container">
            <div class="sp-quick-stats">
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <div class="sp-quick-stat">
                        <div class="sp-quick-stat-icon skeleton" style="border-radius:var(--radius-sm);width:46px;height:46px;"></div>
                        <div class="sp-quick-stat-content">
                            <div class="skeleton skeleton-text" style="width:60px;height:22px;"></div>
                            <div class="skeleton skeleton-text" style="width:80px;height:12px;margin-top:4px;"></div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- PRODUCTS - Skeleton -->
        <div class="sp-section-title">
            <span class="sp-section-title-text"><?php t('featured_items'); ?></span>
            <span class="sp-section-count" id="sp-product-count"></span>
        </div>
        <div class="sp-products-grid" id="seller-products-grid">
            <?php for ($i = 0; $i < 8; $i++): ?>
                <div class="sp-product-card">
                    <div class="sp-product-image skeleton" style="aspect-ratio:1/1;"></div>
                    <div class="sp-product-body">
                        <div class="skeleton skeleton-text" style="width:90%;height:16px;"></div>
                        <div class="skeleton skeleton-text" style="width:50%;height:14px;margin-top:8px;"></div>
                        <div class="skeleton skeleton-text" style="width:30%;height:12px;margin-top:4px;"></div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <!-- REVIEWS - Skeleton -->
        <div class="sp-section-title" style="margin-top:40px;">
            <span class="sp-section-title-text"><?php t('what_neighbours_say'); ?></span>
            <span class="sp-section-count" id="sp-review-count"></span>
        </div>
        <div id="sp-reviews-container">
            <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="sp-review">
                    <div class="sp-review-top">
                        <div class="sp-review-user">
                            <div class="sp-review-user-avatar skeleton" style="width:40px;height:40px;border-radius:50%;"></div>
                            <div>
                                <div class="skeleton skeleton-text" style="width:100px;height:14px;"></div>
                                <div class="skeleton skeleton-text" style="width:80px;height:12px;margin-top:4px;"></div>
                            </div>
                        </div>
                        <div class="skeleton skeleton-text" style="width:80px;height:12px;"></div>
                    </div>
                    <div class="skeleton skeleton-text" style="width:95%;height:14px;margin-top:12px;"></div>
                    <div class="skeleton skeleton-text" style="width:60%;height:14px;margin-top:6px;"></div>
                </div>
            <?php endfor; ?>
        </div>
    </main>

    <!-- Pass data to JavaScript -->
    <script>
        var sellerProfileId = <?php echo $seller_id; ?>;
        var sellerProfileName = '<?php echo addslashes($sellerName); ?>';
        var sellerProfileLocation = '<?php echo addslashes($sellerLocation); ?>';
        var sellerProfileImage = '<?php echo $profileImage; ?>';
    </script>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

</body>

</html>