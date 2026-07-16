<?php
/*
 * ConsuTrade - Search Products API (AJAX)
 * Author: Kamogelo Phale
 * 
 * Returns search results for the search page with filters and pagination
 */

require_once dirname(__DIR__, 3) . '/init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Category detection keywords
$categoryKeywords = [
    'clothing' => ['clothing', 'clothes', 'shirt', 'pants', 'dress', 'jacket', 'jeans', 't-shirt', 'tshirt', 'hoodie', 'sweater', 'shoes', 'sneakers', 'boots', 'accessories', 'hat', 'belt', 'bag', 'wallet', 'fashion', 'wear', 'apparel', 'garment', 'outfit'],
    'electronics' => ['electronics', 'phone', 'smartphone', 'laptop', 'computer', 'tablet', 'tv', 'television', 'headphones', 'earphones', 'speaker', 'charger', 'cable', 'battery', 'screen', 'keyboard', 'mouse', 'monitor', 'printer', 'scanner', 'camera', 'drone', 'gadget', 'tech', 'device', 'accessory'],
    'food' => ['food', 'drink', 'beverage', 'snack', 'meal', 'dinner', 'lunch', 'breakfast', 'fruit', 'vegetable', 'meat', 'chicken', 'beef', 'pork', 'fish', 'seafood', 'bread', 'pastry', 'cake', 'cookie', 'chocolate', 'candy', 'soda', 'juice', 'water', 'coffee', 'tea', 'wine', 'beer', 'spirit', 'alcohol', 'grocery', 'produce', 'bakery', 'dairy', 'cheese', 'milk', 'egg', 'rice', 'pasta', 'sauce', 'spice', 'herb', 'organic', 'fresh', 'frozen', 'canned', 'packaged', 'snack', 'treat', 'dessert', 'ice cream', 'yogurt', 'butter', 'oil', 'vinegar', 'condiment', 'jam', 'honey', 'cereal', 'granola', 'nut', 'seed', 'dried fruit', 'pet food'],
    'furniture' => ['furniture', 'sofa', 'couch', 'chair', 'table', 'desk', 'bed', 'mattress', 'dresser', 'wardrobe', 'closet', 'shelf', 'bookcase', 'cabinet', 'drawer', 'chest', 'ottoman', 'bench', 'stool', 'lamp', 'mirror', 'rug', 'carpet', 'curtain', 'blind', 'cushion', 'pillow', 'blanket', 'throw', 'decor', 'decoration', 'home', 'interior', 'furnishing', 'wood', 'metal', 'glass'],
    'beauty' => ['beauty', 'skincare', 'makeup', 'cosmetic', 'lotion', 'cream', 'serum', 'oil', 'mask', 'scrub', 'exfoliant', 'cleanser', 'toner', 'moisturizer', 'sunscreen', 'foundation', 'powder', 'blush', 'bronzer', 'highlight', 'concealer', 'lipstick', 'gloss', 'liner', 'mascara', 'eyeshadow', 'brow', 'brush', 'tool', 'nail', 'polish', 'remover', 'hair', 'shampoo', 'conditioner', 'mask', 'treatment', 'styling', 'gel', 'mousse', 'spray', 'perfume', 'fragrance', 'deodorant', 'soap', 'body wash', 'bath', 'bubble', 'salts', 'sponge', 'towel', 'robe', 'slipper'],
];

$response = [
    'success' => false,
    'products' => [],
    'total_pages' => 0,
    'current_page' => 1,
    'total_results' => 0,
    'detected_categories' => [],
    'detected_category_hint' => null
];

try {
    // Get and sanitize search parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(48, max(1, (int)$_GET['limit'])) : 12;
    $sort = $_GET['sort'] ?? 'newest';

    // If no search term, return empty result
    if (empty($search)) {
        echo json_encode($response);
        exit;
    }

    // ============================================================
    // DETECT CATEGORY FROM SEARCH QUERY
    // ============================================================
    $detectedCategories = [];
    $searchLower = strtolower($search);

    foreach ($categoryKeywords as $category => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($searchLower, $keyword) !== false) {
                $detectedCategories[] = $category;
                break;
            }
        }
    }
    $detectedCategories = array_unique($detectedCategories);

    $categoryLabels = [
        'clothing' => 'Clothing & Accessories',
        'electronics' => 'Electronics',
        'food' => 'Food & Drinks',
        'furniture' => 'Furniture',
        'beauty' => 'Beauty & Health',
        'other' => 'Other'
    ];

    $response['detected_categories'] = $detectedCategories;

    if (!empty($detectedCategories)) {
        $categoryHints = array_map(function ($cat) use ($categoryLabels) {
            return $categoryLabels[$cat] ?? ucfirst($cat);
        }, $detectedCategories);
        $response['detected_category_hint'] = implode(' or ', $categoryHints);
    }

    // Filter parameters
    $categories = isset($_GET['categories']) ? array_filter(explode(',', $_GET['categories'])) : [];
    $price_range = $_GET['price_range'] ?? '';
    $location = isset($_GET['location']) ? trim($_GET['location']) : '';

    // If no categories are selected but we detected one, auto-add it
    if (empty($categories) && !empty($detectedCategories)) {
        $categories = $detectedCategories;
    }

    // Build filters array for ProductService
    $filters = [
        'categories' => $categories,
        'price_range' => $price_range,
        'location' => $location,
        'sort' => $sort,
        'limit' => $limit,
        'offset' => ($page - 1) * $limit,
    ];

    // Use ProductService for search
    $result = $productService->search($search, $filters);

    $formattedProducts = [];
    foreach ($result['products'] as $product) {
        $formattedProducts[] = [
            'id' => (int) $product['id'] ?? 0,
            'name' => $product['product_name'] ?? $product['name'] ?? 'Product',
            'product_name' => $product['product_name'] ?? $product['name'] ?? 'Product',
            'price' => (float) $product['price'] ?? 0,
            'image' => $productService->getImageUrl($product['image'] ?? ''),
            'image_url' => $productService->getImageUrl($product['image'] ?? ''),
            'display_image' => $productService->getImageUrl($product['image'] ?? ''),
            'seller_name' => $product['seller_name'] ?? 'Unknown Seller',
            'seller_id' => (int) $product['seller_id'] ?? 0,
            'location' => $product['location'] ?? 'South Africa',
            'condition' => $product['condition'] ?? 'Good',
            'stock_quantity' => (int) ($product['stock_quantity'] ?? 0),
            'is_verified' => (bool) ($product['is_verified'] ?? false),
            'profile_image' => $product['profile_image'] ?? null,
            'created_at' => $product['created_at'] ?? '',
            'rating' => $product['rating'] ?? null,
            'review_count' => $product['review_count'] ?? 0
        ];
    }

    // Build response
    $response['success'] = true;
    $response['products'] = $formattedProducts;
    $response['total_pages'] = ceil($result['total'] / $limit);
    $response['current_page'] = $page;
    $response['total_results'] = (int) $result['total'];
} catch (Exception $e) {
    error_log("Search Products Error: " . $e->getMessage());
    $response['message'] = 'An error occurred while searching.';
}

echo json_encode($response);
exit;
