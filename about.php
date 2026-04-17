<?php
/*
 * ConsuTrade - About Us Page
 * Author: Kamogelo Phale
 */

session_start();
$baseUrl = "/www/consutrade/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - ConsuTrade</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/login-signup.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main style="max-width: 800px; margin: 40px auto; padding: 0 20px;">
        <h1 style="font-size: 32px; font-weight: bold; margin-bottom: 20px; color: #1A1A1A;">About ConsuTrade</h1>
        
        <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <p style="margin-bottom: 20px; line-height: 1.6; color: #333;">
                ConsuTrade is an online marketplace connecting informal traders with buyers across South Africa.
            </p>
            
            <p style="margin-bottom: 20px; line-height: 1.6; color: #333;">
                Our mission is to empower local entrepreneurs by providing a platform to showcase their products 
                to a wider audience, while giving buyers access to unique, locally-made goods.
            </p>
            
            <h2 style="font-size: 20px; font-weight: bold; margin: 20px 0 10px; color: #FF6B00;">What We Offer</h2>
            <ul style="margin-bottom: 20px; padding-left: 20px;">
                <li style="margin-bottom: 8px;">✓ Easy-to-use platform for sellers</li>
                <li style="margin-bottom: 8px;">✓ Secure payments via PayFast</li>
                <li style="margin-bottom: 8px;">✓ Nationwide delivery across SA</li>
                <li style="margin-bottom: 8px;">✓ Verified seller system for trust</li>
            </ul>
            
            <h2 style="font-size: 20px; font-weight: bold; margin: 20px 0 10px; color: #FF6B00;">Contact Us</h2>
            <p style="margin-bottom: 8px;"> Email: support@consutrade.co.za</p>
            <p> South Africa</p>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>