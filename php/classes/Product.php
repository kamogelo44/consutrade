<?php
/**
 * ConsuTrade - Product
 *
 * Domain class representing a single product listing.
 *
 * @author     Kamogelo Phale
 * @module     ITECA3-12 Web Development and e-Commerce
 * @institution Eduvos
 * @version    2.0.0
 * @since      2026
 */

class Product
{
    /** @var int */
    private $productId;

    /** @var int */
    private $sellerId;

    /** @var int */
    private $categoryId;

    /** @var string */
    private $title;

    /** @var string */
    private $description;

    /** @var float */
    private $price;

    /** @var int */
    private $stockQuantity;

    /** @var string */
    private $condition;

    /** @var string */
    private $location;

    /** @var string */
    private $imageUrl;

    /** @var string */
    private $status;

    /** @var string */
    private $createdAt;

    /**
     * Constructor.
     *
     * @param array $data Associative array of product data from the database
     */
    public function __construct(array $data)
    {
        $this->productId     = (int) ($data['product_id']     ?? 0);
        $this->sellerId      = (int) ($data['seller_id']      ?? 0);
        $this->categoryId    = (int) ($data['category_id']    ?? 0);
        $this->title         = (string) ($data['title']        ?? '');
        $this->description   = (string) ($data['description']  ?? '');
        $this->price         = (float) ($data['price']         ?? 0.00);
        $this->stockQuantity = (int) ($data['stock_quantity']  ?? 0);
        $this->condition     = (string) ($data['condition']    ?? '');
        $this->location      = (string) ($data['location']     ?? '');
        $this->imageUrl      = (string) ($data['image_url']    ?? '');
        $this->status        = (string) ($data['status']       ?? 'active');
        $this->createdAt     = (string) ($data['created_at']   ?? '');
    }

    // ============================================================
    //  GETTERS
    // ============================================================

    public function getProductId(): int { return $this->productId; }
    public function getSellerId(): int { return $this->sellerId; }
    public function getCategoryId(): int { return $this->categoryId; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): string { return $this->description; }
    public function getPrice(): float { return $this->price; }
    public function getStockQuantity(): int { return $this->stockQuantity; }
    public function getCondition(): string { return $this->condition; }
    public function getLocation(): string { return $this->location; }
    public function getImageUrl(): string { return $this->imageUrl; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): string { return $this->createdAt; }

    // ============================================================
    //  SETTERS (for updates)
    // ============================================================

    public function setTitle(string $title): void { $this->title = $title; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setPrice(float $price): void { $this->price = $price; }
    public function setStockQuantity(int $stockQuantity): void { $this->stockQuantity = $stockQuantity; }
    public function setCondition(string $condition): void { $this->condition = $condition; }
    public function setLocation(string $location): void { $this->location = $location; }
    public function setCategoryId(int $categoryId): void { $this->categoryId = $categoryId; }
    public function setImageUrl(string $imageUrl): void { $this->imageUrl = $imageUrl; }
    public function setStatus(string $status): void { $this->status = $status; }

    // ============================================================
    //  BUSINESS LOGIC METHODS
    // ============================================================

    /**
     * Checks whether the product is available for purchase.
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
     * Checks if product has low stock (5 or less).
     *
     * @return bool
     */
    public function isLowStock(): bool
    {
        return $this->stockQuantity > 0 && $this->stockQuantity <= 5;
    }

    /**
     * Returns formatted price with currency.
     *
     * @return string
     */
    public function getFormattedPrice(): string
    {
        return 'R ' . number_format($this->price, 2);
    }

    /**
     * Returns stock badge class for UI.
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
     * Returns stock badge text for UI.
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
     * Returns condition badge class.
     *
     * @return string
     */
    public function getConditionClass(): string
    {
        switch (strtolower($this->condition)) {
            case 'new': return 'new';
            case 'like new': return 'like-new';
            case 'good': return 'good';
            case 'fair': return 'fair';
            default: return '';
        }
    }

    /**
     * Returns all product data as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'product_id'        => $this->productId,
            'seller_id'         => $this->sellerId,
            'category_id'       => $this->categoryId,
            'title'             => $this->title,
            'description'       => $this->description,
            'price'             => $this->price,
            'stock_quantity'    => $this->stockQuantity,
            'condition'         => $this->condition,
            'location'          => $this->location,
            'image_url'         => $this->imageUrl,
            'status'            => $this->status,
            'created_at'        => $this->createdAt,
            'formatted_price'   => $this->getFormattedPrice(),
            'is_available'      => $this->isAvailable(),
            'stock_badge_class' => $this->getStockBadgeClass(),
            'stock_badge_text'  => $this->getStockBadgeText(),
            'condition_class'   => $this->getConditionClass()
        ];
    }
}