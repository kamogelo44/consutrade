<?php
/*
 * ConsuTrade - Seller Information Page
 * Author: Kamogelo Phale
 * 
 * Information page for potential sellers to learn about selling on ConsuTrade
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
include __DIR__ . '/includes/functions.php';

// If user is already a seller, redirect to seller dashboard
if ($isLoggedIn && isset($currentUser) && $currentUser->hasRole('seller')) {
    header('Location: ' . $baseUrl . 'admin/seller-dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell on ConsuTrade - Start Selling Online</title>
    <meta name="description" content="Stop selling on WhatsApp and Facebook. Get your own storefront, verified badge, and secure PayFast payments on ConsuTrade.">
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="sell-hero">
            <div class="sell-hero-grid">
                <div class="sell-hero-content">
                    <h1>Stop selling in WhatsApp groups</h1>
                    <p class="sell-hero-subtitle">
                        Get your own storefront, earn a verified badge, and get paid securely through PayFast.
                        No more "payment proof" screenshots. No more ghosting.
                    </p>
                    <?php if ($isLoggedIn && isset($currentUser) && $currentUser->hasRole('buyer') && !$currentUser->hasRole('seller')): ?>
                        <div class="sell-hero-actions">
                            <button class="sell-hero-btn" id="upgradeToSellerBtn">Add Seller Access — It's Free</button>
                        </div>
                    <?php else: ?>
                        <div class="sell-hero-actions">
                            <button class="sell-hero-btn" id="sellerRegisterBtn">Create Seller Account — It's Free</button>
                            <button class="sell-hero-btn-secondary" onclick="openModal($('#login-modal'))">Already have an account? Login</button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="sell-hero-visual">
                    <div class="sell-hero-card">
                        <div class="sell-hero-card-header">
                            <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16" height="16" alt="Verified">
                            <span>Verified Seller</span>
                        </div>
                        <div class="sell-hero-card-body">
                            <div class="sell-hero-product">
                                <div class="sell-hero-product-img"></div>
                                <div>
                                    <strong>Handmade Baskets</strong>
                                    <span>R350</span>
                                </div>
                            </div>
                            <div class="sell-hero-product">
                                <div class="sell-hero-product-img"></div>
                                <div>
                                    <strong>Shweshwe Dress</strong>
                                    <span>R580</span>
                                </div>
                            </div>
                        </div>
                        <div class="sell-hero-card-footer">
                            <img src="<?php echo $baseUrl; ?>images/icons/Payfast logo.svg" alt="PayFast" width="55" height="auto">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Problem vs Solution Section -->
        <section class="sell-comparison">
            <div class="section-header">
                <h2 class="section-heading">There's a better way to sell</h2>
                <p class="section-subtitle">WhatsApp and Facebook Marketplace weren't built for traders. ConsuTrade was.</p>
            </div>
            <div class="sell-comparison-grid">
                <!-- The Old Way -->
                <div class="sell-comparison-card sell-comparison-old">
                    <h3>The Old Way</h3>
                    <ul>
                        <li>Customers ghost after asking "how much"</li>
                        <li>Fake "payment proof" screenshots</li>
                        <li>No way to prove you're legitimate</li>
                        <li>Scrolling through endless chats to find orders</li>
                        <li>Cash deposits and e-wallet risks</li>
                    </ul>
                </div>
                <!-- The ConsuTrade Way -->
                <div class="sell-comparison-card sell-comparison-new">
                    <h3>The ConsuTrade Way</h3>
                    <ul>
                        <li>Your own storefront with all your products</li>
                        <li>PayFast confirms every payment — no screenshots needed</li>
                        <li>Verified seller badge shows buyers you're real</li>
                        <li>Dashboard tracks every order from pending to completed</li>
                        <li>Secure payments directly to your account</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section class="sell-steps">
            <div class="section-header">
                <h2 class="section-heading">Start selling in minutes</h2>
                <p class="section-subtitle">No paperwork. No registration fees. Just your SA ID and your products.</p>
            </div>
            <div class="sell-steps-grid">
                <div class="sell-step" data-step="01">
                    <div class="sell-step-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/register-svgrepo-com.svg" width="40" height="40" alt="Register">
                    </div>
                    <h3>Create your account</h3>
                    <p>Sign up as a seller. Verify with your SA ID to earn your trusted badge.</p>
                </div>
                <div class="sell-step" data-step="02">
                    <div class="sell-step-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="40" height="40" alt="List">
                    </div>
                    <h3>List your products</h3>
                    <p>Upload photos, set prices, and share your story. Your listings go live instantly.</p>
                </div>
                <div class="sell-step" data-step="03">
                    <div class="sell-step-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/cash-atm-svgrepo-com.svg" width="40" height="40" alt="Get paid">
                    </div>
                    <h3>Get paid securely</h3>
                    <p>Buyers pay through PayFast. You receive payment confirmation. No scams.</p>
                </div>
            </div>
        </section>

        <!-- What You Need -->
        <section class="sell-requirements">
            <div class="sell-requirements-container">
                <h2>What you need to get started</h2>
                <div class="sell-requirements-grid">
                    <div class="sell-requirement">
                        <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="24" height="24" alt="ID">
                        <span>South African ID</span>
                    </div>
                    <div class="sell-requirement">
                        <img src="<?php echo $baseUrl; ?>images/icons/email-svgrepo-com.svg" width="24" height="24" alt="Email">
                        <span>Email address</span>
                    </div>
                    <div class="sell-requirement">
                        <img src="<?php echo $baseUrl; ?>images/icons/phone-call-svgrepo-com.svg" width="24" height="24" alt="Phone">
                        <span>Phone number</span>
                    </div>
                    <div class="sell-requirement">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="24" height="24" alt="Products">
                        <span>Products to sell</span>
                    </div>
                </div>
                <p class="sell-requirements-note">You can start listing products while verification is pending. Verification helps build trust with buyers.</p>
            </div>
        </section>

        <!-- CTA -->
        <section class="sell-cta">
            <div class="sell-cta-container">
                <h2>Your customers are waiting</h2>
                <p>Join South African traders who've already made the switch from WhatsApp to ConsuTrade.</p>
                <?php if ($isLoggedIn && isset($currentUser) && $currentUser->hasRole('buyer') && !$currentUser->hasRole('seller')): ?>
                    <button class="sell-cta-btn" id="upgradeToSellerBtn2">Add Seller Access — It's Free</button>
                <?php else: ?>
                    <button class="sell-cta-btn" id="createSellerBtn">Create Seller Account — It's Free</button>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

    <script>
        window.addEventListener('load', function() {
            var $sellerRegisterBtn = $('#sellerRegisterBtn');
            var $createSellerBtn = $('#createSellerBtn');
            var $upgradeBtn = $('#upgradeToSellerBtn');
            var $upgradeBtn2 = $('#upgradeToSellerBtn2');
            var $registerModal = $('#register-modal');
            var $sellerRadio = $('#seller');

            function openSellerRegisterModal() {
                if ($sellerRadio.length) {
                    $sellerRadio.prop('checked', true);
                }

                var isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;
                var hasSellerRole = <?php echo isset($currentUser) ? json_encode($currentUser->hasRole('seller')) : 'false'; ?>;

                if (isLoggedIn && hasSellerRole) {
                    window.location.href = baseUrl + 'admin/seller-dashboard.php';
                    return;
                }

                if (isLoggedIn) {
                    var userData = <?php echo isset($currentUser) ? json_encode([
                                        'full_name' => $currentUser->getFullName(),
                                        'email' => $currentUser->getEmail(),
                                        'phone' => $currentUser->getPhone()
                                    ]) : 'null'; ?>;

                    if (userData) {
                        $('#register-full-name').val(userData.full_name);
                        $('#register-email').val(userData.email);
                        $('#register-phone').val(userData.phone);
                        $('#register-email').prop('readonly', true);
                        $('#register-phone').prop('readonly', true);
                        $('#register-modal .modal-header p').text('Add seller access to your existing account');
                    }
                } else {
                    $('#register-full-name').val('');
                    $('#register-email').val('');
                    $('#register-phone').val('');
                    $('#register-email').prop('readonly', false);
                    $('#register-phone').prop('readonly', false);
                    $('#register-modal .modal-header p').text('Create your account to start selling');
                }

                if (typeof openModal === 'function') {
                    openModal($registerModal);
                } else {
                    $registerModal.addClass('active');
                    $registerModal.css('visibility', 'visible');
                    $('body').css('overflow', 'hidden');
                }
            }

            if ($sellerRegisterBtn.length) $sellerRegisterBtn.on('click', openSellerRegisterModal);
            if ($createSellerBtn.length) $createSellerBtn.on('click', openSellerRegisterModal);
            if ($upgradeBtn.length) $upgradeBtn.on('click', openSellerRegisterModal);
            if ($upgradeBtn2.length) $upgradeBtn2.on('click', openSellerRegisterModal);
        });
    </script>

</body>

</html>