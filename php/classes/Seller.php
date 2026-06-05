<?php

/**
 * ConsuTrade - Seller
 *
 * Domain class representing a seller user type.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */

class Seller extends User
{
    /** @var ProductRepository */
    private $productRepo;

    /** @var OrderRepository */
    private $orderRepo;

    /** @var SellerVerification|null */
    private $verification;

    /**
     * Constructor.
     *
     * @param array $data User data
     * @param ProductRepository $productRepo Product repository
     * @param OrderRepository $orderRepo Order repository
     * @param SellerVerification|null $verification Verification data
     */
    public function __construct($data, $productRepo, $orderRepo, $verification = null)
    {
        parent::__construct($data);
        $this->productRepo = $productRepo;
        $this->orderRepo = $orderRepo;
        $this->verification = $verification;
    }

    /**
     * Get the seller's display name.
     *
     * @return string
     */
    public function getDisplayName()
    {
        $verifiedBadge = $this->idVerified ? ' ✓' : '';
        return $this->fullName . $verifiedBadge;
    }

    /**
     * Get seller's products
     *
     * @param string $filter Status filter
     * @param string $search Search term
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function getProducts($filter = 'all', $search = '', $limit = 0, $offset = 0)
    {
        return $this->productRepo->getSellerProducts($this->userId, $filter, $search, $limit, $offset);
    }

    /**
     * Get seller's products as Product objects
     *
     * @param string $filter Status filter
     * @param string $search Search term
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function getProductObjects($filter = 'all', $search = '', $limit = 0, $offset = 0)
    {
        return $this->productRepo->getSellerProductObjects($this->userId, $filter, $search, $limit, $offset);
    }

    /**
     * Get seller's orders
     *
     * @param string $filter Status filter
     * @param string $search Search term
     * @return array
     */
    public function getOrders($filter = 'all', $search = '')
    {
        return $this->orderRepo->getSellerOrders($this->userId, $filter, $search);
    }

    /**
     * Get recent orders for dashboard
     *
     * @param int $limit Number of orders
     * @return array
     */
    public function getRecentOrders($limit = 5)
    {
        return $this->orderRepo->getSellerRecentOrders($this->userId, $limit);
    }

    /**
     * Get seller statistics
     *
     * @return array
     */
    public function getStats()
    {
        $totalProducts = $this->productRepo->countUserProducts($this->userId);
        $totalOrders = $this->orderRepo->getSellerTotalOrders($this->userId);
        $totalRevenue = $this->orderRepo->getSellerTotalRevenue($this->userId);
        $avgRating = $this->getAverageRating();

        return [
            'total_products' => $totalProducts,
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'avg_rating' => $avgRating,
            'is_verified' => $this->idVerified,
            'has_verification_document' => $this->verification !== null
        ];
    }

    /**
     * Get seller's average rating from reviews
     *
     * @return float
     */
    public function getAverageRating()
    {
        $reviewRepo = new ReviewRepository($this->productRepo->db ?? null);
        // Note: This needs a db connection - would need to be passed in
        // For now, return 0
        return 0;
    }

    /**
     * Add a new product
     *
     * @param array $productData Product data
     * @return int|false
     */
    public function addProduct($productData)
    {
        $product = new Product([
            'seller_id' => $this->userId,
            'category_id' => $productData['category_id'],
            'title' => $productData['title'],
            'description' => $productData['description'],
            'price' => $productData['price'],
            'stock_quantity' => $productData['stock_quantity'],
            'condition' => $productData['condition'],
            'location' => $productData['location'],
            'image_url' => $productData['image_url'] ?? '',
            'status' => 'active'
        ]);

        return $this->productRepo->createProduct($product);
    }

    /**
     * Update an existing product
     *
     * @param int $productId Product ID
     * @param array $productData Product data
     * @return array
     */
    public function updateProduct($productId, $productData)
    {
        // Verify ownership
        $existing = $this->productRepo->getProductForEdit($productId, $this->userId);
        if (!$existing) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        return $this->productRepo->updateSellerProduct($productId, $this->userId, $productData);
    }

