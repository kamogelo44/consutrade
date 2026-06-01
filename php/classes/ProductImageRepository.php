<?php

/**
 * ConsuTrade - ProductImageRepository
 *
 * Handles all product image database operations.
 *
 * @author     Kamogelo Phale
 * @module     ITECA3-12 Web Development and e-Commerce
 * @institution Eduvos
 * @version    2.0.0
 * @since      2026
 */

class ProductImageRepository
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

    /**
     * Helper to get full file path for an image
     *
     * @param string $imageUrl Relative image path
     * @return string Full system path
     */
    private function getFullPath(string $imageUrl): string
    {
        // Try multiple possible base paths
        $basePaths = [
            $_SERVER['DOCUMENT_ROOT'] . '/',
            $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/',
            dirname(__DIR__, 2) . '/',
            __DIR__ . '/../../',
        ];

        foreach ($basePaths as $basePath) {
            $fullPath = rtrim($basePath, '/') . '/' . ltrim($imageUrl, '/');
            if (file_exists(dirname($fullPath))) {
                return $fullPath;
            }
        }

        // Fallback
        return $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($imageUrl, '/');
    }

    // ============================================================
    //  RETRIEVE
    // ============================================================

    /**
     * Get all images for a product.
     *
     * @param int $productId Product ID
     * @return array
     */
    public function getByProductId(int $productId): array
    {
        $sql = "SELECT image_id, image_url, is_primary, sort_order 
                FROM product_images 
                WHERE product_id = ? 
                ORDER BY sort_order ASC, image_id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        $images = [];
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
        $stmt->close();
        return $images;
    }

    /**
     * Get primary image for a product.
     *
     * @param int $productId Product ID
     * @return array|null
     */
    public function getPrimaryImage(int $productId): ?array
    {
        $sql = "SELECT image_id, image_url, is_primary, sort_order 
                FROM product_images 
                WHERE product_id = ? AND is_primary = 1 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row;
        }
        $stmt->close();
        return null;
    }

    /**
     * Get image by ID.
     *
     * @param int $imageId Image ID
     * @return array|null
     */
    public function getById(int $imageId): ?array
    {
        $sql = "SELECT image_id, product_id, image_url, is_primary, sort_order 
                FROM product_images 
                WHERE image_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $imageId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row;
        }
        $stmt->close();
        return null;
    }

    // ============================================================
    //  CREATE
    // ============================================================

    /**
     * Add a gallery image to a product.
     *
     * @param int $productId Product ID
     * @param string $imageUrl Image path
     * @param bool $isPrimary Whether this is the primary image
     * @param int $sortOrder Sort order
     * @return int|false Insert ID or false on failure
     */
    public function add(int $productId, string $imageUrl, bool $isPrimary = false, int $sortOrder = 0)
    {
        $primaryInt = $isPrimary ? 1 : 0;
        $stmt = $this->db->prepare(
            "INSERT INTO product_images (product_id, image_url, is_primary, sort_order) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('isii', $productId, $imageUrl, $primaryInt, $sortOrder);

        if ($stmt->execute()) {
            $imageId = $stmt->insert_id;
            $stmt->close();
            return $imageId;
        }
        $stmt->close();
        return false;
    }

    /**
     * Add multiple gallery images to a product.
     *
     * @param int $productId Product ID
     * @param array $imageUrls Array of image paths
     * @return int Number of images successfully added
     */
    public function addMultiple(int $productId, array $imageUrls): int
    {
        $count = 0;

        $maxSql = "SELECT MAX(sort_order) as max_sort FROM product_images WHERE product_id = ?";
        $maxStmt = $this->db->prepare($maxSql);
        $maxStmt->bind_param('i', $productId);
        $maxStmt->execute();
        $maxResult = $maxStmt->get_result();
        $maxRow = $maxResult->fetch_assoc();
        $sortOrder = (int)($maxRow['max_sort'] ?? -1) + 1;
        $maxStmt->close();

        foreach ($imageUrls as $imageUrl) {
            $imageId = $this->add($productId, $imageUrl, false, $sortOrder);
            if ($imageId) {
                $count++;
                $sortOrder++;
            }
        }

        return $count;
    }

    // ============================================================
    //  UPDATE
    // ============================================================

    /**
     * Set an image as the primary image for a product.
     *
     * @param int $productId Product ID
     * @param int $imageId Image ID to set as primary
     * @return bool
     */
    public function setPrimary(int $productId, int $imageId): bool
    {
        $clearStmt = $this->db->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
        $clearStmt->bind_param('i', $productId);
        $clearStmt->execute();
        $clearStmt->close();

        $setStmt = $this->db->prepare("UPDATE product_images SET is_primary = 1 WHERE image_id = ? AND product_id = ?");
        $setStmt->bind_param('ii', $imageId, $productId);
        $result = $setStmt->execute();
        $setStmt->close();

        return $result;
    }

    /**
     * Update sort order of an image.
     *
     * @param int $imageId Image ID
     * @param int $sortOrder New sort order
     * @return bool
     */
    public function updateSortOrder(int $imageId, int $sortOrder): bool
    {
        $stmt = $this->db->prepare("UPDATE product_images SET sort_order = ? WHERE image_id = ?");
        $stmt->bind_param('ii', $sortOrder, $imageId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ============================================================
    //  DELETE
    // ============================================================

    /**
     * Delete an image.
     *
     * @param int $imageId Image ID
     * @param int $productId Product ID (for verification)
     * @return bool
     */
    public function delete(int $imageId, int $productId): bool
    {
        $image = $this->getById($imageId);
        if ($image && $image['product_id'] == $productId) {
            $fullPath = $this->getFullPath($image['image_url']);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $stmt = $this->db->prepare("DELETE FROM product_images WHERE image_id = ? AND product_id = ?");
        $stmt->bind_param('ii', $imageId, $productId);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Delete all images for a product.
     *
     * @param int $productId Product ID
     * @return bool
     */
    public function deleteByProductId(int $productId): bool
    {
        $images = $this->getByProductId($productId);
        foreach ($images as $image) {
            $fullPath = $this->getFullPath($image['image_url']);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $stmt = $this->db->prepare("DELETE FROM product_images WHERE product_id = ?");
        $stmt->bind_param('i', $productId);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }
}
