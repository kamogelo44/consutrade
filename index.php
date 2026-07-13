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
    <title>ConsuTrade - South African Marketplace</title>
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
                <span>Account created! Please check your email to verify your account before logging in.</span>
            </div>
        <?php endif; ?>

        <!-- HERO -->
        <section class="hero">
            <div class="hero-grid">
                <div class="hero-content">
                    <div class="hero-tag">
                        <span>South African marketplace</span>
                    </div>
                    <h1>Trade with <span class="hero-highlight">real people</span> in your area</h1>
                    <p class="hero-subtitle">Buy and sell with verified local traders. No middlemen, no delivery fees — just your community.</p>
                    <div class="hero-actions">
                        <a href="product-listings.php" class="hero-btn hero-btn-primary">Browse products</a>
                        <button class="hero-btn hero-btn-secondary" id="primary-btn">Start selling</button>
                    </div>
                    <div class="hero-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($activeSellers); ?></span>
                            <span class="stat-label">Active traders</span>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($totalListings); ?></span>
                            <span class="stat-label">Items listed</span>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <span class="stat-number">98%</span>
                            <span class="stat-label">Trades completed</span>
                        </div>
                    </div>
                    <div class="hero-payment">
                        <span>Secure payments with</span>
                        <img src="images/icons/Payfast logo.svg" alt="PayFast" width="60" height="18">
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="hero-card" id="hero-products">
                        <div class="hero-card-header">
                            <span>Latest listings</span>
                            <span>New</span>
                        </div>
                        <div class="hero-card-body">
                            <div class="skeleton-row">
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

        <!-- CATEGORIES -->
        <section class="category-section">
            <div class="category-container">
                <div class="category-header">
                    <span class="section-tag">Browse</span>
                    <h2>Shop by category</h2>
                    <p>Find what you need from local sellers</p>
                </div>
                <div class="category-grid">
                    <a href="product-listings.php?category=clothing" class="category-card">
                        <div class="cat-image">
                            <picture>
                                <source srcset="images/categories/clothing.webp" type="image/webp">
                                <img src="images/categories/clothing.jpg" alt="Clothing" width="64" height="64" loading="lazy">
                            </picture>
                        </div>
                        <span class="cat-name">Clothing</span>
                        <span class="cat-count">Fashion &amp; accessories</span>
                    </a>
                    <a href="product-listings.php?category=electronics" class="category-card">
                        <div class="cat-image">
                            <picture>
                                <source srcset="images/categories/electronics.webp" type="image/webp">
                                <img src="images/categories/electronics.jpg" alt="Electronics" width="64" height="64" loading="lazy">
                            </picture>
                        </div>
                        <span class="cat-name">Electronics</span>
                        <span class="cat-count">Phones &amp; gadgets</span>
                    </a>
                    <a href="product-listings.php?category=food" class="category-card">
                        <div class="cat-image">
                            <picture>
                                <source srcset="images/categories/food.webp" type="image/webp">
                                <img src="images/categories/food.jpg" alt="Food" width="64" height="64" loading="lazy">
                            </picture>
                        </div>
                        <span class="cat-name">Food &amp; Drinks</span>
                        <span class="cat-count">Groceries &amp; beverages</span>
                    </a>
                    <a href="product-listings.php?category=furniture" class="category-card">
                        <div class="cat-image">
                            <picture>
                                <source srcset="images/categories/furniture.webp" type="image/webp">
                                <img src="images/categories/furniture.jpg" alt="Furniture" width="64" height="64" loading="lazy">
                            </picture>
                        </div>
                        <span class="cat-name">Furniture</span>
                        <span class="cat-count">Home &amp; office</span>
                    </a>
                    <a href="product-listings.php?category=beauty" class="category-card">
                        <div class="cat-image">
                            <picture>
                                <source srcset="images/categories/beauty.webp" type="image/webp">
                                <img src="images/categories/beauty.jpg" alt="Beauty" width="64" height="64" loading="lazy">
                            </picture>
                        </div>
                        <span class="cat-name">Beauty &amp; Health</span>
                        <span class="cat-count">Cosmetics &amp; wellness</span>
                    </a>
                    <a href="product-listings.php?category=other" class="category-card category-all">
                        <div class="cat-image cat-all">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14M12 5l7 7-7 7" />
                            </svg>
                        </div>
                        <span class="cat-name">Other</span>
                        <span class="cat-count">Everything else</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- FEATURED PRODUCTS -->
        <section class="featured">
            <div class="featured-header">
                <div>
                    <span class="section-tag">Recent</span>
                    <h2 class="section-heading">Newly listed</h2>
                    <p class="section-subtitle">From sellers across South Africa</p>
                </div>
                <a href="product-listings.php" class="view-all-link">View all →</a>
            </div>
            <div class="prod-grid" id="featured-products-grid">
                <div class="loading-spinner">Loading products...</div>
            </div>
        </section>

        <!-- TOP SELLERS -->
        <section class="sellers-section">
            <div class="sellers-header">
                <span class="section-tag">Community</span>
                <h2>Top verified sellers</h2>
            </div>
            <div class="sellers-grid" id="sellers-grid">
                <div class="loading-spinner">Loading sellers...</div>
            </div>
        </section>

        <!-- DIFFERENCE -->
        <section class="difference-section">
            <div class="difference-container">
                <div class="difference-content">
                    <span class="section-tag">Why ConsuTrade</span>
                    <h2>Built for local trade</h2>
                    <ul class="difference-list">
                        <li>
                            <div class="diff-icon diff-verified">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                    <polyline points="22 4 12 14.01 9 11.01" />
                                </svg>
                            </div>
                            <div>
                                <strong>Verified identity</strong>
                                <p>Sellers verify with SA ID. You know who you're trading with.</p>
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
                                <strong>No delivery fees</strong>
                                <p>Find traders in your area. Collect in person or arrange your own delivery.</p>
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
                                <strong>PayFast protection</strong>
                                <p>Pay securely through South Africa's trusted payment gateway.</p>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="difference-testimonial">
                    <blockquote>
                        <p>"I was selling through WhatsApp groups. ConsuTrade made it easier — people find my shop without me spamming groups."</p>
                        <cite>Thabo M., Soweto</cite>
                    </blockquote>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="cta-section">
            <div class="cta-container">
                <h2>Ready to start trading?</h2>
                <p>Join thousands of South Africans buying and selling locally.</p>
                <div class="cta-buttons">
                    <a href="register.php" class="cta-btn cta-btn-primary">Create account</a>
                    <a href="about.php" class="cta-btn cta-btn-secondary">Learn more</a>
                </div>
            </div>
        </section>

    </main>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

</body>

</html>