    /**
     * Delete a product (soft delete)
     *
     * @param int $productId Product ID
     * @return array
     */
    public function deleteProduct($productId)
    {
        return $this->productRepo->deleteSellerProduct($productId, $this->userId);
    }

    /**
     * Suspend a product (seller action)
     *
     * @param int $productId Product ID
     * @param string $reason Suspension reason
     * @return array
     */
    public function suspendProduct($productId, $reason = '')
    {
        return $this->productRepo->updateProductStatus($productId, $this->userId, 'suspend', 'seller', $reason);
    }

    /**
     * Reactivate a suspended product (seller action)
     *
     * @param int $productId Product ID
     * @return array
     */
    public function reactivateProduct($productId)
    {
        return $this->productRepo->updateProductStatus($productId, $this->userId, 'activate', 'seller');
    }

    /**
     * Update order status (seller action)
     *
     * @param int $orderId Order ID
     * @param string $status New status
     * @return array
     */
    public function updateOrderStatus($orderId, $status)
    {
        $orderDetails = $this->orderRepo->getOrderDetails($orderId, $this->userId, 'seller');
        if (!$orderDetails) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        return $this->orderRepo->updateSellerOrderStatus($orderId, $this->userId, $status);
    }

    /**
     * Check if seller can add more products
     *
     * @param int $maxProducts Maximum allowed (default 50)
     * @return bool
     */
    public function canAddMoreProducts($maxProducts = 50)
    {
        $currentCount = $this->productRepo->countUserProducts($this->userId);
        return $currentCount < $maxProducts;
    }

    /**
     * Get seller verification status
     *
     * @return array
     */
    public function getVerificationStatus()
    {
        if ($this->idVerified) {
            return [
                'is_verified' => true,
                'status' => 'verified',
                'message' => 'Your seller account is verified'
            ];
        }

        if ($this->verification && $this->verification->isPending()) {
            return [
                'is_verified' => false,
                'status' => 'pending',
                'message' => 'Verification documents submitted. Pending review.'
            ];
        }

        if ($this->verification && $this->verification->isRejected()) {
            return [
                'is_verified' => false,
                'status' => 'rejected',
                'message' => 'Verification was rejected. Please submit new documents.',
                'rejection_reason' => $this->verification->getRejectionReason()
            ];
        }

        return [
            'is_verified' => false,
            'status' => 'not_submitted',
            'message' => 'Submit verification documents to become a verified seller'
        ];
    }

    /**
     * Submit verification documents
     *
     * @param string $documentPath Path to uploaded document
     * @param string $documentType Type of document (id, business_license, etc.)
     * @return bool
     */
    public function submitVerificationDocuments($documentPath, $documentType = 'id')
    {
        $stmt = $this->db->prepare("
            INSERT INTO seller_verification (seller_id, document_path, document_type, submitted_at) 
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            document_path = ?, document_type = ?, document_verified = 0, submitted_at = NOW(), rejection_reason = NULL
        ");
        $stmt->bind_param('isss', $this->userId, $documentPath, $documentType, $documentPath, $documentType);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Get seller's public profile data
     *
     * @return array
     */
    public function getPublicProfile()
    {
        $products = $this->productRepo->getSellerProductsForDisplay($this->userId, false, 12);
        $stats = $this->getStats();
        $reviews = []; // Would need review repository

        return [
            'seller_id' => $this->userId,
            'full_name' => $this->fullName,
            'profile_image' => $this->getProfileImageUrl(),
            'location' => $this->location,
            'is_verified' => $this->idVerified,
            'member_since' => date('F Y', strtotime($this->createdAt)),
            'total_products' => $stats['total_products'],
            'total_sales' => $stats['total_orders'],
            'rating' => 0, // Would need to calculate from reviews
            'products' => $products
        ];
    }
}
