<?php

/**
 * ConsuTrade - Buyer
 *
 * Domain class representing a buyer user type.
 * Contains ONLY business logic, no database operations.
 *
 * @author Kamogelo Phale
 * @version 3.0.0
 */

class Buyer extends User
{
    /**
     * Constructor.
     *
     * @param array $data User data from database
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    /**
     * Get the buyer's display name.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->fullName;
    }

    /**
     * Check if buyer can leave a review for a product.
     * Business logic only - buyer must have purchased the product.
     *
     * @param int $productId Product ID to check
     * @param array $purchasedProductIds List of product IDs buyer has purchased
     * @return bool
     */
    public function canReviewProduct(int $productId, array $purchasedProductIds): bool
    {
        return in_array($productId, $purchasedProductIds);
    }

    /**
     * Check if buyer has reached max cart items (business rule).
     *
     * @param int $currentCartCount Current items in cart
     * @param int $maxItems Maximum allowed (default 50)
     * @return bool
     */
    public function canAddToCart(int $currentCartCount, int $maxItems = 50): bool
    {
        return $currentCartCount < $maxItems;
    }

    /**
     * Get buyer statistics as array (calculated from passed data).
     *
     * @param int $totalOrders Total orders from repository
     * @param float $totalSpent Total spent from repository
     * @return array
     */
    public function getStats(int $totalOrders, float $totalSpent): array
    {
        return [
            'total_orders' => $totalOrders,
            'total_spent' => $totalSpent,
            'member_since' => date('F Y', strtotime($this->createdAt)),
            'is_active' => $this->status === 'active'
        ];
    }
}
