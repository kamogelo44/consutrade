<?php

/**
 * ConsuTrade - Test Endpoint for Category Creation
 */

global $categoryRepo;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    // If running under PHPUnit testing, return instead of killing the process
    if (defined('PHPUNIT_TESTING')) return;
    else exit;
}

$categoryName = isset($_POST['category_name']) ? trim($_POST['category_name']) : '';

if (empty($categoryName)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Category name is required.']);
    if (defined('PHPUNIT_TESTING')) return;
    else exit;
}

$insertedId = $categoryRepo->create($categoryName);

if ($insertedId) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Category created successfully.',
        'category_id' => $insertedId
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save category to database.']);
}

// Safely stop or hand execution control back
if (defined('PHPUNIT_TESTING')) return;
else exit;
