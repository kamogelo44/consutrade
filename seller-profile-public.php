<?php
/*
 * ConsuTrade - Public Seller Profile Page
 * Author: Kamogelo Phale
 * 
 * Displays public seller profile with products and reviews
 * Reviews limited to 5 initially with "Load More" AJAX
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
include __DIR__ . '/includes/functions.php';

$seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;

if ($seller_id <= 0) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$seller = $userRepo->findById($seller_id);

if (!$seller || !$seller->hasRole('seller')) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$profile_image = $seller->getProfileImageUrl();
$ratingData = $reviewRepo->getSellerRating($seller_id);
$avgRating = $ratingData['avg_rating'] ?? 0;
$reviewCount = $ratingData['review_count'] ?? 0;

// Only load first 5 reviews initially
$initialReviews = $reviewRepo->findBySeller($seller_id, 5);
$hasMoreReviews = $reviewCount > 5;

$from_product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$from_product_name = isset($_GET['product_name']) ? urldecode($_GET['product_name']) : '';

if ($from_product_id > 0 && $from_product_name) {
    $breadcrumbItems = [
        ['url' => 'product-listings.php', 'label' => 'Products'],
        ['url' => 'product-details.php?id=' . $from_product_id, 'label' => htmlspecialchars($from_product_name)],
        ['label' => htmlspecialchars($seller->getFullName())]
    ];
} else {
    $breadcrumbItems = [
        ['label' => htmlspecialchars($seller->getFullName())]
    ];
}

$page_js = 'seller-profile.js';
$load_products_js = true;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($seller->getFullName()); ?> - Seller Profile | ConsuTrade</title>
    <meta name="description" content="View products and reviews from <?php echo htmlspecialchars($seller->getFullName()); ?> on ConsuTrade">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <style>
        .public-seller-profile-container {
            width: 100%;
            max-width: 100%;
            padding: var(--spacing-xl);
            min-height: calc(100vh - 200px);
        }

        .seller-public-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-xl);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: var(--radius-lg);
            padding: var(--spacing-2xl) var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
            color: var(--white);
        }

        .seller-public-avatar {
            width: 72px;
            height: 72px;
            background: var(--white);
            border-radius: var(--radius-round);
            overflow: hidden;
            flex-shrink: 0;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .seller-public-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .seller-public-info {
            flex: 1;
            min-width: 0;
        }

        .seller-public-info h1 {
            font-size: var(--font-2xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-xs);
            color: var(--white);
        }

        .seller-public-meta {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        .seller-public-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            padding: 2px 10px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-semibold);
        }

        .seller-public-badge.verified {
            background-color: var(--success);
            color: var(--white);
        }

        .seller-public-badge.unverified {
            background-color: var(--warning);
            color: var(--white);
        }

        .seller-public-badge img {
            filter: brightness(0) invert(1);
            width: 14px;
            height: 14px;
        }

        .member-since {
            font-size: var(--font-xs);
            color: rgba(255, 255, 255, 0.7);
        }

        .seller-location {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            font-size: var(--font-xs);
            color: rgba(255, 255, 255, 0.7);
            margin-top: var(--spacing-xs);
        }

        .seller-location img {
            filter: brightness(0) invert(1);
            width: 12px;
            height: 12px;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            text-align: center;
            border: 1px solid var(--border-light);
            width: 100%;
            box-sizing: border-box;
        }

        .seller-public-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
        }

        .seller-stat-number {
            font-size: var(--font-2xl);
            font-weight: var(--font-bold);
            color: var(--primary-color);
        }

        .seller-stat-label {
            font-size: var(--font-xs);
            color: var(--gray-medium);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .rating-summary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-xs);
            margin-top: var(--spacing-xs);
        }

        .seller-public-products h2,
        .seller-reviews-section h2 {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-sm);
            border-bottom: 2px solid var(--primary-color);
            color: var(--gray-dark);
            display: inline-block;
        }

        .seller-public-products .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: var(--spacing-md);
        }

        .seller-reviews-section {
            margin-top: var(--spacing-2xl);
            width: 100%;
        }

        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }

        .review-card {
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-sm);
        }

        .reviewer-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .reviewer-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--gray-bg);
            overflow: hidden;
        }

        .reviewer-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .reviewer-name {
            font-weight: var(--font-semibold);
            color: var(--dark-bg);
            font-size: var(--font-sm);
        }

        .review-date {
            font-size: var(--font-xs);
            color: var(--gray-light);
        }

        .review-comment {
            font-size: var(--font-sm);
            line-height: 1.5;
            color: var(--gray-dark);
            margin-top: var(--spacing-sm);
        }

        .load-more-btn {
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: var(--spacing-md);
            background: var(--gray-bg);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: var(--font-sm);
            font-weight: var(--font-medium);
            color: var(--gray-dark);
            transition: all var(--transition-fast);
        }

        .load-more-btn:hover {
            background: var(--primary-fade);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main class="public-seller-profile-container">
        <?php include 'includes/breadcrumb.php'; ?>
        <?php include 'includes/flash-message.php'; ?>

        <div class="seller-public-header">
            <div class="seller-public-avatar">
                <img src="<?php echo $profile_image; ?>" alt="<?php echo htmlspecialchars($seller->getFullName()); ?>" loading="lazy">
            </div>
            <div class="seller-public-info">
                <h1><?php echo htmlspecialchars($seller->getFullName()); ?></h1>
                <div class="seller-public-meta">
                    <?php if ($seller->isVerified()): ?>
                        <span class="seller-public-badge verified">
                            <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="14" height="14" alt="Verified"> Verified
                        </span>
                    <?php else: ?>
                        <span class="seller-public-badge unverified">
                            <img src="<?php echo $baseUrl; ?>images/icons/not-verified-svgrepo-com.svg" width="14" height="14" alt="Unverified"> Unverified
                        </span>
                    <?php endif; ?>
                    <span class="member-since">Joined <?php echo date('M Y', strtotime($seller->getCreatedAt())); ?></span>
                </div>
                <?php if ($seller->getLocation()): ?>
                    <div class="seller-location">
                        <img src="<?php echo $baseUrl; ?>images/icons/pin-location-svgrepo-com.svg" width="12" height="12" alt="Location">
                        <?php echo htmlspecialchars($seller->getLocation()); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="seller-public-stats">
            <div class="stat-card">
                <p class="seller-stat-label">Products</p>
                <p class="seller-stat-number" id="sellerProductCount">-</p>
            </div>
            <div class="stat-card">
                <p class="seller-stat-label">Reviews</p>
                <p class="seller-stat-number"><?php echo $reviewCount; ?></p>
                <?php if ($reviewCount > 0): ?>
                    <div class="rating-summary">
                        <?php echo renderStars($avgRating); ?>
                        <span>(<?php echo number_format($avgRating, 1); ?>)</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="stat-card">
                <p class="seller-stat-label">Member Since</p>
                <p class="seller-stat-number"><?php echo date('M Y', strtotime($seller->getCreatedAt())); ?></p>
            </div>
        </div>

        <div class="seller-public-products">
            <h2><?php echo htmlspecialchars($seller->getFullName()); ?>'s Shop</h2>
            <div class="products-grid" id="seller-products-grid">
                <div class="loading-spinner">Loading products...</div>
            </div>
        </div>

        <div class="seller-reviews-section">
            <h2>Customer Reviews</h2>
            <?php if (!empty($initialReviews)): ?>
                <div class="reviews-list" id="reviews-list">
                    <?php foreach ($initialReviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar">
                                        <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="<?php echo htmlspecialchars($review['buyer_name']); ?>" loading="lazy">
                                    </div>
                                    <div>
                                        <div class="reviewer-name"><?php echo htmlspecialchars($review['buyer_name']); ?></div>
                                        <?php echo renderStars($review['rating']); ?>
                                    </div>
                                </div>
                                <div class="review-date"><?php echo formatDate($review['created_at'], 'd M Y'); ?></div>
                            </div>
                            <?php if (!empty($review['comment'])): ?>
                                <div class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($hasMoreReviews): ?>
                    <button class="load-more-btn" id="loadMoreReviews" data-seller="<?php echo $seller_id; ?>" data-offset="5">Load More Reviews</button>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <img src="<?php echo $baseUrl; ?>images/icons/comment-svgrepo-com.svg" width="64" height="64" alt="No reviews">
                    <h3>No Reviews Yet</h3>
                    <p>This seller hasn't received any reviews yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        var sellerProfileId = <?php echo $seller_id; ?>;
    </script>
    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

</body>

</html>