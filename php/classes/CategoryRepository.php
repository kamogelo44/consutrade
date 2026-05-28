<?php
/**
 * ConsuTrade - CategoryRepository
 *
 * Handles all categories table queries.
 * The categories table only has category_id and category_name columns.
 *
 * @author     Kamogelo Phale
 * @module     ITECA3-12 Web Development and e-Commerce
 * @institution Eduvos
 * @version    2.0.0
 * @since      2026
 */

class CategoryRepository
{
    /** @var mysqli Database connection */
    private $db;

    /**
     * Constructor.
     *
     * @param mysqli $db Database connection
     */
    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    // ============================================================
    //  RETRIEVE
    // ============================================================

    /**
     * Get all categories.
     *
     * @return array Array of categories
     */
    public function getAll(): array
    {
        $sql = "SELECT category_id, category_name FROM categories ORDER BY category_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'id' => (int) $row['category_id'],
                'name' => $row['category_name']
            ];
        }
        $stmt->close();

        return $categories;
    }

    /**
     * Get category by ID.
     *
     * @param int $categoryId Category ID
     * @return array|null Category data or null if not found
     */
    public function getById(int $categoryId): ?array
    {
        $sql = "SELECT category_id, category_name FROM categories WHERE category_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return [
                'id' => (int) $row['category_id'],
                'name' => $row['category_name']
            ];
        }

        $stmt->close();
        return null;
    }

    /**
     * Get category by name.
     *
     * @param string $name Category name
     * @return array|null Category data or null if not found
     */
    public function getByName(string $name): ?array
    {
        $sql = "SELECT category_id, category_name FROM categories WHERE category_name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return [
                'id' => (int) $row['category_id'],
                'name' => $row['category_name']
            ];
        }

        $stmt->close();
        return null;
    }

    /**
     * Get category name by ID (convenience method).
     *
     * @param int $categoryId Category ID
     * @return string|null Category name or null if not found
     */
    public function getCategoryName(int $categoryId): ?string
    {
        $category = $this->getById($categoryId);
        return $category ? $category['name'] : null;
    }

    // ============================================================
    //  CREATE, UPDATE, DELETE
    // ============================================================

    /**
     * Create a new category.
     *
     * @param string $categoryName Category name
     * @return bool True on success, false on failure
     */
    public function create(string $categoryName): bool
    {
        $stmt = $this->db->prepare("INSERT INTO categories (category_name) VALUES (?)");
        $stmt->bind_param('s', $categoryName);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Update a category.
     *
     * @param int $categoryId Category ID
     * @param string $name New category name
     * @return bool True on success, false on failure
     */
    public function update(int $categoryId, string $name): bool
    {
        $stmt = $this->db->prepare("UPDATE categories SET category_name = ? WHERE category_id = ?");
        $stmt->bind_param('si', $name, $categoryId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Delete a category.
     *
     * @param int $categoryId Category ID
     * @return bool True on success, false on failure
     */
    public function delete(int $categoryId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->bind_param('i', $categoryId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ============================================================
    //  UTILITY
    // ============================================================

    /**
     * Get total number of categories.
     *
     * @return int
     */
    public function getTotalCategories(): int
    {
        $sql = "SELECT COUNT(*) as total FROM categories";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = (int) ($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        return $total;
    }
}