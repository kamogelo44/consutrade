<?php

/**
 * ConsuTrade - Seller
 *
 * Domain class representing a seller user type.
 * Contains ONLY business logic, no database operations.
 *
 * @author Kamogelo Phale
 * @version 2.1.0
 */

class Seller extends User
{
    /** @var SellerVerification|null Verification data (domain object, not repository) */
    private ?SellerVerification $verification;

    /**
     * Constructor.
     *
     * @param array $data User data from database
     * @param SellerVerification|null $verification Verification data (optional)
     */
    public function __construct(array $data, ?SellerVerification $verification = null)
    {
        parent::__construct($data);
        $this->verification = $verification;
    }

    /**
     * Get the seller's display name with verification badge.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        $verifiedBadge = $this->idVerified ? ' ✓' : '';
        return $this->fullName . $verifiedBadge;
    }

    /**
     * Check if seller can add more products.
     * Business rule: max 50 products per seller.
     *
     * @param int $currentProductCount Current product count from repository
     * @param int $maxProducts Maximum allowed (default 50)
     * @return bool
     */
    public function canAddMoreProducts(int $currentProductCount, int $maxProducts = 50): bool
    {
        return $currentProductCount < $maxProducts;
    }

    /**
     * Check if seller can edit a product.
     * Business logic: can only edit if product belongs to them.
     *
     * @param int $productSellerId Seller ID of the product
     * @return bool
     */
    public function ownsProduct(int $productSellerId): bool
    {
        return $this->userId === $productSellerId;
    }

    /**
     * Get seller verification status (business logic only).
     *
     * @return array
     */
    public function getVerificationStatus(): array
    {
        // Already verified by admin
        if ($this->idVerified) {
            return [
                'is_verified' => true,
                'status' => 'verified',
                'message' => 'Your seller account is verified'
            ];
        }

        // No verification document submitted
        if (!$this->verification) {
            return [
                'is_verified' => false,
                'status' => 'not_submitted',
                'message' => 'Submit verification documents to become a verified seller'
            ];
        }

        // Verification pending review
        if ($this->verification->isPending()) {
            return [
                'is_verified' => false,
                'status' => 'pending',
                'message' => 'Verification documents submitted. Pending review.'
            ];
        }

        // Verification was rejected
        if ($this->verification->isRejected()) {
            return [
                'is_verified' => false,
                'status' => 'rejected',
                'message' => 'Verification was rejected. Please submit new documents.',
                'rejection_reason' => $this->verification->getRejectionReason()
            ];
        }

        return [
            'is_verified' => false,
            'status' => 'unknown',
            'message' => 'Verification status unknown'
        ];
    }

    /**
     * Calculate seller statistics from provided data.
     * This method receives data from repositories, doesn't fetch it.
     *
     * @param int $totalProducts Total products count
     * @param int $totalOrders Total orders count
     * @param float $totalRevenue Total revenue
     * @param float $averageRating Average rating from reviews
     * @return array
     */
    public function calculateStats(
        int $totalProducts,
        int $totalOrders,
        float $totalRevenue,
        float $averageRating
    ): array {
        return [
            'total_products' => $totalProducts,
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'avg_rating' => round($averageRating, 1),
            'is_verified' => $this->idVerified,
            'has_verification_document' => $this->verification !== null,
            'member_since' => date('F Y', strtotime($this->createdAt))
        ];
    }

    /**
     * Get seller's public profile data.
     * Receives products and stats from repositories.
     *
     * @param array $products List of products (from ProductRepository)
     * @param array $stats Pre-calculated stats
     * @return array
     */
    public function getPublicProfile(array $products, array $stats): array
    {
        return [
            'seller_id' => $this->userId,
            'full_name' => $this->fullName,
            'profile_image' => $this->getProfileImageUrl(),
            'location' => $this->location,
            'is_verified' => $this->idVerified,
            'member_since' => date('F Y', strtotime($this->createdAt)),
            'total_products' => $stats['total_products'],
            'total_sales' => $stats['total_orders'],
            'rating' => $stats['avg_rating'] ?? 0,
            'products' => $products
        ];
    }

    /**
     * Get verification document info (if exists).
     *
     * @return SellerVerification|null
     */
    public function getVerification(): ?SellerVerification
    {
        return $this->verification;
    }
}
