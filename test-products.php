<?php
require_once __DIR__ . '/init.php';

echo "<pre>";

// Test 1: Raw product query
echo "=== Test 1: Raw product query ===\n";
$sql = "SELECT p.product_id, p.title, p.status FROM products p WHERE p.status = 'active'";
$result = $conn->query($sql);
echo "Products found: " . $result->num_rows . "\n";
while ($row = $result->fetch_assoc()) {
    echo "  - ID: {$row['product_id']}, Title: {$row['title']}, Status: {$row['status']}\n";
}

// Test 2: get-product.php endpoint (simulate what the endpoint does)
echo "\n=== Test 2: Simulate endpoint query ===\n";
$product_id = 2;
$sql2 = "SELECT p.product_id, p.title, p.description, p.price, p.image_url, 
        p.location, p.condition, p.category_id, p.stock_quantity,
        u.user_id as seller_id, u.full_name as seller_name, 
        u.profile_image, u.id_verified as is_verified
        FROM products p
        JOIN users u ON p.seller_id = u.user_id
        WHERE p.product_id = ? AND p.status = 'active'";
$stmt = $conn->prepare($sql2);
$stmt->bind_param('i', $product_id);
$stmt->execute();
$result2 = $stmt->get_result();

if ($row = $result2->fetch_assoc()) {
    echo "Product found:\n";
    echo "  - ID: {$row['product_id']}\n";
    echo "  - Title: {$row['title']}\n";
    echo "  - Price: {$row['price']}\n";
    echo "  - Seller: {$row['seller_name']}\n";
} else {
    echo "No product found for ID: $product_id\n";
}
$stmt->close();

// Test 3: Gallery
echo "\n=== Test 3: Gallery ===\n";
$gallery = $productRepo->getProductGallery($product_id);
echo "Gallery images: " . count($gallery) . "\n";
foreach ($gallery as $img) {
    echo "  - {$img['image_url']} (primary: {$img['is_primary']})\n";
}

// Test 4: Reviews
echo "\n=== Test 4: Reviews ===\n";
$rating = $reviewRepo->getSellerRating(1);
echo "Seller 1 rating: {$rating['avg_rating']} ({$rating['review_count']} reviews)\n";

// Test 5: Check JS path
echo "\n=== Test 5: JS Path Check ===\n";
echo "The JS in products.js should call:\n";
echo "  baseUrl + 'php/endpoints/get-product.php?id=' + id\n";
echo "Current baseUrl = " . getBaseUrl() . "\n";
echo "Full URL would be: " . getBaseUrl() . "php/endpoints/get-product.php?id=2\n";

// Test 6: Direct endpoint test via curl
echo "\n=== Test 6: Direct endpoint output ===\n";
$_GET['id'] = 2;
ob_start();
require __DIR__ . '/php/endpoints/get-product.php';
$output = ob_get_clean();
$data = json_decode($output, true);
echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
if ($data['success']) {
    echo "Product name: " . $data['product']['name'] . "\n";
} else {
    echo "Error: " . ($data['error'] ?? 'Unknown') . "\n";
}