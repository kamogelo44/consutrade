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
    private int $productId;
    private int $sellerId;
    private int $categoryId;
    private string $title;
    private string $description;
    private float $price;
    private int $stockQuantity;
    private string $condition;
    private string $location;
    private string $imageUrl;
    private string $status;
    private string $createdAt;

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
    }

    public function getProductId(): int
    {
        return $this->productId;
    }
    public function getSellerId(): int
    {
        return $this->sellerId;
    }
    public function getCategoryId(): int
    {
        return $this->categoryId;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function getDescription(): string
    {
        return $this->description;
    }
    public function getPrice(): float
    {
        return $this->price;
    }
    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }
    public function getCondition(): string
    {
        return $this->condition;
    }
    public function getLocation(): string
    {
        return $this->location;
    }
    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
    public function setPrice(float $price): void
    {
        $this->price = $price;
    }
    public function setStockQuantity(int $stockQuantity): void
    {
        $this->stockQuantity = $stockQuantity;
    }
    public function setCondition(string $condition): void
    {
        $this->condition = $condition;
    }
    public function setLocation(string $location): void
    {
        $this->location = $location;
    }
    public function setCategoryId(int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }
    public function setImageUrl(string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * Checks if product is available for purchase
     * Business rule: Must be active AND have stock > 0
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->status === 'active' && $this->stockQuantity > 0;
    }

    public function isOutOfStock(): bool
    {
        return $this->stockQuantity <= 0;
    }

    public function isLowStock(): bool
    {
        return $this->stockQuantity > 0 && $this->stockQuantity <= 5;
    }

    /**
     * Returns formatted price with currency symbol
     *
     * @return string
     */
    public function getFormattedPrice(): string
    {
        return 'R ' . number_format($this->price, 2);
    }

    public function getStockBadgeClass(): string
    {
        if ($this->isOutOfStock()) return 'out-of-stock';
        if ($this->isLowStock()) return 'low-stock';
        return '';
    }

    public function getStockBadgeText(): string
    {
        if ($this->isOutOfStock()) return 'Out of Stock';
        if ($this->isLowStock()) return 'Only ' . $this->stockQuantity . ' left';
        return '';
    }

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
     * Checks if requested quantity can be deducted from stock
     *
     * @param int $quantity Requested quantity
     * @return bool
     */
    public function canDecreaseStock(int $quantity): bool
    {
        return $this->stockQuantity >= $quantity;
    }

    /**
     * Decreases stock quantity (caller should check canDecreaseStock first)
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
     * Increases stock quantity (for cancellations/restocks)
     *
     * @param int $quantity Quantity to add
     * @return void
     */
    public function increaseStock(int $quantity): void
    {
        $this->stockQuantity += $quantity;
    }

    /**
     * Export product data as array for API responses
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
            'is_available' => $this->isAvailable(),
            'stock_badge_class' => $this->getStockBadgeClass(),
            'stock_badge_text' => $this->getStockBadgeText(),
            'condition_class' => $this->getConditionClass()
        ];
    }
}
