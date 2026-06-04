<?php
/*
 * ConsuTrade - Product Display Helpers
 * Uses existing Product OOP methods to avoid duplication
 */

if (!function_exists('displayProductCard')) {
    /**
     * Generate product card HTML using Product object
     * 
     * @param Product $product Product object
     * @param User $seller Seller object (optional, will fetch if not provided)
     * @return string HTML for product card
     */
    function displayProductCard(Product $product, ?User $seller = null): string
    {
        $baseUrl = getBaseUrl();

        // Get seller if not provided
        if ($seller === null && isset($GLOBALS['userRepo'])) {
            $seller = $GLOBALS['userRepo']->findById($product->getSellerId());
        }

        $imageUrl = getProductImageUrl($product->getImageUrl());
        $productName = htmlspecialchars($product->getTitle());
        $formattedPrice = $product->getFormattedPrice();
        $conditionClass = $product->getConditionClass();
        $conditionText = htmlspecialchars($product->getCondition());

        // Stock badge
        $stockBadge = '';
        if ($product->isOutOfStock()) {
            $stockBadge = '<div class="out-of-stock-badge-card">Out of Stock</div>';
        } elseif ($product->isLowStock()) {
            $stockBadge = '<div class="low-stock-badge-card">Only ' . $product->getStockQuantity() . ' left</div>';
        }

        // Seller info
        $sellerName = $seller ? htmlspecialchars($seller->getFullName()) : 'Unknown Seller';
        $sellerAvatar = $seller ? $seller->getProfileImageUrl() : $baseUrl . 'images/icons/profile-svgrepo-com.svg';
        $sellerLocation = $seller ? htmlspecialchars($seller->getLocation() ?: 'South Africa') : 'South Africa';
        $verifiedBadge = $seller && $seller->isVerified()
            ? '<div class="verified-badge-card"><img src="' . $baseUrl . 'images/icons/verified-svgrepo-com.svg" width="14" height="14"><span>Verified Seller</span></div>'
            : '<div class="unverified-badge-card"><img src="' . $baseUrl . 'images/icons/not-verified-svgrepo-com.svg" width="14" height="14"><span>Unverified</span></div>';

        // Add to cart button (disabled if out of stock)
        $addToCartButton = '';
        if (!$product->isOutOfStock()) {
            $addToCartButton = '<button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(' . $product->getProductId() . ', \'' . addslashes($product->getTitle()) . '\', ' . $product->getPrice() . ')">
                <img src="' . $baseUrl . 'images/icons/shopping-cart-01-svgrepo-com.svg" width="16" height="16"> Add to Cart
            </button>';
        } else {
            $addToCartButton = '<button class="out-of-stock-btn" disabled>Out of Stock</button>';
        }

        return '<div class="prod-card" onclick="window.location.href=\'' . $baseUrl . 'product-details.php?id=' . $product->getProductId() . '\'">
            <div class="img-container">
                <img src="' . $imageUrl . '" alt="' . $productName . '" onerror="this.src=\'' . $baseUrl . 'images/default-product.png\'">
                <div class="condition-badge ' . $conditionClass . '">' . $conditionText . '</div>
                ' . $stockBadge . '
            </div>
            <div class="prod-info-container">
                <h3 class="prod-name">' . $productName . '</h3>
                <p class="prod-price">' . $formattedPrice . '</p>
                <div class="seller-info">
                    <div class="seller-avatar"><img src="' . $sellerAvatar . '" alt="' . $sellerName . '" onerror="this.src=\'' . $baseUrl . 'images/icons/profile-svgrepo-com.svg\'"></div>
                    <div class="seller-details">
                        <p class="seller-name">' . $sellerName . '</p>
                        <p class="location"><img src="' . $baseUrl . 'images/icons/pin-location-svgrepo-com.svg" width="10" height="10"> ' . $sellerLocation . '</p>
                    </div>
                    ' . $verifiedBadge . '
                </div>
                ' . $addToCartButton . '
                <div class="payment-badge">
                    <span>Secure payment via</span>
                    <img src="' . $baseUrl . 'images/icons/Payfast logo.svg" alt="PayFast">
                </div>
            </div>
        </div>';
    }
}

if (!function_exists('getProductImageUrl')) {
    function getProductImageUrl(?string $imagePath): string
    {
        $baseUrl = getBaseUrl();
        if (empty($imagePath)) {
            return $baseUrl . 'images/default-product.png';
        }
        if (str_starts_with($imagePath, 'http')) {
            return $imagePath;
        }
        return $baseUrl . ltrim($imagePath, '/');
    }
}

if (!function_exists('renderStarsStatic')) {
    /**
     * Generate star rating HTML (PHP version for server-side rendering)
     */
    function renderStarsStatic(int $rating, int $max = 5): string
    {
        $html = '<div class="review-stars">';
        for ($i = 1; $i <= $max; $i++) {
            $html .= ($i <= $rating) ? '<span class="star">★</span>' : '<span class="star empty">★</span>';
        }
        $html .= '</div>';
        return $html;
    }
}
