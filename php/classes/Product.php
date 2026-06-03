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
    /** @var int Product ID */
    private int $productId;

    /** @var int Seller user ID */
    private int $sellerId;

    /** @var int Category ID */
    private int $categoryId;

    /** @var string Product title */
    private string $title;

    /** @var string Product description */
    private string $description;

    /** @var float Product price */
    private float $price;

    /** @var int Available stock quantity */
    private int $stockQuantity;

    /** @var string Product condition (New, Like New, Good, Fair) */
    private string $condition;

    /** @var string Product location */
    private string $location;

    /** @var string Product image URL */
    private string $imageUrl;

    /** @var string Product status (active, suspended, deleted) */
    private string $status;

    /** @var string Product creation date */
    private string $createdAt;

    /** @var string|null Who suspended the product (seller or admin) */
    private ?string $suspendedBy;

    /** @var string|null Reason for suspension */
    private ?string $suspendedReason;

    /**
     * Product constructor.
     *
     * @param array $data Product data from database
     */
    public function __construct(array $data)
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

    /**
     * Returns the product ID.
     *
     * @return int
     */
    public function getProductId(): int
    {
        return $this->productId;
    }

    /**
     * Returns the seller user ID.
     *
     * @return int
     */
    public function getSellerId(): int
    {
        return $this->sellerId;
    }

    /**
     * Returns the category ID.
     *
     * @return int
     */
    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    /**
     * Returns the product title.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Returns the product description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Returns the product price.
     *
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * Returns the available stock quantity.
     *
     * @return int
     */
    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    /**
     * Returns the product condition.
     *
     * @return string
     */
    public function getCondition(): string
    {
        return $this->condition;
    }

    /**
     * Returns the product location.
     *
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * Returns the product image URL.
     *
     * @return string
     */
    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    /**
     * Returns the product status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Returns the product creation date.
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Returns who suspended the product (seller or admin).
     *
     * @return string|null
     */
    public function getSuspendedBy(): ?string
    {
        return $this->suspendedBy;
    }

    /**
     * Returns the suspension reason.
     *
     * @return string|null
     */
    public function getSuspendedReason(): ?string
    {
        return $this->suspendedReason;
    }

    /**
     * Sets the product title.
     *
     * @param string $title
     * @return void
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Sets the product description.
     *
     * @param string $description
     * @return void
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Sets the product price.
     *
     * @param float $price
     * @return void
     */
    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    /**
     * Sets the stock quantity.
     *
     * @param int $stockQuantity
     * @return void
     */
    public function setStockQuantity(int $stockQuantity): void
    {
        $this->stockQuantity = $stockQuantity;
    }

    /**
     * Sets the product condition.
     *
     * @param string $condition
     * @return void
     */
    public function setCondition(string $condition): void
    {
        $this->condition = $condition;
    }

    /**
     * Sets the product location.
     *
     * @param string $location
     * @return void
     */
    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    /**
     * Sets the category ID.
     *
     * @param int $categoryId
     * @return void
     */
    public function setCategoryId(int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    /**
     * Sets the product image URL.
     *
     * @param string $imageUrl
     * @return void
     */
    public function setImageUrl(string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    /**
     * Sets the product status.
     *
     * @param string $status
     * @return void
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * Sets who suspended the product.
     *
     * @param string|null $suspendedBy
     * @return void
     */
    public function setSuspendedBy(?string $suspendedBy): void
    {
        $this->suspendedBy = $suspendedBy;
    }

    /**
     * Sets the suspension reason.
     *
     * @param string|null $suspendedReason
     * @return void
     */
    public function setSuspendedReason(?string $suspendedReason): void
    {
        $this->suspendedReason = $suspendedReason;
    }

    /**
     * Checks if product is available for purchase.
     * Business rule: Must be active AND have stock > 0.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->status === 'active' && $this->stockQuantity > 0;
    }

    /**
     * Checks if product is out of stock.
     *
     * @return bool
     */
    public function isOutOfStock(): bool
    {
        return $this->stockQuantity <= 0;
    }

    /**
     * Checks if product has low stock (5 or fewer units).
     *
     * @return bool
     */
    public function isLowStock(): bool
    {
        return $this->stockQuantity > 0 && $this->stockQuantity <= 5;
    }

    /**
     * Returns formatted price with currency symbol.
     *
     * @return string
     */
    public function getFormattedPrice(): string
    {
        return 'R ' . number_format($this->price, 2);
    }

    /**
     * Returns CSS class for stock badge based on stock level.
     *
     * @return string
     */
    public function getStockBadgeClass(): string
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
    public function getStockBadgeText(): string
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
    public function getConditionClass(): string
    {
        return match (strtolower($this->condition)) {
            'new' => 'new',
            'like new' => 'like-new',
            'good' => 'good',
            'fair' => 'fair',
            default => ''
        };
    }

    /**
     * Checks if requested quantity can be deducted from stock.
     *
     * @param int $quantity Requested quantity
     * @return bool
     */
    public function canDecreaseStock(int $quantity): bool
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
    public function decreaseStock(int $quantity): void
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
    public function increaseStock(int $quantity): void
    {
        $this->stockQuantity += $quantity;
    }

    /**
     * Checks if seller can activate this product.
     * Seller can only activate if product was suspended by themselves, not by admin.
     *
     * @return bool
     */
    public function canSellerActivate(): bool
    {
        return $this->suspendedBy !== 'admin';
    }

    /**
     * Checks if product was suspended by admin.
     *
     * @return bool
     */
    public function isAdminSuspended(): bool
    {
        return $this->status === 'suspended' && $this->suspendedBy === 'admin';
    }

    /**
     * Exports product data as array for API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
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
