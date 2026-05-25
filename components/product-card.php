<?php
/*
 * ConsuTrade - Product Card Component
 * Author: Kamogelo Phale
 * 
 * Reusable product card used across homepage, listings, and search results
 * Expects $product array with id, name, price, image, condition, seller_name, location, profile_image, is_verified
 */

// ========== EXTRACT PRODUCT DATA ==========
$productId = $product['id'] ?? 0;
$productName = $product['name'] ?? '';
$productPrice = $product['price'] ?? 0;
$productImage = getProductImageUrl($product['image'] ?? '');
$productCondition = $product['condition'] ?? 'Good';
$sellerName = $product['seller_name'] ?? '';
$sellerLocation = $product['location'] ?? 'South Africa';
$sellerAvatar = getSellerAvatar($product['profile_image'] ?? null);
$isVerified = $product['is_verified'] ?? false;
$stock = $product['stock_quantity'] ?? 0;
$isOutOfStock = $stock <= 0;
?>

<!-- ========== PRODUCT CARD HTML ========== -->
<div class="prod-card" data-product-id="<?php echo $productId; ?>" onclick="window.location.href='<?php echo $baseUrl; ?>product-details.php?id=<?php echo $productId; ?>'">
    
    <!-- ========== IMAGE SECTION ========== -->
    <div class="img-container">
        <img src="<?php echo $productImage; ?>" alt="<?php echo htmlspecialchars($productName); ?>" loading="lazy" onerror="this.src='<?php echo $baseUrl; ?>images/default-product.png'">
        
        <div class="condition-badge <?php echo strtolower(str_replace(' ', '-', $productCondition)); ?>">
            <?php echo htmlspecialchars($productCondition); ?>
        </div>
        
        <?php if ($isOutOfStock): ?>
        <div class="out-of-stock-badge-card">Out of Stock</div>
        <?php endif; ?>
    </div>
    
    <!-- ========== PRODUCT INFO SECTION ========== -->
    <div class="prod-info-container">
        <h3 class="prod-name"><?php echo htmlspecialchars($productName); ?></h3>
        <p class="prod-price">R <?php echo number_format($productPrice, 2); ?></p>
        
        <!-- ========== SELLER INFO SECTION ========== -->
        <div class="seller-info">
            <div class="seller-avatar">
                <img src="<?php echo $sellerAvatar; ?>" alt="<?php echo htmlspecialchars($sellerName); ?>" onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
            </div>
            
            <div class="seller-details">
                <p class="seller-name"><?php echo htmlspecialchars($sellerName); ?></p>
                <p class="location">
                    <img src="<?php echo $baseUrl; ?>images/icons/pin-location-svgrepo-com.svg" width="10" height="10" alt="location">
                    <?php echo htmlspecialchars($sellerLocation); ?>
                </p>
            </div>
            
            <!-- ========== VERIFICATION BADGE ========== -->
            <?php if ($isVerified): ?>
            <div class="verified-badge-card">
                <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="14" height="14">
                <span>Verified Seller</span>
            </div>
            <?php else: ?>
            <div class="unverified-badge-card">
                <img src="<?php echo $baseUrl; ?>images/icons/not-verified-svgrepo-com.svg" width="14" height="14">
                <span>Unverified</span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- ========== ACTION BUTTONS ========== -->
        <?php if (!$isOutOfStock): ?>
        <button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(<?php echo $productId; ?>, '<?php echo addslashes($productName); ?>', <?php echo $productPrice; ?>)">
            <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="16" height="16" alt="Cart">
            Add to Cart
        </button>
        <?php else: ?>
        <button class="out-of-stock-btn" disabled>
            <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="16" height="16" alt="Cart">
            Out of Stock
        </button>
        <?php endif; ?>
        
        <!-- ========== PAYMENT BADGE ========== -->
        <div class="payment-badge">
            <span>Secure payment via</span>
            <img src="<?php echo $baseUrl; ?>images/icons/Payfast logo.svg" alt="PayFast" width="40" height="16">
        </div>
    </div>
</div>