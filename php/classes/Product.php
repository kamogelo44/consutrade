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
     * Returns the product title.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
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
     * Returns the product status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Checks whether the product is available for purchase.
     * Must be active and have stock.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->status === 'active' && $this->stockQuantity > 0;
    }

    // ============================================================
    //  ADDITIONAL GETTERS (not in diagram but useful)
    // ============================================================

    /**
     * Returns the seller ID.
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
     * Returns the product description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Returns the current stock quantity.
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
     * Returns the image URL.
     *
     * @return string
     */
    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    /**
     * Returns the creation date.
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Returns all product data as an array (useful for templates).
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'product_id'     => $this->productId,
            'seller_id'      => $this->sellerId,
            'category_id'    => $this->categoryId,
            'title'          => $this->title,
            'description'    => $this->description,
            'price'          => $this->price,
            'stock_quantity' => $this->stockQuantity,
            'condition'      => $this->condition,
            'location'       => $this->location,
            'image_url'      => $this->imageUrl,
            'status'         => $this->status,
            'created_at'     => $this->createdAt,
        ];
    }
}