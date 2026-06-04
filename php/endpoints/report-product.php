<?php
/*
 * ConsuTrade - Report Product (AJAX)
 * Author: Kamogelo Phale
 * 
 * Allows logged-in buyers to report problematic products
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in and is a buyer
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

// Get and validate input
$input = json_decode(file_get_contents('php://input'), true);
$productId = (int) ($input['product_id'] ?? 0);
$reason = trim($input['reason'] ?? '');
$description = trim($input['description'] ?? '');

// Validate product ID
if ($productId <= 0) {
    $response['message'] = 'Invalid product.';
    echo json_encode($response);
    exit;
}

// Validate reason
$validReasons = ['fake_product', 'wrong_description', 'counterfeit', 'scam', 'other'];
if (!in_array($reason, $validReasons)) {
    $response['message'] = 'Please select a valid reason for reporting.';
    echo json_encode($response);
    exit;
}

// Limit description length
if (strlen($description) > 1000) {
    $description = substr($description, 0, 1000);
}

// Get product to check if it exists
$product = $productRepo->getProductObject($productId);
if (!$product) {
    $response['message'] = 'Product not found.';
    echo json_encode($response);
    exit;
}

// Prevent seller from reporting their own product
if ($product->getSellerId() === $currentUser->getUserId()) {
    $response['message'] = 'You cannot report your own product.';
    echo json_encode($response);
    exit;
}

// Check if user has already reported this product (prevent spam)
if ($reportRepo->hasUserReportedProduct($currentUser->getUserId(), $productId)) {
    $response['message'] = 'You have already reported this product. Our team will review it.';
    echo json_encode($response);
    exit;
}

// Create the report using the domain object
$reportId = $reportRepo->createReportFromData($productId, $currentUser->getUserId(), $reason, $description);

if ($reportId) {
    $response['success'] = true;
    $response['message'] = 'Thank you for your report. Our team will review it shortly.';
} else {
    $response['message'] = 'Failed to submit report. Please try again later.';
}

echo json_encode($response);
