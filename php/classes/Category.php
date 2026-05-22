<?php
/**
 * ConsuTrade - Category
 *
 * Domain class representing a product category.
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
    public function __construct(array $data)
    {
        $this->categoryId   = (int) ($data['category_id']   ?? 0);
        $this->categoryName = (string) ($data['category_name'] ?? '');
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
     * Returns the category name.
     *
     * @return string
     */
    public function getCategoryName(): string
    {
        return $this->categoryName;
    }
}