<?php

/**
 * ConsuTrade - ProductImage
 *
 * Domain class representing a gallery image for a product listing.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */

class ProductImage
{
    private $imageId;
    private $productId;
    private $imageUrl;
    private $isPrimary;
    private $sortOrder;

    /**
     * Constructor.
     *
     * @param array $data Associative array of image data from the database
     */
    public function __construct($data)
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
    public function getImageId()
    {
        return $this->imageId;
    }

    /**
     * Returns the image URL.
     *
     * @return string
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * Checks whether this image is the primary display image.
     *
     * @return bool
     */
    public function isPrimary()
    {
        return $this->isPrimary;
    }
}
