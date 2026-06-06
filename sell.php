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
if ($isLoggedIn && $currentUser instanceof Seller) {
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
                <div class="seller-hero-buttons">
                    <button class="register-now-btn" id="sellerRegisterBtn">Create Seller Account</button>
                    <a href="<?php echo $baseUrl; ?>admin/login.php" class="login-now-btn">Login to Seller Account</a>
                </div>
            </div>
        </section>

        <!-- Why Sell Section -->
        <section class="why-sell">
            <h1 class="section-heading">Why Sell with Us</h1>
            <div class="why-sell-container">
                <div class="why-sell-card">
                    <img src="<?php echo $baseUrl; ?>images/icons/users-svgrepo-com.svg" width="48" height="48" alt="Reach customers" class="icon">
                    <h3>Reach More Customers</h3>
                    <p>Connect with thousands of buyers across South Africa</p>
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
                        <p>Active Email Address</p>
                    </div>
                    <div class="requirement-item">
                        <img src="<?php echo $baseUrl; ?>images/icons/phone-call-svgrepo-com.svg" class="requirement-icon" alt="Phone">
                        <p>Valid Phone Number</p>
                    </div>
                </div>
                <p class="requirement-note">All sellers must be verified before they can start selling on ConsuTrade.</p>
            </div>
        </section>

        <!-- Ready to Start Section -->
        <section class="ready-to-start">
            <div class="ready-container">
                <h2 class="ready-title">Ready to Grow Your Business?</h2>
                <p class="ready-subtitle">Create your seller account today and start reaching more customers.</p>
                <button class="create-seller-btn" id="createSellerBtn">Create Seller Account</button>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

    <script>
        // ========== CACHED DOM ELEMENTS ==========
        var $sellerRegisterBtn = null;
        var $createSellerBtn = null;
        var $registerModal = null;
        var $sellerRadio = null;

        // ========== CACHE FUNCTION ==========
        function cacheSellPageElements() {
            $sellerRegisterBtn = $('#sellerRegisterBtn');
            $createSellerBtn = $('#createSellerBtn');
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

            // Open the register modal using the shared function from main.js
            if (typeof openModal === 'function') {
                openModal($registerModal);
            } else {
                // Fallback if openModal is not available
                $registerModal.addClass('active');
                $registerModal.css('visibility', 'visible');
                $('body').css('overflow', 'hidden');
            }
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
        });
    </script>

</body>

</html>