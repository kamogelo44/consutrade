<?php

/**
 * ConsuTrade - Product
 *
 * Domain class representing a single product listing.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */
class Product
{
    private $productId;
    private $sellerId;
    private $categoryId;
    private $title;
    private $description;
    private $price;
    private $stockQuantity;
    private $condition;
    private $location;
    private $imageUrl;
    private $status;
    private $createdAt;
    private $suspendedBy;
    private $suspendedReason;

    /**
     * Product constructor.
     *
     * @param array $data Product data from database
     */
    public function __construct($data)
    {
        $this->productId = (int) ($data['product_id'] ?? 0);
        $this->sellerId = (int) ($data['seller_id'] ?? 0);
        $this->categoryId = (int) ($data['category_id'] ?? 0);
        $this->title = (string) ($data['title'] ?? '');
        $this->description = (string) ($data['description'] ?? '');
        $this->price = (float) ($data['price'] ?? 0.00);
        $this->stockQuantity = (int) ($data['stock_quantity'] ?? 0);
        $this->condition = (string) ($data['condition'] ?? '');
        $this->location = (string) ($data['location'] ?? '');
        $this->imageUrl = (string) ($data['image_url'] ?? '');
        $this->status = (string) ($data['status'] ?? 'active');
        $this->createdAt = (string) ($data['created_at'] ?? '');
        $this->suspendedBy = isset($data['suspended_by']) ? (string) $data['suspended_by'] : null;
        $this->suspendedReason = isset($data['suspended_reason']) ? (string) $data['suspended_reason'] : null;
    }

    // ========== GETTERS ==========

    public function getProductId()
    {
        return $this->productId;
    }

    public function getSellerId()
    {
        return $this->sellerId;
    }

    public function getCategoryId()
    {
        return $this->categoryId;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function getStockQuantity()
    {
        return $this->stockQuantity;
    }

    public function getCondition()
    {
        return $this->condition;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getSuspendedBy()
    {
        return $this->suspendedBy;
    }

    public function getSuspendedReason()
    {
        return $this->suspendedReason;
    }

    // ========== SETTERS ==========

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function setStockQuantity($stockQuantity)
    {
        $this->stockQuantity = $stockQuantity;
    }

    public function setCondition($condition)
    {
        $this->condition = $condition;
    }

    public function setLocation($location)
    {
        $this->location = $location;
    }

    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
    }

    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = $imageUrl;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setSuspendedBy($suspendedBy)
    {
        $this->suspendedBy = $suspendedBy;
    }

    public function setSuspendedReason($suspendedReason)
    {
        $this->suspendedReason = $suspendedReason;
    }

    // ========== BUSINESS LOGIC ==========

    /**
     * Checks if product is available for purchase.
     * Must be active AND have stock > 0.
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->status === 'active' && $this->stockQuantity > 0;
    }

    /**
     * Checks if product is out of stock.
     *
     * @return bool
     */
    public function isOutOfStock()
    {
        return $this->stockQuantity <= 0;
    }

    /**
     * Checks if product has low stock (5 or fewer units).
     *
     * @return bool
     */
    public function isLowStock()
    {
        return $this->stockQuantity > 0 && $this->stockQuantity <= 5;
    }

    /**
     * Returns formatted price with currency symbol.
     *
     * @return string
     */
    public function getFormattedPrice()
    {
        return 'R ' . number_format($this->price, 2);
    }

    /**
     * Returns CSS class for stock badge based on stock level.
     *
     * @return string
     */
    public function getStockBadgeClass()
    {
        if ($this->isOutOfStock()) return 'out-of-stock';
        if ($this->isLowStock()) return 'low-stock';
        return '';
    }

    /**
     * Returns text for stock badge based on stock level.
     *
     * @return string
     */
    public function getStockBadgeText()
    {
        if ($this->isOutOfStock()) return 'Out of Stock';
        if ($this->isLowStock()) return 'Only ' . $this->stockQuantity . ' left';
        return '';
    }

    /**
     * Returns CSS class for condition badge.
     *
     * @return string
     */
    public function getConditionClass()
    {
        $conditionMap = [
            'new' => 'new',
            'like new' => 'like-new',
            'good' => 'good',
            'fair' => 'fair'
        ];
        return $conditionMap[strtolower($this->condition)] ?? '';
    }

    /**
     * Checks if requested quantity can be deducted from stock.
     *
     * @param int $quantity Requested quantity
     * @return bool
     */
    public function canDecreaseStock($quantity)
    {
        return $this->stockQuantity >= $quantity;
    }

    /**
     * Decreases stock quantity.
     * Caller should check canDecreaseStock() first.
     *
     * @param int $quantity Quantity to deduct
     * @return void
     */
    public function decreaseStock($quantity)
    {
        if ($this->canDecreaseStock($quantity)) {
            $this->stockQuantity -= $quantity;
        }
    }

    /**
     * Increases stock quantity (for cancellations/restocks).
     *
     * @param int $quantity Quantity to add
     * @return void
     */
    public function increaseStock($quantity)
    {
        $this->stockQuantity += $quantity;
    }

    /**
     * Checks if seller can activate this product.
     * Seller can only activate if product wasn't suspended by admin.
     *
     * @return bool
     */
    public function canSellerActivate()
    {
        return $this->suspendedBy !== 'admin';
    }

    /**
     * Checks if product was suspended by admin.
     *
     * @return bool
     */
    public function isAdminSuspended()
    {
        return $this->status === 'suspended' && $this->suspendedBy === 'admin';
    }

    /**
     * Exports product data as array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'product_id' => $this->productId,
            'seller_id' => $this->sellerId,
            'category_id' => $this->categoryId,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'formatted_price' => $this->getFormattedPrice(),
            'stock_quantity' => $this->stockQuantity,
            'condition' => $this->condition,
            'location' => $this->location,
            'image_url' => $this->imageUrl,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'suspended_by' => $this->suspendedBy,
            'suspended_reason' => $this->suspendedReason,
            'is_available' => $this->isAvailable(),
            'stock_badge_class' => $this->getStockBadgeClass(),
            'stock_badge_text' => $this->getStockBadgeText(),
            'condition_class' => $this->getConditionClass(),
            'can_seller_activate' => $this->canSellerActivate(),
            'is_admin_suspended' => $this->isAdminSuspended()
        ];
    }
}
