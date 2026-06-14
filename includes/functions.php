<?php
/*
 * ConsuTrade - Reusable PHP Helper Functions
 * Author: Kamogelo Phale
 * 
 * Centralizes common display functions used across multiple pages
 * All functions are wrapped with !function_exists to prevent redeclaration errors
 */

// ============================================================
// IMAGE URL HELPERS
// ============================================================

if (!function_exists('fixImageUrl')) {
    /**
     * Fix image URL to ensure correct absolute path
     * 
     * @param string|null $imagePath The image path from database
     * @param string $default Default image path (relative to baseUrl)
     * @return string Full absolute URL to image
     */
    function fixImageUrl($imagePath, $default = 'images/default-product.png')
    {
        $baseUrl = getBaseUrl();

        // If empty, return default
        if (empty($imagePath)) {
            return $baseUrl . $default;
        }

        // If already full URL, return as-is
        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return $imagePath;
        }

        // Remove leading slash if present, then prepend base URL
        $imagePath = ltrim($imagePath, '/');

        // Check if the path already includes uploads/ or images/
        if (str_starts_with($imagePath, 'uploads/') || str_starts_with($imagePath, 'images/')) {
            return $baseUrl . $imagePath;
        }

        // Default fallback
        return $baseUrl . $default;
    }
}

if (!function_exists('getSellerAvatarUrl')) {
    /**
     * Get seller avatar URL with fallback to default
     * 
     * @param User|null $seller Seller object
     * @return string Full URL to avatar
     */
    function getSellerAvatarUrl($seller)
    {
        $baseUrl = getBaseUrl();

        if ($seller && method_exists($seller, 'getProfileImageUrl')) {
            $avatarUrl = $seller->getProfileImageUrl();
            if (!empty($avatarUrl) && $avatarUrl !== $baseUrl . 'images/icons/profile-svgrepo-com.svg') {
                return $avatarUrl;
            }
        }

        return $baseUrl . 'images/icons/profile-svgrepo-com.svg';
    }
}

// ============================================================
// CONDITION BADGE HELPERS
// ============================================================

if (!function_exists('getConditionClass')) {
    /**
     * Get CSS class for condition badge
     * 
     * @param string $condition Product condition (New, Like New, Good, Fair)
     * @return string CSS class name
     */
    function getConditionClass($condition)
    {
        switch (strtolower(trim($condition))) {
            case 'new':
                return 'new';
            case 'like new':
                return 'like-new';
            case 'good':
                return 'good';
            case 'fair':
                return 'fair';
            default:
                return 'good';
        }
    }
}

if (!function_exists('renderConditionBadge')) {
    /**
     * Render condition badge HTML
     * 
     * @param string $condition Product condition
     * @return string HTML for condition badge
     */
    function renderConditionBadge($condition)
    {
        $conditionText = !empty($condition) ? ucfirst($condition) : 'Good';
        $class = getConditionClass($condition);
        return '<div class="condition-badge ' . $class . '">' . htmlspecialchars($conditionText) . '</div>';
    }
}

// ============================================================
// STOCK BADGE HELPERS
// ============================================================

if (!function_exists('renderStockBadge')) {
    /**
     * Render stock status badge HTML
     * 
     * @param int $stockQuantity Available stock quantity
     * @return string HTML for stock badge
     */
    function renderStockBadge($stockQuantity)
    {
        $stockQty = (int)$stockQuantity;

        if ($stockQty <= 0) {
            return '<div class="out-of-stock-badge-card">Out of Stock</div>';
        }

        if ($stockQty <= 5) {
            return '<div class="low-stock-badge-card">Only ' . $stockQty . ' left</div>';
        }

        return '';
    }
}

if (!function_exists('getStockStatusClass')) {
    /**
     * Get CSS class for stock status
     * 
     * @param int $stockQuantity Available stock quantity
     * @return string CSS class name
     */
    function getStockStatusClass($stockQuantity)
    {
        if ($stockQuantity <= 0) return 'out-of-stock';
        if ($stockQuantity <= 5) return 'low-stock';
        return 'in-stock';
    }
}

// ============================================================
// SELLER BADGE HELPERS
// ============================================================

