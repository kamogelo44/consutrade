<?php

/**
 * ConsuTrade - ProductService
 *
 * Handles product business logic including stock management,
 * status updates, and search/filter operations.
 *
 * @author Kamogelo Phale
 * @version 1.1.0
 */

class ProductService
{
    private ProductRepository $productRepo;

    public function __construct(ProductRepository $productRepo)
    {
        $this->productRepo = $productRepo;
    }

    /**
     * Get product by ID as Product object.
     */
    public function findById(int $productId): ?Product
    {
        return $this->productRepo->findById($productId);
    }

    /**
     * Get product for display (lightweight).
     */
    public function findForDisplay(int $productId): ?array
    {
        return $this->productRepo->findForDisplay($productId);
    }

    /**
     * Get product for editing with ownership verification.
     */
    public function findForEdit(int $productId, int $sellerId): ?array
    {
        return $this->productRepo->findForEdit($productId, $sellerId);
    }

    /**
     * Get seller products with filters.
     */
    public function findBySeller(int $sellerId, string $filter = 'all', string $search = '', int $limit = 0, int $offset = 0): array
    {
        return $this->productRepo->findBySeller($sellerId, $filter, $search, $limit, $offset);
    }

    /**
     * Get seller products for display on seller dashboard.
     */
    public function findBySellerForDisplay(int $sellerId, bool $isOwner = false, int $limit = 0): array
    {
        return $this->productRepo->findBySellerForDisplay($sellerId, $isOwner, $limit);
    }

    /**
     * Get all products for admin with filters.
     */
    public function findAll(string $status = 'all', string $search = '', int $limit = 12, int $offset = 0): array
    {
        return $this->productRepo->findAll($status, $search, $limit, $offset);
    }

    /**
     * Get public product listings with filters.
     */
    public function findPublic(array $filters = []): array
    {
        return $this->productRepo->findPublic($filters);
    }

    /**
     * Search products with filters.
     */
    public function search(string $search, array $filters = []): array
    {
        return $this->productRepo->search($search, $filters);
    }

    /**
     * Create a new product.
     */
    public function create(Product $product): int|false
    {
        return $this->productRepo->create($product);
    }

    /**
     * Update an existing product.
     */
    public function update(Product $product): bool
    {
        return $this->productRepo->update($product);
    }

    /**
     * Decrease product stock with validation.
     */
    public function decreaseStock(int $productId, int $quantity): bool
    {
        $currentStock = $this->productRepo->getStock($productId);

        if ($currentStock < $quantity) {
            error_log("Insufficient stock for product ID: $productId. Stock: $currentStock, Requested: $quantity");
            return false;
        }

        return $this->productRepo->decreaseStock($productId, $quantity);
    }

    /**
     * Increase product stock.
     */
    public function increaseStock(int $productId, int $quantity): bool
    {
        return $this->productRepo->increaseStock($productId, $quantity);
    }

    /**
     * Restore stock from an order (when cancelled).
     */
    public function restoreStockFromOrder(int $orderId): bool
    {
        return $this->productRepo->restoreStockFromOrder($orderId);
    }

    /**
     * Update product status with business logic.
     * 
     * @param int $id Product ID
     * @param int $sellerId Seller ID
     * @param string $action Action to perform ('suspend', 'activate', 'delete')
     * @param string $suspendedBy Who is performing the action ('admin' or 'seller')
     * @param string $suspendedReason Reason for suspension (optional)
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateStatus(int $id, int $sellerId, string $action, string $suspendedBy = 'seller', string $suspendedReason = ''): array
    {
        // Map action to status
        $newStatus = match ($action) {
            'suspend' => 'suspended',
            'activate' => 'active',
            'delete' => 'deleted',
            default => $action
        };

        // Delete action - use delete method instead
        if ($action === 'delete') {
            return $this->delete($id, $sellerId);
        }

        // Call repository method
        $result = $this->productRepo->updateStatusDirect(
            $id,
            $sellerId,
            $newStatus,
            $suspendedBy,
            $suspendedReason
        );

        if ($result) {
            return [
                'success' => true,
                'message' => 'Product status updated to ' . ucfirst($newStatus) . '.'
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to update product status.'
        ];
    }

    /**
     * Delete product and its images.
     * 
     * @param int $id Product ID
     * @param int $sellerId Seller ID
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete(int $id, int $sellerId): array
    {
        $result = $this->productRepo->delete($id, $sellerId);

        if ($result) {
            // Delete associated images
            $product = $this->productRepo->findById($id);
            if ($product) {
                // Delete main image if exists
                if ($product->getImageUrl()) {
                    $this->productRepo->deleteImageFile($product->getImageUrl());
                }
                // Delete gallery images
                // Gallery images are handled separately via ProductImageRepository
            }

            return [
                'success' => true,
                'message' => 'Product deleted successfully.'
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to delete product.'
        ];
    }

    /**
     * Get current stock quantity for a product.
     */
    public function getStock(int $productId): int
    {
        return $this->productRepo->getStock($productId);
    }

    /**
     * Count all active products.
     */
    public function countAll(): int
    {
        return $this->productRepo->countAll();
    }

    /**
     * Count products for a seller with filters.
     */
    public function countBySeller(int $sellerId, string $filter = 'all', string $search = ''): int
    {
        return $this->productRepo->countBySeller($sellerId, $filter, $search);
    }

    /**
     * Count products for a user.
     */
    public function countByUser(int $userId): int
    {
        return $this->productRepo->countByUser($userId);
    }

    /**
     * Get image URL for a product.
     */
    public function getImageUrl(?string $imageUrl): string
    {
        return $this->productRepo->getImageUrl($imageUrl);
    }

    /**
     * Upload a product image.
     */
    public function uploadImage(array $file, int $sellerId, string $productTitle, string $prefix = 'main'): string|false
    {
        return $this->productRepo->uploadImage($file, $sellerId, $productTitle, $prefix);
    }
}
