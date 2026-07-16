<?php
/*
 * ConsuTrade - About Us Page
 * Author: Kamogelo Phale
 * 
 * Information about the platform - C2C marketplace for South African informal traders
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php t('about'); ?> - ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    <meta name="description" content="Learn about ConsuTrade - South Africa's C2C marketplace connecting informal traders with buyers">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>
    <?php include 'includes/breadcrumb.php'; ?>

    <main class="about-container">
        <div class="about-header">
            <div class="about-header-badge">
                <span><?php t('proudly_south_african'); ?></span>
            </div>
            <h1><?php t('about_consutrade'); ?></h1>
            <p><?php t('about_subtitle'); ?></p>
        </div>

        <div class="about-content">
            <div class="about-section">
                <h2><?php t('what_this_is'); ?></h2>
                <p>ConsuTrade is a peer-to-peer marketplace connecting informal traders — from township vendors to home-based entrepreneurs — with buyers across South Africa.</p>
                <p>Most informal traders sell through WhatsApp groups and Facebook Marketplace. Those platforms weren't built for trading. There's no payment protection, no seller verification, and no way to prove you're legitimate. ConsuTrade fixes that.</p>
            </div>

            <div class="about-section about-section-highlight">
                <div class="about-highlight-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 6v6l4 2" />
                    </svg>
                </div>
                <h2><?php t('why_it_exists'); ?></h2>
                <p>South Africa's informal economy is worth nearly <strong>R900 billion</strong> and employs almost <strong>20% of the working population</strong>. Yet platforms like Takealot and BobShop require business registration documents that most informal traders don't have.</p>
                <p>ConsuTrade was built specifically for these traders — no business registration needed, just a South African ID.</p>
            </div>

            <div class="about-section">
                <h2><?php t('how_its_different'); ?></h2>
                <ul class="about-difference-list">
                    <li>
                        <span class="diff-icon-about diff-verified-about">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                <polyline points="22 4 12 14.01 9 11.01" />
                            </svg>
                        </span>
                        <div>
                            <strong><?php t('diff_verified_sellers'); ?></strong>
                            <p><?php t('diff_verified_sellers_desc'); ?></p>
                        </div>
                    </li>
                    <li>
                        <span class="diff-icon-about diff-payment-about">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
                                <line x1="1" y1="10" x2="23" y2="10" />
                            </svg>
                        </span>
                        <div>
                            <strong><?php t('diff_payfast'); ?></strong>
                            <p><?php t('diff_payfast_desc'); ?></p>
                        </div>
                    </li>
                    <li>
                        <span class="diff-icon-about diff-data-about">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
                                <line x1="8" y1="21" x2="16" y2="21" />
                                <line x1="12" y1="17" x2="12" y2="21" />
                            </svg>
                        </span>
                        <div>
                            <strong><?php t('diff_low_data'); ?></strong>
                            <p><?php t('diff_low_data_desc'); ?></p>
                        </div>
                    </li>
                    <li>
                        <span class="diff-icon-about diff-tracking-about">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 12h-4l-3 9-4-18-3 9H2" />
                            </svg>
                        </span>
                        <div>
                            <strong><?php t('diff_order_tracking'); ?></strong>
                            <p><?php t('diff_order_tracking_desc'); ?></p>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="about-section about-section-contact">
                <h2><?php t('contact_us'); ?></h2>
                <div class="about-contact">
                    <a href="mailto:support@consutrade.co.za" class="about-contact-item">
                        <img src="<?php echo $baseUrl; ?>images/icons/email-svgrepo-com.svg" width="20" height="20" alt="Email">
                        support@consutrade.co.za
                    </a>
                    <div class="about-contact-item">
                        <img src="<?php echo $baseUrl; ?>images/icons/pin-location-svgrepo-com.svg" width="20" height="20" alt="Location">
                        <?php t('limpopo_south_africa'); ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

</body>

</html>