if (!function_exists('renderSellerBadge')) {
    /**
     * Render seller verification badge HTML
     * 
     * @param User|null $seller Seller object
     * @return string HTML for seller badge
     */
    function renderSellerBadge($seller)
    {
        $baseUrl = getBaseUrl();

        if ($seller && $seller->isVerified()) {
            return '<div class="verified-badge-card">
                        <img src="' . $baseUrl . 'images/icons/verified-svgrepo-com.svg" width="14" height="14" alt="Verified" loading="lazy">
                        <span>Verified Seller</span>
                    </div>';
        }

        return '<div class="unverified-badge-card">
                    <img src="' . $baseUrl . 'images/icons/not-verified-svgrepo-com.svg" width="14" height="14" alt="Unverified" loading="lazy">
                    <span>Unverified</span>
                </div>';
    }
}

// ============================================================
// RATING STARS HELPERS
// ============================================================

if (!function_exists('renderStars')) {
    /**
     * Render star rating HTML
     * 
     * @param float $rating Rating value (0-5)
     * @param int $maxStars Maximum number of stars (default 5)
     * @return string HTML for star rating
     */
    function renderStars($rating, $maxStars = 5)
    {
        $rating = (float)$rating;
        $html = '<div class="review-stars">';

        for ($i = 1; $i <= $maxStars; $i++) {
            if ($i <= $rating) {
                $html .= '<span class="star">★</span>';
            } elseif ($i - 0.5 <= $rating) {
                $html .= '<span class="star half">½</span>';
            } else {
                $html .= '<span class="star empty">★</span>';
            }
        }

        $html .= '</div>';
        return $html;
    }
}

if (!function_exists('renderRatingSummary')) {
    /**
     * Render rating summary with stars and count
     * 
     * @param float $avgRating Average rating
     * @param int $reviewCount Number of reviews
     * @return string HTML for rating summary
     */
    function renderRatingSummary($avgRating, $reviewCount)
    {
        if ($reviewCount == 0) {
            return '<p>No reviews yet</p>';
        }

        return renderStars($avgRating) . '<p>Rating: ' . number_format($avgRating, 1) . '/5 (' . $reviewCount . ' reviews)</p>';
    }
}

// ============================================================
// PRICE HELPERS
// ============================================================

if (!function_exists('formatPrice')) {
    /**
     * Format price with currency symbol
     * 
     * @param float $price Price value
     * @return string Formatted price (e.g., "R 1,200.00")
     */
    function formatPrice($price)
    {
        return 'R ' . number_format((float)$price, 2);
    }
}

// ============================================================
// ORDER STATUS HELPERS
// ============================================================

if (!function_exists('getOrderStatusClass')) {
    /**
     * Get CSS class for order status badge
     * 
     * @param string $status Order status
     * @return string CSS class name
     */
    function getOrderStatusClass($status)
    {
        switch ($status) {
            case 'pending':
                return 'status-pending';
            case 'processing':
                return 'status-processing';
            case 'shipped':
                return 'status-shipped';
            case 'completed':
                return 'status-completed';
            case 'cancelled':
                return 'status-cancelled';
            default:
                return '';
        }
    }
}

if (!function_exists('getOrderStatusLabel')) {
    /**
     * Get human-readable order status label
     * 
     * @param string $status Order status
     * @return string Human-readable status
     */
    function getOrderStatusLabel($status)
    {
        switch ($status) {
            case 'pending':
                return 'Pending';
            case 'processing':
                return 'Processing';
            case 'shipped':
                return 'Shipped';
            case 'completed':
                return 'Completed';
            case 'cancelled':
                return 'Cancelled';
            default:
                return ucfirst($status);
        }
    }
}

// ============================================================
// DATE HELPERS
// ============================================================

if (!function_exists('formatDate')) {
    /**
     * Format date for display
     * 
     * @param string $dateString MySQL datetime string
     * @param string $format Date format (default: 'd M Y')
     * @return string Formatted date
     */
    function formatDate($dateString, $format = 'd M Y')
    {
        if (empty($dateString)) return '';
        return date($format, strtotime($dateString));
    }
}

if (!function_exists('formatDateTime')) {
    /**
     * Format datetime for display
     * 
     * @param string $dateString MySQL datetime string
     * @return string Formatted datetime
     */
    function formatDateTime($dateString)
    {
        if (empty($dateString)) return '';
        return date('d M Y, h:i A', strtotime($dateString));
    }
}

// ============================================================
// PRODUCT CARD RENDERING
// ============================================================

