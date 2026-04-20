<?php
/*
 * ConsuTrade - Seller Profile Page
 * Author: Kamogelo Phale
 * 
 * This page displays seller information, their products, and reviews from buyers
 */

session_start();

$baseUrl = "/www/consutrade/";

// Get seller ID from URL
$seller_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($seller_id <= 0) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

require_once 'php/config.php';

// Get seller information
$seller_sql = "SELECT user_id, full_name, email, location, phone, id_verified as is_verified, created_at
               FROM users 
               WHERE user_id = ? AND role = 'seller'";

$seller_stmt = $conn->prepare($seller_sql);
$seller_stmt->bind_param('i', $seller_id);
$seller_stmt->execute();
$seller_result = $seller_stmt->get_result();

if ($seller_result->num_rows === 0) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$seller = $seller_result->fetch_assoc();
$seller_stmt->close();

// Get seller's products
$products_sql = "SELECT product_id, title, price, image_url, status, created_at
                 FROM products 
                 WHERE seller_id = ? AND status = 'active'
                 ORDER BY created_at DESC
                 LIMIT 10";

$products_stmt = $conn->prepare($products_sql);
$products_stmt->bind_param('i', $seller_id);
$products_stmt->execute();
$products_result = $products_stmt->get_result();

$products = [];
while ($row = $products_result->fetch_assoc()) {
    $products[] = $row;
}
$products_stmt->close();

// Get seller reviews
$reviews_sql = "SELECT r.rating, r.comment, r.created_at,
                u.full_name as buyer_name
                FROM reviews r
                JOIN users u ON r.buyer_id = u.user_id
                WHERE r.seller_id = ?
                ORDER BY r.created_at DESC";

$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param('i', $seller_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();

$reviews = [];
$total_rating = 0;
while ($row = $reviews_result->fetch_assoc()) {
    $reviews[] = $row;
    $total_rating += $row['rating'];
}

$review_count = count($reviews);
$avg_rating = $review_count > 0 ? round($total_rating / $review_count, 1) : 0;

$reviews_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($seller['full_name']); ?> - Seller Profile | ConsuTrade</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/login-signup.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/seller-profile.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="seller-profile-container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="/www/consutrade/index.php">Home</a>
            <span class="breadcrumb-separator">></span>
            <a href="/www/consutrade/product-listings.php">Product Listings</a>
            <span class="breadcrumb-separator">></span>
            <span class="current-page">Seller Profile</span>
        </div>

        <!-- Seller Information Section -->
        <div class="seller-info-section">
            <div class="seller-card">
                <div class="seller-avatar-large">
                    <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="Seller Avatar">
                </div>
                <div class="seller-details-large">
                    <h1><?php echo htmlspecialchars($seller['full_name']); ?></h1>
                    <div class="seller-meta">
                        <?php if ($seller['is_verified'] == 1): ?>
                            <span class="verified-badge-large">
                                <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16px" height="16px" alt="Verified">
                                Verified Seller
                            </span>
                        <?php else: ?>
                            <span class="unverified-badge-large">
                                <img src="<?php echo $baseUrl; ?>images/icons/not-verified-svgrepo-com.svg" width="16px" height="16px" alt="Not Verified">
                                Not Verified
                            </span>
                        <?php endif; ?>
                        
                        <span class="member-since">
                            Member since <?php echo date('F Y', strtotime($seller['created_at'])); ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($seller['location'])): ?>
                        <p class="seller-location">
                            <img src="<?php echo $baseUrl; ?>images/icons/pin-location-svgrepo-com.svg" width="16px" height="16px" alt="Location">
                            <?php echo htmlspecialchars($seller['location']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="seller-rating-summary">
                        <div class="rating-stars-large">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $avg_rating): ?>
                                    <span class="star filled">★</span>
                                <?php elseif ($i - 0.5 <= $avg_rating): ?>
                                    <span class="star half">★</span>
                                <?php else: ?>
                                    <span class="star empty">★</span>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-value"><?php echo $avg_rating; ?> / 5</span>
                        <span class="review-count">(<?php echo $review_count; ?> reviews)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seller Products Section -->
        <section class="seller-products-section">
            <h2>Products by <?php echo htmlspecialchars($seller['full_name']); ?></h2>
            
            <?php if (count($products) > 0): ?>
                <div class="seller-products-grid">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $imagePath = $product['image_url'];
                        if (!empty($imagePath)) {
                            $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $imagePath;
                            if (file_exists($fullPath)) {
                                $imagePath = $baseUrl . $imagePath;
                            } else {
                                $imagePath = $baseUrl . 'images/default-product.png';
                            }
                        } else {
                            $imagePath = $baseUrl . 'images/default-product.png';
                        }
                        ?>
                        <div class="seller-product-card" onclick="window.location.href='product-details.php?id=<?php echo $product['product_id']; ?>'">
                            <div class="product-image">
                                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                                <p class="product-price">R <?php echo number_format($product['price'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-products">This seller hasn't listed any products yet.</p>
            <?php endif; ?>
        </section>

        <!-- Seller Reviews Section -->
        <section class="seller-reviews-section">
            <h2>Customer Reviews</h2>
            
            <?php if (count($reviews) > 0): ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" width="24px" height="24px" alt="Buyer">
                                    <span class="reviewer-name"><?php echo htmlspecialchars($review['buyer_name']); ?></span>
                                </div>
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $review['rating']): ?>
                                            <span class="star small filled">★</span>
                                        <?php else: ?>
                                            <span class="star small empty">★</span>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <span class="review-date"><?php echo date('d M Y', strtotime($review['created_at'])); ?></span>
                            </div>
                            <?php if (!empty($review['comment'])): ?>
                                <p class="review-comment"><?php echo htmlspecialchars($review['comment']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-reviews">No reviews yet. Be the first to leave a review!</p>
            <?php endif; ?>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/main.js"></script>
</body>
</html>