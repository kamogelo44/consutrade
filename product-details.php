<?php
/*
 * ConsuTrade - Product Details Page
 * Author: Kamogelo Phale
 * 
 * Displays single product - uses AJAX to load detailed data via products.js
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
include __DIR__ . '/includes/functions.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header('Location: product-listings.php');
    exit;
}

// Get product title for breadcrumb only
$product_name = 'Product Details';
try {
    $productData = $productRepo->getProductForDisplay($product_id);
    if ($productData && isset($productData['title'])) {
        $product_name = $productData['title'];
    }
} catch (Exception $e) {
    $product_name = 'Product Details';
}

// Set breadcrumb
$breadcrumbItems = [
    ['url' => 'product-listings.php', 'label' => 'All Products'],
    ['label' => htmlspecialchars($product_name)]
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product_name); ?> - ConsuTrade</title>
    <meta name="description" content="View product details and purchase from trusted sellers on ConsuTrade">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">

    <style>
        /* ========== PRODUCT DETAILS PAGE SPECIFIC STYLES ONLY ========== */
        /* Note: Stock status styles are in components.css */

        .product-details-main {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: var(--spacing-xl);
        }

        .product-details-container {
            width: 100%;
        }

        /* Product Images Gallery */
        .top-items {
            display: flex;
            justify-content: space-between;
            gap: 40px;
            margin: 30px 0;
        }

        .product-imgs {
            flex: 1;
            max-width: 600px;
            width: 100%;
        }

        .main-img {
            background-color: var(--gray-lighter);
            width: 100%;
            height: auto;
            aspect-ratio: 4/3;
            margin-bottom: var(--spacing-md);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        #main-product-image {
            transition: opacity 0.3s ease;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .smaller-imgs {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: var(--spacing-md);
            width: 100%;
        }

        .small-img {
            background-color: var(--gray-lighter);
            width: 100%;
            aspect-ratio: 1/1;
            border-radius: var(--radius-md);
            overflow: hidden;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .small-img:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .small-img.active {
            border: 2px solid var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 0, 0.2);
        }

        .small-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Product Info Section */
        .product-info {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
            background-color: var(--white);
            padding: 30px;
            flex: 1;
            max-width: 500px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
        }

        .details-prod-name {
            font-size: var(--font-3xl);
            font-weight: var(--font-bold);
            color: var(--dark-bg);
        }

        .details-price {
            font-size: var(--font-4xl);
            font-weight: var(--font-bold);
            color: var(--primary-color);
        }

        .cat-badge {
            display: inline-flex;
            align-items: center;
            background-color: var(--primary-fade);
            border: 1px solid var(--primary-color);
            border-radius: var(--radius-round);
            padding: 4px 12px;
            width: fit-content;
        }

        .cat-badge .cat-name {
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
            color: var(--primary-color);
        }

        .description {
            margin-top: var(--spacing-sm);
        }

        .description .sub-head {
            font-size: var(--font-lg);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-sm);
            color: var(--dark-bg);
        }

        .description .des {
            font-size: var(--font-md);
            line-height: 1.5;
            color: var(--gray-medium);
        }

        .con-loc {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }

        /* Seller Reviews Section */
        .rev-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--spacing-md);
            width: 100%;
        }

        .verified-badge,
        .not-verified-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: var(--radius-round);
            width: auto;
            min-width: 140px;
        }

        .verified-badge {
            background-color: var(--success-light);
            border: 1px solid var(--success);
        }

        .verified-badge p {
            font-size: var(--font-sm);
            font-weight: var(--font-medium);
            color: var(--success);
            margin: 0;
        }

        .not-verified-badge {
            background-color: var(--warning-light);
            border: 1px solid var(--warning);
        }

        .not-verified-badge p {
            font-size: var(--font-sm);
            font-weight: var(--font-medium);
            color: var(--warning);
            margin: 0;
        }

        .seller-profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .profile-pic {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 60px;
            height: 60px;
            background-color: var(--white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }

        .seller-name {
            text-align: center;
            font-size: var(--font-base);
            color: var(--dark-bg);
            font-weight: var(--font-bold);
        }

        .star-reviews {
            text-align: center;
        }

        .star-reviews h1 {
            font-size: var(--font-lg);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-sm);
            color: var(--dark-bg);
        }

        .view-profile {
            color: var(--gray-medium);
            width: 160px;
            height: 36px;
            border-radius: var(--radius-round);
            border: 1px solid var(--border-medium);
            background-color: var(--white);
            font-weight: var(--font-medium);
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .view-profile:hover {
            background-color: var(--gray-bg);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        /* Action Buttons Section */
        .actions {
            display: flex;
            justify-content: center;
            margin: 30px auto;
            padding: 0 var(--spacing-xl);
        }

        .actions-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            max-width: 600px;
            width: 100%;
            min-height: 280px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            gap: var(--spacing-md);
            padding: 30px;
            background-color: var(--white);
        }

        .action-btns {
            display: flex;
            align-items: center;
            flex-direction: column;
            gap: var(--spacing-md);
            width: 100%;
        }

        .action-btns button {
            max-width: 400px;
            width: 100%;
            height: 52px;
            border-radius: var(--radius-md);
            font-weight: var(--font-bold);
            font-size: var(--font-base);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .action-btns .cart-btn {
            color: var(--primary-color);
            background-color: var(--white);
            border: 2px solid var(--primary-color);
        }

        .action-btns .cart-btn:hover:not(:disabled) {
            background-color: var(--primary-fade);
            transform: translateY(-2px);
        }

        .action-btns .buy-btn {
            color: var(--white);
            background-color: var(--primary-color);
            border: none;
        }

        .action-btns .buy-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .action-btns .cart-btn.out-of-stock-btn {
            background-color: var(--gray-light);
            cursor: not-allowed;
            opacity: 0.6;
            color: var(--gray-dark);
            border-color: var(--gray-light);
        }

        .action-btns .cart-btn.out-of-stock-btn:hover {
            transform: none;
        }

        /* Report button */
        .action-btns .report-btn {
            background-color: var(--error-light);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .action-btns .report-btn:hover {
            background-color: var(--error);
            color: white;
        }

        .action-btns .report-btn.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Payment Badge */
        .payfast-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--border-light);
            font-size: var(--font-sm);
            color: var(--gray-medium);
        }

        .payfast-badge img {
            height: 30px;
            width: auto;
        }

        /* Report Modal */
        .report-modal .modal-content {
            max-width: 500px;
        }

        .report-modal textarea {
            width: 100%;
            min-height: 100px;
            padding: var(--spacing-md);
            border: 1px solid var(--border-medium);
            border-radius: var(--radius-md);
            font-family: inherit;
            font-size: var(--font-md);
            resize: vertical;
        }

        .report-modal textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .report-modal select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-medium);
            border-radius: var(--radius-md);
            font-size: var(--font-md);
        }

        .report-modal .modal-actions {
            display: flex;
            gap: var(--spacing-md);
            margin-top: var(--spacing-xl);
            justify-content: flex-end;
        }

        .report-modal .btn-primary {
            background: var(--primary-color);
            color: var(--white);
            padding: 10px 24px;
            border: none;
            border-radius: var(--radius-md);
            font-weight: var(--font-bold);
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .report-modal .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .report-modal .btn-primary:disabled {
            background: var(--gray-light);
            cursor: not-allowed;
            transform: none;
        }


        /* Responsive */
        @media (max-width: 992px) {
            .top-items {
                flex-direction: column;
                align-items: center;
            }

            .product-info {
                max-width: 100%;
            }
        }

        @media (max-width: 768px) {
            .product-details-main {
                padding: var(--spacing-lg);
            }

            .small-img {
                width: 70px;
                height: 70px;
            }

            .actions-card {
                padding: 20px;
            }
        }

        @media (max-width: 576px) {
            .details-prod-name {
                font-size: var(--font-xl);
            }

            .details-price {
                font-size: var(--font-2xl);
            }

            .small-img {
                width: 60px;
                height: 60px;
            }

            .action-btns button {
                height: 44px;
                font-size: var(--font-sm);
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main class="product-details-main">
        <?php include 'includes/breadcrumb.php'; ?>

        <div class="product-details-container" data-product-id="<?php echo $product_id; ?>">
            <div id="product-details-content">
                <div class="loading-spinner">Loading product details...</div>
            </div>
        </div>
    </main>

    <!-- Report Product Modal -->
    <div id="reportModal" class="modal report-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Report Product</h3>
                <button class="btn-close " id="closeReportModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <div id="reportErrorContainer" class="error-container" style="display:none;"></div>
                <form id="reportForm">
                    <div class="input-group">
                        <label for="reportReason">Reason for reporting *</label>
                        <select id="reportReason" name="reason" required>
                            <option value="">Select a reason...</option>
                            <option value="fake_product">Fake Product</option>
                            <option value="wrong_description">Wrong Description</option>
                            <option value="counterfeit">Counterfeit Item</option>
                            <option value="scam">Potential Scam</option>
                            <option value="other">Other Issue</option>
                        </select>
                    </div>
                    <div class="input-group" style="margin-top: 16px;">
                        <label for="reportDescription">Additional Details (Optional)</label>
                        <textarea id="reportDescription" name="description" rows="4" placeholder="Please provide more details about the issue..."></textarea>
                        <small>Maximum 1000 characters</small>
                    </div>
                    <div class="modal-actions" style="display:flex; gap:12px; margin-top:20px; justify-content: flex-end;">
                        <button type="submit" class="btn-primary" id="submitReportBtn">Submit Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php $load_products_js = true; ?>
    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

</body>

</html>