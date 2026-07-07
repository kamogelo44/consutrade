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
    $productData = $productRepo->findForDisplay($product_id);
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
                <button class="btn-close" id="closeReportModalBtn">&times;</button>
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
                    <div class="modal-actions">
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