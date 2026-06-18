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
    <title>About Us - ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    <meta name="description" content="Learn about ConsuTrade - South Africa's C2C marketplace connecting informal traders with buyers">
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--spacing-lg);
            margin-top: var(--spacing-md);
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            text-align: center;
            border: 1px solid var(--border-light);
        }

        .stat-card .number {
            font-size: var(--font-3xl);
            font-weight: var(--font-bold);
            color: var(--primary-color);
        }

        .stat-card .label {
            font-size: var(--font-sm);
            color: var(--gray-medium);
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

        .offer-list li::before {
            content: "✓";
            color: var(--primary-color);
            font-weight: var(--font-bold);
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

            .stats-grid {
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
    <?php include 'includes/breadcrumb.php'; ?>

    <main class="about-container">
        <div class="about-header">
            <h1>About ConsuTrade</h1>
            <p>South Africa's C2C Marketplace for Informal Traders</p>
        </div>

        <div class="about-content">
            <!-- Our Story -->
            <div class="about-section">
                <h2>Our Story</h2>
                <p>ConsuTrade is a peer-to-peer (C2C) marketplace designed specifically for South Africa's informal traders. The platform connects informal economy participants — from township street sellers to home-based entrepreneurs — with buyers across the country.</p>
                <p>According to Statistics South Africa, the informal economy is valued at nearly R900 billion annually and employs almost 20% of the working population. Yet most informal traders still sell through WhatsApp and Facebook because no proper e-commerce platform was built for them. ConsuTrade changes that.</p>
            </div>

            <!-- Why We Built It -->
            <div class="about-section">
                <h2>Why We Built It</h2>
                <p>Existing platforms like Takealot and BobShop were designed for registered businesses with warehouses. They don't serve informal traders who operate without formal registration documents.</p>
                <p>Our research identified five core problems facing township traders:</p>
                <ul class="offer-list">
                    <li>No trust in online payments with unverified sellers</li>
                    <li>No affordable delivery logistics outside major cities</li>
                    <li>Digital fraud with no seller verification system</li>
                    <li>High mobile data costs — South Africa has some of the most expensive data in Africa</li>
                    <li>Existing platforms built for formal businesses, not informal traders</li>
                </ul>
                <p>ConsuTrade addresses all of these problems in one platform.</p>
            </div>

            <!-- The Impact -->
            <div class="about-section">
                <h2>The Impact</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <p class="number">R900bn</p>
                        <p class="label">South Africa's informal economy</p>
                    </div>
                    <div class="stat-card">
                        <p class="number">20%</p>
                        <p class="label">Of national employment</p>
                    </div>
                </div>
                <p style="margin-top: var(--spacing-md);">ConsuTrade helps informal traders move from survival-level trading to sustainable business growth by providing secure, affordable, and accessible digital infrastructure.</p>
            </div>

            <!-- What We Offer -->
            <div class="about-section">
                <h2>What We Offer</h2>
                <ul class="offer-list">
                    <li>C2C marketplace for peer-to-peer trading</li>
                    <li>Low-data design for affordable access</li>
                    <li>Seller verification and trusted badges</li>
                    <li>Secure payments via PayFast</li>
                    <li>Nationwide delivery across South Africa</li>
                    <li>Real-time order tracking</li>
                    <li>Product reporting system for fraud prevention</li>
                    <li>Seller reviews to build trust</li>
                </ul>
            </div>

            <!-- Why Choose ConsuTrade -->
            <div class="about-section">
                <h2>Why Choose ConsuTrade?</h2>
                <div class="features-grid">
                    <div class="feature">
                        <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="32" height="32" alt="Verification">
                        <h3>Verified Sellers</h3>
                        <p>All sellers verify their identity. Buyers see a "Verified Seller" badge on every listing.</p>
                    </div>
                    <div class="feature">
                        <img src="<?php echo $baseUrl; ?>images/icons/secure-card-svgrepo-com.svg" width="32" height="32" alt="Secure">
                        <h3>Secure Payments</h3>
                        <p>PayFast integration ensures your transactions are protected by a trusted South African payment gateway.</p>
                    </div>
                    <div class="feature">
                        <img src="<?php echo $baseUrl; ?>images/icons/delivery-svgrepo-com.svg" width="32" height="32" alt="Delivery">
                        <h3>Nationwide Delivery</h3>
                        <p>Reliable delivery across South Africa. Free delivery for orders over R500.</p>
                    </div>
                    <div class="feature">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="32" height="32" alt="C2C">
                        <h3>Peer-to-Peer Trading</h3>
                        <p>A C2C marketplace designed for informal traders to sell directly to buyers.</p>
                    </div>
                </div>
            </div>

            <!-- Contact -->
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