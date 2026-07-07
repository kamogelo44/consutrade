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
    <title>About ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    <meta name="description" content="Learn about ConsuTrade - South Africa's C2C marketplace connecting informal traders with buyers">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>
    <?php include 'includes/breadcrumb.php'; ?>

    <main class="about-container">
        <div class="about-header">
            <h1>About ConsuTrade</h1>
            <p>A marketplace built for South Africa's informal traders</p>
        </div>

        <div class="about-content">
            <div class="about-section">
                <h2>What This Is</h2>
                <p>ConsuTrade is a peer-to-peer marketplace connecting informal traders — from township vendors to home-based entrepreneurs — with buyers across South Africa.</p>
                <p>Most informal traders sell through WhatsApp groups and Facebook Marketplace. Those platforms weren't built for trading. There's no payment protection, no seller verification, and no way to prove you're legitimate. ConsuTrade fixes that.</p>
            </div>

            <div class="about-section">
                <h2>Why It Exists</h2>
                <p>South Africa's informal economy is worth nearly <strong>R900 billion</strong> and employs almost <strong>20% of the working population</strong>. Yet platforms like Takealot and BobShop require business registration documents that most informal traders don't have.</p>
                <p>ConsuTrade was built specifically for these traders — no business registration needed, just a South African ID.</p>
            </div>

            <div class="about-section">
                <h2>How It's Different</h2>
                <ul class="about-difference-list">
                    <li>
                        <strong>Verified sellers, not anonymous profiles.</strong> Every seller verifies with their SA ID. Buyers see a badge on every listing.
                    </li>
                    <li>
                        <strong>PayFast on every transaction.</strong> No cash deposits. No e-wallet scams. Payments are protected.
                    </li>
                    <li>
                        <strong>Built for low data usage.</strong> South Africa has some of the most expensive mobile data in Africa. Every page is designed to load fast and use minimal data.
                    </li>
                    <li>
                        <strong>Order tracking from start to finish.</strong> No more scrolling through WhatsApp chats to find out what someone ordered.
                    </li>
                </ul>
            </div>

            <div class="about-section">
                <h2>Contact</h2>
                <div class="about-contact">
                    <a href="mailto:support@consutrade.co.za" class="about-contact-item">
                        <img src="<?php echo $baseUrl; ?>images/icons/email-svgrepo-com.svg" width="20" height="20" alt="Email">
                        support@consutrade.co.za
                    </a>
                    <div class="about-contact-item">
                        <img src="<?php echo $baseUrl; ?>images/icons/pin-location-svgrepo-com.svg" width="20" height="20" alt="Location">
                        Johannesburg, South Africa
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

</body>

</html>