<?php
/*
 * ConsuTrade - About Us Page
 * Author: Kamogelo Phale
 * 
 * Information about the platform and company
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    <meta name="description" content="Learn about ConsuTrade - South Africa's online marketplace connecting informal traders with buyers">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <style>
        /* About Page Styles - Page specific only */
        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--spacing-xl);
        }

        .about-header {
            text-align: center;
            margin-bottom: var(--spacing-2xl);
        }

        .about-header h1 {
            font-size: var(--font-3xl);
            font-weight: var(--font-bold);
            color: var(--dark-bg);
            margin-bottom: var(--spacing-sm);
        }

        .about-header p {
            font-size: var(--font-lg);
            color: var(--gray-medium);
        }

        .about-section {
            margin-bottom: var(--spacing-2xl);
        }

        .about-section h2 {
            font-size: var(--font-2xl);
            font-weight: var(--font-semibold);
            color: var(--dark-bg);
            margin-bottom: var(--spacing-md);
            padding-bottom: var(--spacing-sm);
            border-bottom: 2px solid var(--primary-color);
            display: inline-block;
        }

        .about-section p {
            font-size: var(--font-md);
            color: var(--gray-medium);
            line-height: 1.6;
            margin-top: var(--spacing-md);
        }

        .offer-list {
            list-style: none;
            padding: 0;
            margin-top: var(--spacing-md);
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--spacing-md);
        }

        .offer-list li {
            font-size: var(--font-md);
            color: var(--gray-dark);
            padding: var(--spacing-sm) 0;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: var(--spacing-lg);
            margin-top: var(--spacing-lg);
        }

        .feature {
            text-align: center;
            padding: var(--spacing-lg);
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            transition: all var(--transition-normal);
        }

        .feature:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
        }

        .feature img {
            margin-bottom: var(--spacing-md);
            transition: transform var(--transition-normal);
            filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(1577%) hue-rotate(350deg) brightness(102%) contrast(101%);
        }

        .feature:hover img {
            transform: scale(1.05);
        }

        .feature h3 {
            font-size: var(--font-lg);
            font-weight: var(--font-semibold);
            color: var(--dark-bg);
            margin-bottom: var(--spacing-sm);
            transition: color var(--transition-fast);
        }

        .feature:hover h3 {
            color: var(--primary-color);
        }

        .feature p {
            font-size: var(--font-sm);
            color: var(--gray-medium);
            line-height: 1.4;
        }

        .contact-info {
            display: flex;
            justify-content: center;
            gap: var(--spacing-2xl);
            flex-wrap: wrap;
            margin-top: var(--spacing-lg);
            padding: var(--spacing-xl);
            background: var(--gray-bg-light);
            border-radius: var(--radius-lg);
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            transition: all var(--transition-fast);
        }

        .contact-item:hover {
            transform: translateX(4px);
        }

        .contact-item img {
            transition: transform var(--transition-fast);
            filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(1577%) hue-rotate(350deg) brightness(102%) contrast(101%);
        }

        .contact-item:hover img {
            transform: scale(1.1);
        }

        .contact-item p {
            font-size: var(--font-md);
            color: var(--gray-dark);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 992px) {
            .offer-list {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .about-container {
                padding: var(--spacing-md);
            }

            .about-header h1 {
                font-size: var(--font-2xl);
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .contact-info {
                flex-direction: column;
                align-items: center;
                gap: var(--spacing-md);
            }
        }

        @media (max-width: 480px) {
            .about-header h1 {
                font-size: var(--font-xl);
            }

            .about-section h2 {
                font-size: var(--font-lg);
            }
        }
    </style>
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
                    <li>Easy-to-use platform for sellers</li>
                    <li>Secure payments via PayFast</li>
                    <li>Nationwide delivery across South Africa</li>
                    <li>Verified seller system for trust and safety</li>
                    <li>Real-time order tracking</li>
                    <li>Buyer protection policy</li>
                </ul>
            </div>

            <div class="about-section">
                <h2>Why Choose ConsuTrade?</h2>
                <div class="features-grid">
                    <div class="feature">
                        <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="32" height="32" alt="Trust">
                        <h3>Trust and Safety</h3>
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
    <?php include 'includes/modal-errors.php'; ?>

</body>

</html>