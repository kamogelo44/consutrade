<?php
/*
 * ConsuTrade - Seller Information Page
 * Author: Kamogelo Phale
 * 
 * Information page for potential sellers to learn about selling on ConsuTrade
 */

require_once __DIR__ . '/init.php';

// Read register errors
$registerErrors = $_SESSION['register_errors'] ?? [];
$registerFormData = $_SESSION['register_form_data'] ?? [];
unset($_SESSION['register_errors'], $_SESSION['register_form_data']);

// Read login errors
$loginErrors = $_SESSION['login_errors'] ?? [];
$loginEmail = $_SESSION['login_email'] ?? '';
unset($_SESSION['login_errors'], $_SESSION['login_email']);

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
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/sell.css">
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

    <?php if (!empty($registerErrors)): ?>
        <script>
            $(function() {
                openModal($('#register-modal'));
                displayModalErrors('#register-modal', <?php echo json_encode($registerErrors); ?>, <?php echo json_encode($registerFormData); ?>);
            });
        </script>
    <?php endif; ?>

    <?php if (!empty($loginErrors)): ?>
        <script>
            $(function() {
                openModal($('#login-modal'));
                displayModalErrors('#login-modal', <?php echo json_encode($loginErrors); ?>, {
                    email: <?php echo json_encode($loginEmail); ?>
                });
            });
        </script>
    <?php endif; ?>

    <script>
        // Function to open register modal with seller role pre-selected
        function openSellerRegisterModal() {
            // Select the seller radio button
            $('#seller').prop('checked', true);

            // Open the register modal
            $('#register-modal').addClass('active');
            $('#register-modal').css('visibility', 'visible');
        }

        // Attach click handlers to seller registration buttons
        document.getElementById('sellerRegisterBtn').addEventListener('click', openSellerRegisterModal);
        document.getElementById('createSellerBtn').addEventListener('click', openSellerRegisterModal);
    </script>

</body>

</html>