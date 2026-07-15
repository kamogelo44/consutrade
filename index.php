<?php
/*
 * ConsuTrade - Homepage
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
include __DIR__ . '/includes/functions.php';

// Get stats
$activeSellers = $userRepo->countByRole('seller');
$totalListings = $productRepo->countAll();

$load_products_js = true;
$page_js = 'index.js';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConsuTrade - <?php t('south_african_marketplace'); ?></title>
    <meta name="description" content="Buy and sell from local South African traders. Secure payments with PayFast.">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main class="content">
        <?php include 'includes/flash-message.php'; ?>

        <?php if (isset($_GET['verified']) && $_GET['verified'] === 'pending'): ?>
            <div class="verification-notice">
                <img src="images/icons/email-svgrepo-com.svg" width="20" height="20" alt="Email">
                <span><?php t('account_created_verify'); ?></span>
            </div>
        <?php endif; ?>

        <!-- ============================================================
        HERO — STATIC CONTENT RENDERED BY PHP
        ============================================================ -->
        <section class="hero">
            <div class="hero-grid">
                <div class="hero-content">
                    <div class="hero-tag">
                        <span><?php t('local_trade_tagline'); ?></span>
                    </div>
                    <h1><?php t('your_community_marketplace'); ?></h1>
                    <p class="hero-subtitle"><?php t('hero_subtitle_text'); ?></p>
                    <div class="hero-actions">
                        <a href="product-listings.php" class="hero-btn hero-btn-primary"><?php t('browse_local_goods'); ?></a>
                        <button class="hero-btn hero-btn-secondary" id="primary-btn"><?php t('start_selling'); ?></button>
                    </div>
                    <!-- Hero Stats — Skeleton (dynamic data from DB) -->
                    <div class="hero-stats" id="hero-stats-container">
                        <div class="stat-item">
                            <span class="stat-number skeleton skeleton-stat-number"></span>
                            <span class="stat-label skeleton skeleton-stat-label"></span>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <span class="stat-number skeleton skeleton-stat-number"></span>
                            <span class="stat-label skeleton skeleton-stat-label"></span>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <span class="stat-number skeleton skeleton-stat-number"></span>
                            <span class="stat-label skeleton skeleton-stat-label"></span>
                        </div>
                    </div>
                    <div class="hero-payment">
                        <span><?php t('secure_payments'); ?></span>
                        <img src="images/icons/Payfast logo.svg" alt="PayFast" width="60" height="18" loading="lazy">
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="hero-card" id="hero-products">
                        <div class="hero-card-header">
                            <span><?php t('latest_listings'); ?></span>
                            <span><?php t('new'); ?></span>
                        </div>
                        <div class="hero-card-body">
                            <!-- Skeleton for hero products -->
                            <div class="skeleton-row" id="hero-products-skeleton">
                                <div class="skeleton skeleton-image"></div>
                                <div style="flex:1;">
                                    <div class="skeleton skeleton-text" style="width:70%;"></div>
                                    <div class="skeleton skeleton-text" style="width:40%;margin-top:4px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================
        CATEGORIES — STATIC CONTENT RENDERED BY PHP
        ============================================================ -->
        <section class="category-section">
            <div class="category-container">
                <div class="category-header">
                    <span class="section-tag"><?php t('browse'); ?></span>
                    <h2><?php t('shop_by_category_text'); ?></h2>
                    <p><?php t('find_what_you_need_text'); ?></p>
                </div>
                <div class="category-grid" id="categories-grid">
                    <?php
                    $categories = [
                        ['name' => 'clothing', 'desc' => 'fashion_accessories', 'link' => 'product-listings.php?category=clothing', 'img' => 'clothing'],
                        ['name' => 'electronics', 'desc' => 'phones_gadgets', 'link' => 'product-listings.php?category=electronics', 'img' => 'electronics'],
                        ['name' => 'food_drinks', 'desc' => 'groceries_beverages', 'link' => 'product-listings.php?category=food', 'img' => 'food'],
                        ['name' => 'furniture', 'desc' => 'home_office', 'link' => 'product-listings.php?category=furniture', 'img' => 'furniture'],
                        ['name' => 'beauty_health', 'desc' => 'cosmetics_wellness', 'link' => 'product-listings.php?category=beauty', 'img' => 'beauty'],
                        ['name' => 'other', 'desc' => 'everything_else', 'link' => 'product-listings.php?category=other', 'img' => 'other'],
                    ];
                    foreach ($categories as $cat):
                        $isLast = $cat['name'] === 'other';
                        $cardClass = $isLast ? 'category-card category-all' : 'category-card';
                    ?>
                        <a href="<?php echo $cat['link']; ?>" class="<?php echo $cardClass; ?>">
                            <?php if ($isLast): ?>
                                <div class="cat-image cat-all">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M5 12h14M12 5l7 7-7 7" />
                                    </svg>
                                </div>
                            <?php else: ?>
                                <div class="cat-image">
                                    <picture>
                                        <source srcset="images/categories/<?php echo $cat['img']; ?>.webp" type="image/webp">
                                        <img src="images/categories/<?php echo $cat['img']; ?>.jpg" alt="<?php t($cat['name']); ?>" width="64" height="64" loading="lazy">
                                    </picture>
                                </div>
                            <?php endif; ?>
                            <span class="cat-name"><?php t($cat['name']); ?></span>
                            <span class="cat-count"><?php t($cat['desc']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ============================================================
        FEATURED PRODUCTS — DYNAMIC (Skeleton + AJAX)
        ============================================================ -->
        <section class="featured">
            <div class="featured-header">
                <div>
                    <span class="section-tag"><?php t('recent'); ?></span>
                    <h2 class="section-heading"><?php t('newly_listed_text'); ?></h2>
                    <p class="section-subtitle"><?php t('from_sellers_across_sa'); ?></p>
                </div>
                <a href="product-listings.php" class="view-all-link"><?php t('view_all_text'); ?> →</a>
            </div>
            <div class="prod-grid" id="featured-products-grid">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <div class="prod-card skeleton-card">
                        <div class="img-container skeleton skeleton-product-image"></div>
                        <div class="prod-info-container">
                            <div class="skeleton skeleton-product-title"></div>
                            <div class="skeleton skeleton-product-price"></div>
                            <div class="skeleton skeleton-product-meta"></div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </section>

        <!-- ============================================================
        TOP SELLERS — DYNAMIC (Skeleton + AJAX)
        ============================================================ -->
        <section class="sellers-section">
            <div class="sellers-header">
                <span class="section-tag"><?php t('community'); ?></span>
                <h2><?php t('top_verified_sellers'); ?></h2>
            </div>
            <div class="sellers-grid" id="sellers-grid">
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <div class="seller-card skeleton-card">
                        <div class="seller-card-top">
                            <div class="seller-avatar skeleton skeleton-seller-avatar"></div>
                            <div>
                                <div class="skeleton skeleton-seller-name"></div>
                                <div class="skeleton skeleton-seller-location"></div>
                            </div>
                        </div>
                        <div class="seller-card-stats">
                            <div>
                                <div class="skeleton skeleton-seller-stat"></div>
                                <div class="skeleton skeleton-seller-stat-label"></div>
                            </div>
                            <div>
                                <div class="skeleton skeleton-seller-stat"></div>
                                <div class="skeleton skeleton-seller-stat-label"></div>
                            </div>
                            <div>
                                <div class="skeleton skeleton-seller-stat"></div>
                                <div class="skeleton skeleton-seller-stat-label"></div>
                            </div>
                        </div>
                        <div class="skeleton skeleton-seller-link"></div>
                    </div>
                <?php endfor; ?>
            </div>
        </section>

        <!-- ============================================================
        DIFFERENCE — STATIC CONTENT RENDERED BY PHP
        ============================================================ -->
        <section class="difference-section">
            <div class="difference-container">
                <div class="difference-content">
                    <span class="section-tag"><?php t('why_consutrade_text'); ?></span>
                    <h2><?php t('built_for_local_trade_text'); ?></h2>
                    <ul class="difference-list">
                        <li>
                            <div class="diff-icon diff-verified">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                    <polyline points="22 4 12 14.01 9 11.01" />
                                </svg>
                            </div>
                            <div>
                                <strong><?php t('verified_identity_text'); ?></strong>
                                <p><?php t('verified_identity_desc_text'); ?></p>
                            </div>
                        </li>
                        <li>
                            <div class="diff-icon diff-location">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                                    <circle cx="12" cy="10" r="3" />
                                </svg>
                            </div>
                            <div>
                                <strong><?php t('no_delivery_fees_text'); ?></strong>
                                <p><?php t('no_delivery_fees_desc_text'); ?></p>
                            </div>
                        </li>
                        <li>
                            <div class="diff-icon diff-payment">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
                                    <line x1="1" y1="10" x2="23" y2="10" />
                                </svg>
                            </div>
                            <div>
                                <strong><?php t('payfast_protection_text'); ?></strong>
                                <p><?php t('payfast_protection_desc_text'); ?></p>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="difference-testimonial">
                    <blockquote>
                        <p><?php t('testimonial_text'); ?></p>
                        <cite><?php t('testimonial_author_text'); ?></cite>
                    </blockquote>
                </div>
            </div>
        </section>

        <!-- ============================================================
        CTA — STATIC CONTENT RENDERED BY PHP
        ============================================================ -->
        <section class="cta-section">
            <div class="cta-container">
                <h2><?php t('ready_to_start_text'); ?></h2>
                <p><?php t('join_thousands_text'); ?></p>
                <div class="cta-buttons">
                    <a href="register.php" class="cta-btn cta-btn-primary"><?php t('create_account_text'); ?></a>
                    <a href="about.php" class="cta-btn cta-btn-secondary"><?php t('learn_more_text'); ?></a>
                </div>
            </div>
        </section>

    </main>

    <!-- Pass data to JavaScript -->
    <script>
        var heroStats = {
            activeSellers: <?php echo json_encode($activeSellers); ?>,
            totalListings: <?php echo json_encode($totalListings); ?>,
            tradesCompleted: 98
        };
    </script>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

</body>

</html>