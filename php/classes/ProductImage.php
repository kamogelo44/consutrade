<?php
/**
 * ConsuTrade - ProductImage
 *
 * Domain class representing a gallery image for a product listing.
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

class ProductImage
{
    /** @var int */
    private $imageId;

    /** @var int */
    private $productId;

    /** @var string */
    private $imageUrl;

    /** @var bool */
    private $isPrimary;

    /** @var int */
    private $sortOrder;

    /**
     * Constructor.
     *
     * @param array $data Associative array of image data from the database
     */
    public function __construct(array $data)
    {
        $this->imageId   = (int) ($data['image_id']   ?? 0);
        $this->productId = (int) ($data['product_id'] ?? 0);
        $this->imageUrl  = (string) ($data['image_url'] ?? '');
        $this->isPrimary = (bool) ($data['is_primary'] ?? false);
        $this->sortOrder = (int) ($data['sort_order'] ?? 0);
    }

    /**
     * Returns the image ID.
     *
     * @return int
     */
    public function getImageId(): int
    {
        return $this->imageId;
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
     * Checks whether this image is the primary display image.
     *
     * @return bool
     */
    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }
}