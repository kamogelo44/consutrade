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
    <meta name="description" content="Start selling your products on ConsuTrade. Reach thousands of customers across South Africa.">
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <style>
        /* ========== SELLER PAGE STYLES ========== */

        /* Make icons orange to match brand */
        .why-sell-card .icon,
        .requirement-icon {
            filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(1577%) hue-rotate(350deg) brightness(102%) contrast(101%);
        }

        /* Hero Section */
        .seller-hero {
            background: linear-gradient(135deg, var(--primary-color) 0%, #2a52be 100%);
            padding: 80px var(--spacing-xl);
            text-align: center;
            color: var(--white);
        }

        .seller-hero-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .seller-hero-title {
            font-size: 48px;
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-md);
        }

        .seller-hero-subtitle {
            font-size: var(--font-lg);
            margin-bottom: var(--spacing-xl);
            opacity: 0.95;
        }

        .seller-hero-buttons {
            display: flex;
            gap: var(--spacing-md);
            justify-content: center;
            flex-wrap: wrap;
        }

        .register-now-btn,
        .login-now-btn {
            text-decoration: none;
            padding: 12px 32px;
            font-size: var(--font-base);
            font-weight: var(--font-bold);
            border-radius: var(--radius-xl);
            cursor: pointer;
            transition: all var(--transition-normal);
        }

        .register-now-btn {
            background-color: var(--white);
            color: var(--primary-color);
            border: none;
        }

        .register-now-btn:hover {
            background-color: var(--white);
            color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 0, 0.3);
        }

        .login-now-btn {
            background-color: transparent;
            color: var(--white);
            border: 2px solid var(--white);
        }

        .login-now-btn:hover {
            background-color: var(--white);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        /* Why Sell Section */
        .why-sell {
            padding: 60px var(--spacing-xl);
            background-color: var(--white);
        }

        .section-heading {
            font-size: 2.5rem;
            margin-bottom: 50px;
            color: var(--gray-dark);
            text-align: center;
            font-weight: var(--font-bold);
        }

        .why-sell-container {
            display: flex;
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            justify-content: center;
            flex-wrap: wrap;
        }

        .why-sell-card {
            flex: 1;
            min-width: 250px;
            max-width: 350px;
            background-color: var(--white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            text-align: center;
            transition: all var(--transition-normal);
            padding: var(--spacing-lg);
        }

        .why-sell-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .why-sell-card .icon {
            margin-bottom: var(--spacing-md);
            transition: transform var(--transition-normal);
        }

        .why-sell-card:hover .icon {
            transform: scale(1.05);
        }

        .why-sell-card h3 {
            font-size: 1.5rem;
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-sm);
            color: var(--dark-bg);
            transition: color var(--transition-fast);
        }

        .why-sell-card:hover h3 {
            color: var(--primary-color);
        }

        .why-sell-card p {
            font-size: var(--font-md);
            color: var(--gray-medium);
            line-height: 1.5;
        }

        /* Requirements Section */
        .requirements {
            background-color: var(--gray-bg);
            padding: 60px var(--spacing-xl);
            border-top: 1px solid var(--border-light);
            border-bottom: 1px solid var(--border-light);
        }

        .requirements-container {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }

        .requirements-container h2 {
            font-size: var(--font-2xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-sm);
            color: var(--dark-bg);
        }

        .requirements-container>p {
            color: var(--gray-medium);
            margin-bottom: var(--spacing-xl);
        }

        .requirements-list {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-lg);
            justify-content: center;
            margin-top: var(--spacing-xl);
        }

        .requirement-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            background-color: var(--white);
            padding: var(--spacing-md) var(--spacing-xl);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            min-width: 250px;
            transition: all var(--transition-fast);
        }

        .requirement-item:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-md);
        }

        .requirement-icon {
            width: 32px;
            height: 32px;
            transition: transform var(--transition-fast);
        }

        .requirement-item:hover .requirement-icon {
            transform: scale(1.1);
        }

        .requirement-item p {
            font-size: var(--font-base);
            color: var(--dark-bg);
            font-weight: var(--font-medium);
        }

        .requirement-note {
            font-size: var(--font-sm);
            color: var(--gray-medium);
            margin-top: var(--spacing-xl);
            font-style: italic;
        }

        /* Ready to Start Section */
        .ready-to-start {
            padding: 60px var(--spacing-xl);
            background: linear-gradient(135deg, var(--dark-bg) 0%, #2a2a2a 100%);
            text-align: center;
        }

        .ready-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .ready-title {
            font-size: 32px;
            font-weight: var(--font-bold);
            color: var(--white);
            margin-bottom: var(--spacing-md);
        }

        .ready-subtitle {
            font-size: var(--font-lg);
            color: var(--gray-light);
            margin-bottom: var(--spacing-xl);
        }

        .create-seller-btn {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 14px 32px;
            font-size: var(--font-base);
            font-weight: var(--font-bold);
            border-radius: var(--radius-xl);
            cursor: pointer;
            transition: all var(--transition-normal);
        }

        .create-seller-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 0, 0.3);
        }

        /* Upgrade Banner for Logged-in Buyers */
        .upgrade-banner {
            background-color: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
            text-align: left;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .upgrade-banner h3 {
            color: var(--white);
            margin-bottom: var(--spacing-sm);
        }

        .upgrade-banner p {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: var(--spacing-md);
        }

        .upgrade-banner .upgrade-btn {
            background-color: var(--white);
            color: var(--primary-color);
            border: none;
            padding: 10px 24px;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: var(--font-bold);
            transition: all var(--transition-normal);
        }

        .upgrade-banner .upgrade-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .seller-hero {
                padding: 60px var(--spacing-md);
            }

            .seller-hero-title {
                font-size: 32px;
            }

            .seller-hero-subtitle {
                font-size: var(--font-base);
            }

            .seller-hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .register-now-btn,
            .login-now-btn {
                width: 220px;
            }

            .why-sell {
                padding: 40px var(--spacing-md);
            }

            .section-heading {
                font-size: 2rem;
                margin-bottom: 30px;
            }

            .why-sell-card {
                min-width: 100%;
                max-width: 100%;
            }

            .requirements {
                padding: 40px var(--spacing-md);
            }

            .requirement-item {
                width: 100%;
            }

            .ready-title {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .seller-hero-title {
                font-size: 24px;
            }

            .why-sell-card h3 {
                font-size: 1.2rem;
            }

            .ready-title {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="seller-hero">
            <div class="seller-hero-container">
                <h1 class="seller-hero-title">Start Selling Today</h1>
                <p class="seller-hero-subtitle">Join thousands of South African entrepreneurs selling on ConsuTrade</p>

                <?php if ($isLoggedIn && isset($currentUser) && $currentUser->hasRole('buyer') && !$currentUser->hasRole('seller')): ?>
                    <!-- Logged in as BUYER ONLY - Show upgrade option -->
                    <div class="upgrade-banner">
                        <h3>👋 Welcome back, <?php echo htmlspecialchars($currentUser->getDisplayName()); ?>!</h3>
                        <p>You're currently a buyer. Would you like to start selling too?</p>
                        <button class="upgrade-btn" id="upgradeToSellerBtn">Add Seller Access</button>
                    </div>
                    <div class="seller-hero-buttons">
                        <button class="register-now-btn" id="sellerRegisterBtn">Add Seller Access</button>
                        <button class="login-now-btn" onclick="openModal($('#login-modal'))">Login</button>
                    </div>
                <?php elseif ($isLoggedIn && isset($currentUser) && $currentUser->hasRole('seller')): ?>
                    <!-- Logged in as SELLER - Shouldn't be here (redirected above), but just in case -->
                    <div class="seller-hero-buttons">
                        <a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php" class="register-now-btn">Go to Dashboard</a>
                    </div>
                <?php else: ?>
                    <!-- Not logged in - Show registration options -->
                    <div class="seller-hero-buttons">
                        <button class="register-now-btn" id="sellerRegisterBtn">Create Seller Account</button>
                        <button class="login-now-btn" onclick="openModal($('#login-modal'))">Login</button>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Why Sell Section -->
        <section class="why-sell">
            <h1 class="section-heading">Why Sell with Us</h1>
            <div class="why-sell-container">
                <div class="why-sell-card">
                    <img src="<?php echo $baseUrl; ?>images/icons/users-svgrepo-com.svg" width="48" height="48" alt="Reach customers" class="icon">
                    <h3>Seller Tools</h3>
                    <p>Dashboard to manage products, orders, and track sales</p>
                </div>
                <div class="why-sell-card">
                    <img src="<?php echo $baseUrl; ?>images/icons/secure-card-svgrepo-com.svg" width="48" height="48" alt="Secure payments" class="icon">
                    <h3>Secure Payments</h3>
                    <p>Get paid securely through PayFast</p>
                </div>
                <div class="why-sell-card">
                    <img src="<?php echo $baseUrl; ?>images/icons/delivery-svgrepo-com.svg" width="48" height="48" alt="Easy shipping" class="icon">
                    <h3>Easy Shipping</h3>
                    <p>Simple shipping with nationwide delivery</p>
                </div>
            </div>
        </section>

        <!-- Requirements Section -->
        <section class="requirements">
            <div class="requirements-container">
                <h2>What You Need to Start Selling</h2>
                <p>Getting started is easy. Just make sure you have:</p>
                <div class="requirements-list">
                    <div class="requirement-item">
                        <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" class="requirement-icon" alt="Valid ID">
                        <p>Valid South African ID</p>
                    </div>
                    <div class="requirement-item">
                        <img src="<?php echo $baseUrl; ?>images/icons/email-svgrepo-com.svg" class="requirement-icon" alt="Email">
                        <p>Email Address</p>
                    </div>
                    <div class="requirement-item">
                        <img src="<?php echo $baseUrl; ?>images/icons/phone-call-svgrepo-com.svg" class="requirement-icon" alt="Phone">
                        <p>Phone Number</p>
                    </div>
                </div>
                <p class="requirement-note">Seller verification helps build trust with buyers. You can upload products while your verification is pending.</p>
            </div>
        </section>

        <!-- Ready to Start Section -->
        <section class="ready-to-start">
            <div class="ready-container">
                <h2 class="ready-title">Ready to Grow Your Business?</h2>
                <p class="ready-subtitle">Create your seller account today and start reaching more customers.</p>
                <?php if ($isLoggedIn && isset($currentUser) && $currentUser->hasRole('buyer') && !$currentUser->hasRole('seller')): ?>
                    <button class="create-seller-btn" id="upgradeToSellerBtn2">Add Seller Access</button>
                <?php else: ?>
                    <button class="create-seller-btn" id="createSellerBtn">Create Seller Account</button>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

    <script>
        // ========== CACHED DOM ELEMENTS ==========
        var $sellerRegisterBtn = null;
        var $createSellerBtn = null;
        var $upgradeBtn = null;
        var $upgradeBtn2 = null;
        var $registerModal = null;
        var $sellerRadio = null;

        // ========== CACHE FUNCTION ==========
        function cacheSellPageElements() {
            $sellerRegisterBtn = $('#sellerRegisterBtn');
            $createSellerBtn = $('#createSellerBtn');
            $upgradeBtn = $('#upgradeToSellerBtn');
            $upgradeBtn2 = $('#upgradeToSellerBtn2');
            $registerModal = $('#register-modal');
            $sellerRadio = $('#seller');
        }

        // ========== OPEN SELLER REGISTER MODAL ==========
        function openSellerRegisterModal() {
            cacheSellPageElements();

            // Select the seller radio button
            if ($sellerRadio.length) {
                $sellerRadio.prop('checked', true);
            }

            // Check if user is logged in
            var isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;
            var hasSellerRole = <?php echo isset($currentUser) ? json_encode($currentUser->hasRole('seller')) : 'false'; ?>;

            if (isLoggedIn && hasSellerRole) {
                // Already a seller - redirect to dashboard
                window.location.href = baseUrl + 'admin/seller-dashboard.php';
                return;
            }

            if (isLoggedIn) {
                // Logged in buyer - upgrade flow
                var userData = <?php echo isset($currentUser) ? json_encode([
                                    'full_name' => $currentUser->getFullName(),
                                    'email' => $currentUser->getEmail(),
                                    'phone' => $currentUser->getPhone()
                                ]) : 'null'; ?>;

                if (userData) {
                    $('#register-full-name').val(userData.full_name);
                    $('#register-email').val(userData.email);
                    $('#register-phone').val(userData.phone);
                    // Make email and phone readonly since they already exist
                    $('#register-email').prop('readonly', true);
                    $('#register-phone').prop('readonly', true);
                    // Update modal title
                    $('#register-modal .modal-header p').text('Add seller access to your existing account');
                }
            } else {
                // New user - clear any previous values
                $('#register-full-name').val('');
                $('#register-email').val('');
                $('#register-phone').val('');
                $('#register-email').prop('readonly', false);
                $('#register-phone').prop('readonly', false);
                $('#register-modal .modal-header p').text('Create your account to start selling');
            }

            // Open the register modal
            if (typeof openModal === 'function') {
                openModal($registerModal);
            } else {
                $registerModal.addClass('active');
                $registerModal.css('visibility', 'visible');
                $('body').css('overflow', 'hidden');
            }
        }

        // ========== HANDLE UPGRADE BUTTONS ==========
        function handleUpgrade() {
            var isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;

            if (!isLoggedIn) {
                // Not logged in - open registration modal
                openSellerRegisterModal();
                return;
            }

            // Logged in - show modal with pre-filled data
            openSellerRegisterModal();
        }

        // ========== INITIALIZE ==========
        $(document).ready(function() {
            cacheSellPageElements();

            // Attach click handlers to seller registration buttons
            if ($sellerRegisterBtn.length) {
                $sellerRegisterBtn.on('click', openSellerRegisterModal);
            }

            if ($createSellerBtn.length) {
                $createSellerBtn.on('click', openSellerRegisterModal);
            }

            if ($upgradeBtn.length) {
                $upgradeBtn.on('click', handleUpgrade);
            }

            if ($upgradeBtn2.length) {
                $upgradeBtn2.on('click', handleUpgrade);
            }
        });
    </script>

</body>

</html>