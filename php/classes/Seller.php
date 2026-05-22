<?php
/**
 * ConsuTrade - Seller
 *
 * Represents a seller user. Extends User with product management,
 * order fulfilment, and verification capabilities.
 *
 * @author     Kamogelo Phale
 * @module     ITECA3-12 Web Development and e-Commerce
 * @institution Eduvos
 * @version    2.0.0
 * @since      2026
 *
 * References:
 * - Pressman, R.S. and Maxim, B.R., 2015. Software Engineering:
 *   A Practitioner's Approach. 8th ed. McGraw-Hill.
 * - Dennis, A., Wixom, B.H. and Tegarden, D., 2015. Systems Analysis
 *   and Design: An Object-Oriented Approach with UML. 6th ed.
 *   John Wiley and Sons.
 * - PHP Group, 2025. Classes and Objects. Available at:
 *   https://www.php.net/manual/en/language.oop5.php
 * - PHP-FIG, 2023. PSR-12: Extended Coding Style. Available at:
 *   https://www.php.fig.org/psr/psr-12/
 */

class Seller extends User
{
    /** @var SellerVerification|null */
    private $verification;

    /** @var ProductRepository */
    private $productRepo;

    /** @var OrderRepository */
    private $orderRepo;

    /**
     * Constructor.
     *
     * @param array              $data        User data from the database
     * @param ProductRepository  $productRepo Product repository instance
     * @param OrderRepository    $orderRepo   Order repository instance
     * @param SellerVerification|null $verification Verification data (optional)
     */
    public function __construct(
        array $data,
        ProductRepository $productRepo,
        OrderRepository $orderRepo,
        ?SellerVerification $verification = null
    ) {
        parent::__construct($data);
        $this->productRepo  = $productRepo;
        $this->orderRepo    = $orderRepo;
        $this->verification = $verification;
    }

    /**
     * Returns the seller's display name with verification indicator.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        if ($this->verification && $this->verification->isFullyVerified()) {
            return $this->fullName . ' ✓';
        }

        return $this->fullName;
    }

    // ============================================================
    //  ORDER MANAGEMENT
    // ============================================================

    /**
     * Gets the seller's orders with optional filters.
     *
     * @param string $filter Status filter
     * @param string $search Search term
     * @return array
     */
    public function getOrders(string $filter = 'all', string $search = ''): array
    {
        return $this->orderRepo->getSellerOrders($this->userId, $filter, $search);
    }

    /**
     * Updates the status of an order.
     *
     * @param int    $orderId Order ID
     * @param string $status  New status
     * @return array          ['success' => bool, 'message' => string]
     */
    public function updateOrderStatus(int $orderId, string $status): array
    {
        return $this->orderRepo->updateSellerOrderStatus($orderId, $this->userId, $status);
    }

    // ============================================================
    //  PRODUCT MANAGEMENT
    // ============================================================

    /**
     * Gets the seller's products with optional filters.
     *
     * @param string $filter Status filter ('all', 'active', 'suspended')
     * @param string $search Search term
     * @return array
     */
    public function getProducts(string $filter = 'all', string $search = ''): array
    {
        return $this->productRepo->getSellerProducts($this->userId, $filter, $search);
    }

    /**
     * Activates or suspends a product.
     *
     * @param int    $id     Product ID
     * @param string $action 'activate' or 'suspend'
     * @return array         ['success' => bool, 'message' => string]
     */
    public function updateProductStatus(int $id, string $action): array
    {
        return $this->productRepo->updateProductStatus($id, $this->userId, $action);
    }

    /**
     * Soft-deletes a product.
     *
     * @param int $productId Product ID
     * @return array         ['success' => bool, 'message' => string]
     */
    public function deleteProduct(int $productId): array
    {
        return $this->productRepo->deleteSellerProduct($productId, $this->userId);
    }

    /**
     * Gets a single product for editing (with ownership verification).
     *
     * @param int $productId Product ID
     * @return array|null
     */
    public function getProductForEdit(int $productId): ?array
    {
        return $this->productRepo->getProductForEdit($productId, $this->userId);
    }

    /**
     * Updates a product's details.
     *
     * @param int   $id   Product ID
     * @param array $data Product data
     * @return array      ['success' => bool, 'message' => string]
     */
    public function updateProduct(int $id, array $data): array
    {
        return $this->productRepo->updateSellerProduct($id, $this->userId, $data);
    }

    // ============================================================
    //  VERIFICATION
    // ============================================================

    /**
     * Returns the seller's verification status breakdown.
     *
     * @return array
     */
    public function viewVerificationStatus(): array
    {
        if ($this->verification === null) {
            return [
                'email_verified'    => false,
                'phone_verified'    => false,
                'document_verified' => false,
                'location_verified' => false,
                'auto_verified'     => false,
                'score'             => 0,
                'fully_verified'    => false,
            ];
        }

        return $this->verification->getStatus();
    }

    /**
     * Checks whether the seller is fully verified.
     *
     * @return bool
     */
    public function isVerifiedSeller(): bool
    {
        return $this->verification !== null && $this->verification->isFullyVerified();
    }
}