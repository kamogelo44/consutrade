<?php
/*
 * ConsuTrade - Maintenance Mode
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/init.php';

// Check if allowed - if yes, redirect back
if (isMaintenanceAllowed()) {
    $redirect = $_SERVER['HTTP_REFERER'] ?? '/';
    header('Location: ' . $redirect);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.9.14/dist/dotlottie-wc.js" type="module"></script>
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--dark-bg) 0%, #2d2d2d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-md);
            margin: 0;
            font-family: var(--font-family, 'Roboto', sans-serif);
        }

        .maintenance-container {
            max-width: 550px;
            width: 100%;
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--spacing-2xl);
            text-align: center;
            box-shadow: var(--shadow-xl);
        }

        dotlottie-wc {
            display: block;
            margin: 0 auto var(--spacing-lg) auto;
            width: 180px;
            height: 180px;
        }

        .maintenance-container h1 {
            font-size: var(--font-3xl);
            font-weight: var(--font-bold);
            color: var(--dark-bg);
            margin-bottom: var(--spacing-xs);
        }

        .maintenance-container h1 span {
            color: var(--primary-color);
        }

        .maintenance-container .status-badge {
            display: inline-block;
            background: var(--warning-light);
            color: var(--warning);
            padding: 4px 16px;
            border-radius: var(--radius-round);
            font-size: var(--font-sm);
            font-weight: var(--font-medium);
            margin: var(--spacing-sm) 0 var(--spacing-md) 0;
        }

        .maintenance-container p {
            color: var(--gray-medium);
            font-size: var(--font-md);
            line-height: 1.6;
            margin-bottom: var(--spacing-xs);
        }

        .maintenance-container .eta {
            font-size: var(--font-sm);
            color: var(--gray-light);
            margin-top: var(--spacing-xl);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--border-light);
        }

        /* Debug info - shows your IP and if it's allowed */
        .debug-info {
            margin-top: var(--spacing-lg);
            padding: var(--spacing-md);
            background: #f7fafc;
            border-radius: var(--radius-md);
            font-size: var(--font-sm);
            color: var(--gray-medium);
            text-align: left;
            border: 1px solid var(--border-light);
        }

        .debug-info code {
            background: #e2e8f0;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 13px;
            color: #2d3748;
        }

        .debug-info .allowed {
            color: #38a169;
            font-weight: 600;
        }

        .debug-info .blocked {
            color: #e53e3e;
            font-weight: 600;
        }

        @media (max-width: 480px) {
            .maintenance-container {
                padding: var(--spacing-lg);
            }

            .maintenance-container h1 {
                font-size: var(--font-2xl);
            }

            dotlottie-wc {
                width: 140px;
                height: 140px;
            }
        }
    </style>
</head>

<body>
    <div class="maintenance-container">
        <!-- Lottie Animation -->
        <dotlottie-wc
            src="https://lottie.host/3bbce1b7-8129-431f-9c31-8313443461af/je1OGdbUqY.json"
            autoplay
            loop>
        </dotlottie-wc>

        <h1>Consu<span>Trade</span></h1>
        <span class="status-badge">Under Maintenance</span>

        <p>We're currently updating the system to improve your experience.</p>
        <p style="margin-bottom: var(--spacing-md);">We'll be back shortly.</p>

        <div style="margin: var(--spacing-lg) 0 var(--spacing-sm) 0;">
            <span style="font-size: var(--font-sm); color: var(--gray-medium);">Please check back in a few minutes</span>
        </div>

        <div class="eta">
            <p style="margin: 0; font-size: var(--font-sm); color: var(--gray-light);">
                Thank you for your patience.
            </p>
        </div>
    </div>
</body>

</html>