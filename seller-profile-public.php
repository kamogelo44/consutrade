<?php
/*
 * ConsuTrade - Public Seller Profile Page
 * Author: Kamogelo Phale
 * 
 * Displays public seller profile with products and reviews
 * Products loaded via AJAX for consistency with product listings
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

if (!$seller || !$seller instanceof Seller) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$profile_image = $seller->getProfileImageUrl();
$ratingData = $reviewRepo->getSellerRating($seller_id);
$avgRating = $ratingData['avg_rating'] ?? 0;
$reviewCount = $ratingData['review_count'] ?? 0;
$sellerReviews = $reviewRepo->findBySeller($seller_id);

// Breadcrumb setup
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($seller->getFullName()); ?> - Seller Profile | ConsuTrade</title>
    <meta name="description" content="View products and reviews from <?php echo htmlspecialchars($seller->getFullName()); ?> on ConsuTrade">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <style>
        /* ========== PAGE-SPECIFIC STYLES ONLY ========== */
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
            box-shadow: var(--shadow-md);
        }

        .seller-public-avatar {
            width: 120px;
            height: 120px;
            background: var(--white);
            border-radius: var(--radius-round);
            overflow: hidden;
            flex-shrink: 0;
            border: 4px solid rgba(255, 255, 255, 0.3);
            box-shadow: var(--shadow-md);
        }

        .seller-public-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .seller-public-info {
            flex: 1;
        }

        .seller-public-info h1 {
            font-size: var(--font-3xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-sm);
            color: var(--white);
        }

        .seller-public-meta {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            flex-wrap: wrap;
            margin-bottom: var(--spacing-sm);
        }

        .verified-badge,
        .unverified-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            padding: var(--spacing-xs) var(--spacing-md);
            border-radius: var(--radius-round);
            font-size: var(--font-sm);
            font-weight: var(--font-medium);
        }

        .verified-badge {
            background-color: var(--success);
            color: var(--white);
        }

        .unverified-badge {
            background-color: var(--warning);
            color: var(--white);
        }

        .verified-badge img,
        .unverified-badge img {
            filter: brightness(0) invert(1);
        }

        .member-since {
            background: rgba(0, 0, 0, 0.2);
            padding: var(--spacing-xs) var(--spacing-md);
            border-radius: var(--radius-round);
            font-size: var(--font-sm);
            color: var(--white);
        }

        .seller-location {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            background: rgba(0, 0, 0, 0.15);
            padding: var(--spacing-xs) var(--spacing-md);
            border-radius: var(--radius-round);
            font-size: var(--font-sm);
            width: fit-content;
            color: var(--white);
        }

        .seller-location img {
            filter: brightness(0) invert(1);
        }

        .seller-public-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-2xl);
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            transition: transform var(--transition-fast), box-shadow var(--transition-fast);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .stat-card h3 {
            font-size: var(--font-md);
            font-weight: var(--font-medium);
            color: var(--gray-medium);
            margin-bottom: var(--spacing-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-number {
            font-size: var(--font-3xl);
            font-weight: var(--font-bold);
            color: var(--primary-color);
        }

        .stat-text {
            font-size: var(--font-base);
            font-weight: var(--font-medium);
            color: var(--gray-dark);
        }

        .rating-summary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-sm);
        }

        .seller-public-products {
            width: 100%;
        }

        .seller-public-products h2 {
            font-size: var(--font-2xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-xl);
            padding-bottom: var(--spacing-sm);
            border-bottom: 3px solid var(--primary-color);
            color: var(--gray-dark);
            display: inline-block;
        }

        .seller-public-products .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: var(--spacing-lg);
        }

        .seller-reviews-section {
            margin-top: var(--spacing-2xl);
            width: 100%;
        }

        .seller-reviews-section h2 {
            font-size: var(--font-2xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-xl);
            padding-bottom: var(--spacing-sm);
            border-bottom: 3px solid var(--primary-color);
            color: var(--gray-dark);
            display: inline-block;
        }

        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }

        .review-card {
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            transition: box-shadow var(--transition-fast);
        }

        .review-card:hover {
            box-shadow: var(--shadow-md);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
            padding-bottom: var(--spacing-sm);
            border-bottom: 1px solid var(--border-light);
        }

        .reviewer-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .reviewer-avatar {
            width: 40px;
            height: 40px;
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
            font-weight: var(--font-bold);
            color: var(--dark-bg);
        }

        .review-date {
            font-size: var(--font-xs);
            color: var(--gray-light);
        }

        .review-comment {
            font-size: var(--font-md);
            line-height: 1.5;
            color: var(--gray-dark);
            margin-top: var(--spacing-md);
        }

        .empty-state {
            text-align: center;
            padding: var(--spacing-2xl);
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
        }

        .empty-state img {
            opacity: 0.4;
            margin-bottom: var(--spacing-lg);
        }

        .empty-state h3 {
            font-size: var(--font-xl);
            font-weight: var(--font-semibold);
            margin-bottom: var(--spacing-sm);
            color: var(--dark-bg);
        }

        .empty-state p {
            color: var(--gray-medium);
        }

        @media (max-width: 768px) {
            .public-seller-profile-container {
                padding: var(--spacing-lg);
            }

            .seller-public-header {
                flex-direction: column;
                text-align: center;
            }

            .seller-public-meta {
                justify-content: center;
            }

            .seller-location {
                margin: 0 auto;
            }

            .seller-public-stats {
                grid-template-columns: 1fr;
            }

            .seller-public-products .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .seller-public-products .products-grid {
                grid-template-columns: 1fr;
            }
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
                        <span class="verified-badge">
                            <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16" height="16" alt="Verified"> Verified Seller
                        </span>
                    <?php else: ?>
                        <span class="unverified-badge">
                            <img src="<?php echo $baseUrl; ?>images/icons/not-verified-svgrepo-com.svg" width="16" height="16" alt="Unverified"> Unverified Seller
                        </span>
                    <?php endif; ?>
                    <span class="member-since">Member since <?php echo date('d M Y', strtotime($seller->getCreatedAt())); ?></span>
                </div>
                <?php if ($seller->getLocation()): ?>
                    <div class="seller-location">
                        <img src="<?php echo $baseUrl; ?>images/icons/pin-location-svgrepo-com.svg" width="14" height="14" alt="Location">
                        <?php echo htmlspecialchars($seller->getLocation()); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="seller-public-stats">
            <div class="stat-card">
                <h3>Products</h3>
                <p class="stat-number" id="sellerProductCount">-</p>
            </div>
            <div class="stat-card">
                <h3>Reviews</h3>
                <p class="stat-number"><?php echo $reviewCount; ?></p>
                <?php if ($reviewCount > 0): ?>
                    <div class="rating-summary">
                        <?php echo renderStars($avgRating); ?>
                        <span class="rating-count">(<?php echo number_format($avgRating, 1); ?>)</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="stat-card">
                <h3>Member Since</h3>
                <p class="stat-text"><?php echo date('d M Y', strtotime($seller->getCreatedAt())); ?></p>
            </div>
        </div>

        <div class="seller-public-products">
            <h2>Products from <?php echo htmlspecialchars($seller->getFullName()); ?></h2>
            <div class="products-grid" id="seller-products-grid">
                <div class="loading-spinner">Loading products...</div>
            </div>
        </div>

        <div class="seller-reviews-section">
            <h2>Customer Reviews</h2>
            <?php if (!empty($sellerReviews)): ?>
                <div class="reviews-list">
                    <?php foreach ($sellerReviews as $review): ?>
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
            <?php else: ?>
                <div class="empty-state">
                    <img src="<?php echo $baseUrl; ?>images/icons/comment-svgrepo-com.svg" width="64" height="64" alt="No reviews">
                    <h3>No Reviews Yet</h3>
                    <p>This seller hasn't received any reviews yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php $load_products_js = true; ?>
    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

    <script>
        $(function() {
            var sellerId = <?php echo $seller_id; ?>;

            function loadSellerProducts() {
                var $grid = $('#seller-products-grid');

                $.ajax({
                    url: baseUrl + 'php/endpoints/products/get-products.php?limit=12&seller_id=' + sellerId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data.success && data.products && data.products.length > 0) {
                            $('#sellerProductCount').text(data.total || data.products.length);
                            displayProducts(data.products, '#seller-products-grid');
                        } else {
                            $('#sellerProductCount').text(0);
                            $grid.html(
                                '<div class="empty-state">' +
                                '<img src="' + baseUrl + 'images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No products" loading="lazy">' +
                                '<h3>No Products Yet</h3>' +
                                '<p>This seller has no products available at the moment.</p>' +
                                '</div>'
                            );
                        }
                    },
                    error: function() {
                        $('#sellerProductCount').text(0);
                        $grid.html(
                            '<div class="empty-state">' +
                            '<img src="' + baseUrl + 'images/icons/error-svgrepo-com.svg" width="64" height="64" alt="Error" loading="lazy">' +
                            '<h3>Something went wrong</h3>' +
                            '<p>Error loading products. Please refresh the page.</p>' +
                            '</div>'
                        );
                    }
                });
            }

            loadSellerProducts();
        });
    </script>

</body>

</html>