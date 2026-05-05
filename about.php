<?php
/*
 * ConsuTrade - About Us Page
 * Author: Kamogelo Phale
 * 
 * Information about the platform and company
 */

require_once __DIR__ . '/init.php';

$baseUrl = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    <meta name="description" content="Learn about ConsuTrade - South Africa's online marketplace connecting informal traders with buyers">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/login-signup.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="about-container">
        <div class="about-header">
            <h1>About ConsuTrade</h1>
            <p>Empowering South African Entrepreneurs</p>
        </div>
        
        <div class="about-content">
            <div class="about-section">
                <h2>Our Story</h2>
                <p>ConsuTrade is an online marketplace connecting informal traders with buyers across South Africa. Founded with the vision of empowering local entrepreneurs, we provide a platform for small businesses to reach a wider audience.</p>
            </div>

            <div class="about-section">
                <h2>Our Mission</h2>
                <p>To empower local entrepreneurs by providing a platform to showcase their products to a wider audience, while giving buyers access to unique, locally-made goods at competitive prices.</p>
            </div>

            <div class="about-section">
                <h2>What We Offer</h2>
                <ul class="offer-list">
                    <li>✓ Easy-to-use platform for sellers</li>
                    <li>✓ Secure payments via PayFast</li>
                    <li>✓ Nationwide delivery across South Africa</li>
                    <li>✓ Verified seller system for trust and safety</li>
                    <li>✓ Real-time order tracking</li>
                    <li>✓ Buyer protection policy</li>
                </ul>
            </div>

            <div class="about-section">
                <h2>Why Choose ConsuTrade?</h2>
                <div class="features-grid">
                    <div class="feature">
                        <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="32" height="32" alt="Trust">
                        <h3>Trust & Safety</h3>
                        <p>All sellers are verified to ensure a safe shopping experience.</p>
                    </div>
                    <div class="feature">
                        <img src="<?php echo $baseUrl; ?>images/icons/secure-card-svgrepo-com.svg" width="32" height="32" alt="Secure">
                        <h3>Secure Payments</h3>
                        <p>PayFast integration ensures your transactions are protected.</p>
                    </div>
                    <div class="feature">
                        <img src="<?php echo $baseUrl; ?>images/icons/delivery-svgrepo-com.svg" width="32" height="32" alt="Delivery">
                        <h3>Fast Delivery</h3>
                        <p>Reliable nationwide shipping to your doorstep.</p>
                    </div>
                    <div class="feature">
                        <img src="<?php echo $baseUrl; ?>images/icons/contact-location.svg" width="32" height="32" alt="Support">
                        <h3>24/7 Support</h3>
                        <p>Our support team is always ready to help you.</p>
                    </div>
                </div>
            </div>

            <div class="about-section">
                <h2>Contact Us</h2>
                <div class="contact-info">
                    <div class="contact-item">
                        <img src="<?php echo $baseUrl; ?>images/icons/email-svgrepo-com.svg" width="20" height="20" alt="Email">
                        <p>support@consutrade.co.za</p>
                    </div>
                    <div class="contact-item">
                        <img src="<?php echo $baseUrl; ?>images/icons/phone-call-svgrepo-com.svg" width="20" height="20" alt="Phone">
                        <p>+27 12 345 6789</p>
                    </div>
                    <div class="contact-item">
                        <img src="<?php echo $baseUrl; ?>images/icons/pin-location-svgrepo-com.svg" width="20" height="20" alt="Location">
                        <p>Johannesburg, South Africa</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>