<?php
/*
 * ConsuTrade - Report Product (AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows buyers to report a product listing (and by extension, its seller).
 * The report is attached to the product as evidence of seller misconduct.
 * 
 * This is NOT for reviews/ratings. This is for flagging:
 * - Fake/counterfeit products
 * - Scams or fraudulent listings
 * - Misleading descriptions
 * - Other policy violations
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$isLoggedIn) {
    $response['message'] = 'You must be logged in to report a product.';
    echo json_encode($response);
    exit;
}

if (!$currentUser instanceof Buyer) {
    $response['message'] = 'Only buyers can report products.';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$productId = (int) ($input['product_id'] ?? 0);
$reason = trim($input['reason'] ?? '');
$description = trim($input['description'] ?? '');

if ($productId <= 0) {
    $response['message'] = 'Invalid product.';
    echo json_encode($response);
    exit;
}

$validReasons = ['fake_product', 'wrong_description', 'counterfeit', 'scam', 'other'];
if (!in_array($reason, $validReasons)) {
    $response['message'] = 'Please select a valid reason.';
    echo json_encode($response);
    exit;
}

if (strlen($description) > 1000) {
    $description = substr($description, 0, 1000);
}

// Use ProductService for product lookup
$product = $productService->findById($productId);
if (!$product) {
    $response['message'] = 'Product not found.';
    echo json_encode($response);
    exit;
}

// Prevent reporting your own product (and yourself as a seller)
if ($product->getSellerId() === $currentUser->getUserId()) {
    $response['message'] = 'You cannot report your own product.';
    echo json_encode($response);
    exit;
}

// Prevent duplicate reports
if ($reportRepo->hasUserReportedProduct($currentUser->getUserId(), $productId)) {
    $response['message'] = 'You have already reported this product. Our team will review it.';
    echo json_encode($response);
    exit;
}

// The report is attached to the product, but effectively reports the seller
$reportId = $reportRepo->createReportFromData($productId, $currentUser->getUserId(), $reason, $description);

if ($reportId) {
    $response['success'] = true;
    $response['message'] = 'Thank you for your report. Our team will review the product and seller.';
} else {
    $response['message'] = 'Failed to submit report. Please try again later.';
}

echo json_encode($response);
