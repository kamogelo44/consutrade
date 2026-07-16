<?php
/*
 * ConsuTrade - Seller Information Page
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
include __DIR__ . '/includes/functions.php';

// If already a seller, redirect to dashboard
if ($isLoggedIn && isset($currentUser) && $currentUser->hasRole('seller')) {
    header('Location: ' . $baseUrl . 'admin/seller-dashboard.php');
    exit;
}

$page_js = 'sell.js';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php t('sell'); ?> on ConsuTrade - Start Selling Online</title>
    <meta name="description" content="Stop selling on WhatsApp and Facebook. Get your own storefront, verified badge, and secure PayFast payments on ConsuTrade.">
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="sell-hero">
            <div class="sell-hero-content">
                <div class="sell-hero-badge">
                    <span><?php t('proudly_south_african'); ?></span>
                </div>
                <h1><?php t('stop_selling_whatsapp'); ?></h1>
                <p class="sell-hero-subtitle">
                    <?php t('sell_hero_subtitle'); ?>
                </p>
                <?php if ($isLoggedIn && isset($currentUser) && $currentUser->hasRole('buyer') && !$currentUser->hasRole('seller')): ?>
                    <button class="sell-hero-btn" id="upgradeToSellerBtn"><?php t('add_seller_access'); ?></button>
                <?php else: ?>
                    <div class="sell-hero-actions">
                        <button class="sell-hero-btn" id="sellerRegisterBtn"><?php t('create_seller_account'); ?></button>
                        <button class="sell-hero-btn-secondary" id="loginInsteadBtn"><?php t('already_have_account_login'); ?></button>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Problem vs Solution Section -->
        <section class="sell-comparison">
            <div class="section-header">
                <div class="section-tag"><?php t('why_switch'); ?></div>
                <h2 class="section-heading"><?php t('better_way_to_sell'); ?></h2>
                <p class="section-subtitle"><?php t('sell_comparison_subtitle'); ?></p>
            </div>
            <div class="sell-comparison-grid">
                <div class="sell-comparison-card sell-comparison-old">
                    <h3><?php t('the_old_way'); ?></h3>
                    <ul>
                        <li><?php t('old_way_ghost'); ?></li>
                        <li><?php t('old_way_fake_proof'); ?></li>
                        <li><?php t('old_way_no_proof'); ?></li>
                        <li><?php t('old_way_scroll'); ?></li>
                        <li><?php t('old_way_cash_risks'); ?></li>
                    </ul>
                </div>
                <div class="sell-comparison-card sell-comparison-new">
                    <h3><?php t('the_consutrade_way'); ?></h3>
                    <ul>
                        <li><?php t('consutrade_way_storefront'); ?></li>
                        <li><?php t('consutrade_way_payfast'); ?></li>
                        <li><?php t('consutrade_way_verified'); ?></li>
                        <li><?php t('consutrade_way_dashboard'); ?></li>
                        <li><?php t('consutrade_way_secure'); ?></li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section class="sell-steps">
            <div class="section-header">
                <div class="section-tag"><?php t('get_started'); ?></div>
                <h2 class="section-heading"><?php t('start_selling_minutes'); ?></h2>
                <p class="section-subtitle"><?php t('no_paperwork'); ?></p>
            </div>
            <div class="sell-steps-grid">
                <div class="sell-step" data-step="01">
                    <div class="sell-step-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/register-svgrepo-com.svg" width="40" height="40" alt="Register" loading="lazy">
                    </div>
                    <h3><?php t('step_create_account'); ?></h3>
                    <p><?php t('step_create_account_desc'); ?></p>
                </div>
                <div class="sell-step" data-step="02">
                    <div class="sell-step-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="40" height="40" alt="List" loading="lazy">
                    </div>
                    <h3><?php t('step_list_products'); ?></h3>
                    <p><?php t('step_list_products_desc'); ?></p>
                </div>
                <div class="sell-step" data-step="03">
                    <div class="sell-step-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/cash-atm-svgrepo-com.svg" width="40" height="40" alt="Get paid" loading="lazy">
                    </div>
                    <h3><?php t('step_get_paid'); ?></h3>
                    <p><?php t('step_get_paid_desc'); ?></p>
                </div>
            </div>
        </section>

        <!-- What You Need -->
        <section class="sell-requirements">
            <div class="sell-requirements-container">
                <div class="section-tag"><?php t('requirements'); ?></div>
                <h2><?php t('what_you_need'); ?></h2>
                <div class="sell-requirements-grid">
                    <div class="sell-requirement">
                        <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="24" height="24" alt="ID" loading="lazy">
                        <span><?php t('requirement_id'); ?></span>
                    </div>
                    <div class="sell-requirement">
                        <img src="<?php echo $baseUrl; ?>images/icons/email-svgrepo-com.svg" width="24" height="24" alt="Email" loading="lazy">
                        <span><?php t('requirement_email'); ?></span>
                    </div>
                    <div class="sell-requirement">
                        <img src="<?php echo $baseUrl; ?>images/icons/phone-call-svgrepo-com.svg" width="24" height="24" alt="Phone" loading="lazy">
                        <span><?php t('requirement_phone'); ?></span>
                    </div>
                    <div class="sell-requirement">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="24" height="24" alt="Products" loading="lazy">
                        <span><?php t('requirement_products'); ?></span>
                    </div>
                </div>
                <p class="sell-requirements-note"><?php t('requirements_note'); ?></p>
            </div>
        </section>

        <!-- CTA -->
        <section class="sell-cta">
            <div class="sell-cta-container">
                <h2><?php t('customers_are_waiting'); ?></h2>
                <p><?php t('join_traders_switched'); ?></p>
                <?php if ($isLoggedIn && isset($currentUser) && $currentUser->hasRole('buyer') && !$currentUser->hasRole('seller')): ?>
                    <button class="sell-cta-btn" id="upgradeToSellerBtn2"><?php t('add_seller_access'); ?></button>
                <?php else: ?>
                    <button class="sell-cta-btn" id="createSellerBtn"><?php t('create_seller_account'); ?></button>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

</body>

</html>