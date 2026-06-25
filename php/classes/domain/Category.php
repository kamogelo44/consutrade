<?php

/**
 * ConsuTrade - Category
 *
 * Domain class representing a product category.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */

class Category
{
    /** @var int */
    private $categoryId;

    /** @var string */
    private $categoryName;

    /**
     * Constructor.
     *
     * @param array $data Associative array of category data from the database
     */
    public function __construct($data)
    {
        $this->categoryId   = (int) ($data['category_id']   ?? 0);
        $this->categoryName = (string) ($data['category_name'] ?? '');
    }

    /**
     * Returns the category ID.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * Returns the category name.
     *
     * @return string
     */
    public function getCategoryName()
    {
        return $this->categoryName;
    }
}