if (!function_exists('renderProductCard')) {
    /**
     * Render a complete product card HTML using Product and User objects
     * 
     * @param Product $product Product object
     * @param User|null $seller Seller object (optional, will fetch if not provided)
     * @return string HTML for product card
     */
    function renderProductCard($product, $seller = null)
    {
        $baseUrl = getBaseUrl();

        // Get seller if not provided
        if ($seller === null && isset($GLOBALS['userRepo'])) {
            $seller = $GLOBALS['userRepo']->findById($product->getSellerId());
        }

        $imageUrl = fixImageUrl($product->getImageUrl());
        $productName = htmlspecialchars($product->getTitle());
        $sellerName = $seller ? htmlspecialchars($seller->getFullName()) : 'Unknown Seller';
        $sellerLocation = $seller ? htmlspecialchars($seller->getLocation() ?: 'South Africa') : 'South Africa';
        $sellerAvatar = getSellerAvatarUrl($seller);
        $isOutOfStock = $product->isOutOfStock();

        // Add to cart button
        $addToCartButton = '';
        if (!$isOutOfStock) {
            $escapedName = addslashes($product->getTitle());
            $addToCartButton = '<button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(' . $product->getProductId() . ', \'' . $escapedName . '\', ' . $product->getPrice() . ')">
                                    <img src="' . $baseUrl . 'images/icons/shopping-cart-01-svgrepo-com.svg" width="16" height="16" alt="Cart" loading="lazy">
                                    Add to Cart
                                </button>';
        } else {
            $addToCartButton = '<button class="out-of-stock-btn" disabled>Out of Stock</button>';
        }

        return '<div class="prod-card" onclick="window.location.href=\'' . $baseUrl . 'product-details.php?id=' . $product->getProductId() . '\'">
                    <div class="img-container">
                        <img src="' . $imageUrl . '" 
                             alt="' . $productName . '" 
                             loading="lazy"
                             onerror="this.src=\'' . $baseUrl . 'images/default-product.png\'">
                        ' . renderConditionBadge($product->getCondition()) . '
                        ' . renderStockBadge($product->getStockQuantity()) . '
                    </div>
                    <div class="prod-info-container">
                        <h3 class="prod-name">' . $productName . '</h3>
                        <p class="prod-price">' . $product->getFormattedPrice() . '</p>
                        <div class="seller-info">
                            <div class="seller-avatar">
                                <img src="' . $sellerAvatar . '" 
                                     alt="' . $sellerName . '" 
                                     loading="lazy"
                                     onerror="this.src=\'' . $baseUrl . 'images/icons/profile-svgrepo-com.svg\'">
                            </div>
                            <div class="seller-details">
                                <p class="seller-name">' . $sellerName . '</p>
                                <p class="location">
                                    <img src="' . $baseUrl . 'images/icons/pin-location-svgrepo-com.svg" width="10" height="10" alt="location" loading="lazy">
                                    ' . $sellerLocation . '
                                </p>
                            </div>
                            ' . renderSellerBadge($seller) . '
                        </div>
                        ' . $addToCartButton . '
                        <div class="payment-badge">
                            <span>Secure payment via</span>
                            <img src="' . $baseUrl . 'images/icons/Payfast logo.svg" alt="PayFast" loading="lazy">
                        </div>
                    </div>
                </div>';
    }
}

// ============================================================
// STRING HELPERS
// ============================================================

if (!function_exists('truncateText')) {
    /**
     * Truncate text to a specified length
     * 
     * @param string $text The text to truncate
     * @param int $length Maximum length
     * @param string $suffix Suffix to add (default: '...')
     * @return string Truncated text
     */
    function truncateText($text, $length = 100, $suffix = '...')
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        $text = substr($text, 0, $length);
        $lastSpace = strrpos($text, ' ');

        if ($lastSpace !== false) {
            $text = substr($text, 0, $lastSpace);
        }

        return $text . $suffix;
    }
}

// ============================================================
// URL HELPERS
// ============================================================

if (!function_exists('isActivePage')) {
    /**
     * Check if current page matches given page name
     * 
     * @param string $pageName Page name to check against
     * @return string 'active' if match, empty string otherwise
     */
    function isActivePage($pageName)
    {
        $currentPage = basename($_SERVER['PHP_SELF']);
        return $currentPage === $pageName ? 'active' : '';
    }
}

if (!function_exists('getCurrentPageName')) {
    /**
     * Get current page name without extension
     * 
     * @return string Current page name
     */
    function getCurrentPageName()
    {
        return pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
    }
}
