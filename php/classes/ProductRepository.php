<?php
/**
 * ConsuTrade - ProductRepository
 *
 * Handles all product, product image, and gallery database operations.
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

class ProductRepository
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
    //  PRODUCT QUERIES (SELLER)
    // ============================================================

    /**
     * Get seller products with filters (includes primary image from product_images).
     *
     * @param int    $id      Seller ID
     * @param string $filter  Status filter: 'all', 'active', 'suspended'
     * @param string $search  Search by product name or ID
     * @param int    $limit   Maximum products to return (0 = all)
     * @param int    $offset  Pagination offset (default 0)
     * @return array
     */
    public function getSellerProducts(
        int $id,
        string $filter = 'all',
        string $search = '',
        int $limit = 0,
        int $offset = 0
    ): array {
        $sql = "SELECT p.product_id, p.title, p.price, p.image_url, p.status,
                       p.stock_quantity, p.created_at,
                       c.category_name,
                       COALESCE(pi.image_url, p.image_url) AS display_image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                WHERE p.seller_id = ? AND p.status != 'deleted'";

        $params = [$id];
        $types  = "i";

        if ($filter !== 'all') {
            $sql    .= " AND p.status = ?";
            $params[] = $filter;
            $types  .= "s";
        }

        if (!empty($search)) {
            $sql       .= " AND (p.title LIKE ? OR p.product_id LIKE ?)";
            $searchParam = "%$search%";
            $params[]  = $searchParam;
            $params[]  = $searchParam;
            $types    .= "ss";
        }

        $sql .= " ORDER BY p.created_at DESC";

        if ($limit > 0) {
            $sql    .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types  .= "ii";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = [
                'id'             => (int) $row['product_id'],
                'title'          => $row['title'],
                'price'          => (float) $row['price'],
                'image'          => $row['image_url'],
                'display_image'  => $row['display_image'],
                'status'         => $row['status'],
                'stock_quantity' => (int) ($row['stock_quantity'] ?? 1),
                'created_at'     => $row['created_at'],
                'category_name'  => $row['category_name'] ?? 'General'
            ];
        }
        $stmt->close();

        return $products;
    }

    /**
     * Get single product for editing (with ownership verification).
     *
     * @param int $productId Product ID
     * @param int $sellerId  Seller ID (for verification)
     * @return array|null
     */
    public function getProductForEdit(int $productId, int $sellerId): ?array
    {
        $sql = "SELECT p.product_id, p.title, p.description, p.price, p.stock_quantity,
                       p.`condition`, p.location, p.category_id, p.image_url, p.status
                FROM products p
                WHERE p.product_id = ? AND p.seller_id = ? AND p.status != 'deleted'";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $productId, $sellerId);
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
     * Update product information.
     *
     * @param int   $id       Product ID
     * @param int   $sellerId Seller ID (for verification)
     * @param array $data     Product data
     * @return array          ['success' => bool, 'message' => string]
     */
    public function updateSellerProduct(int $id, int $sellerId, array $data): array
    {
        $sql = "UPDATE products SET
                    title = ?,
                    description = ?,
                    price = ?,
                    stock_quantity = ?,
                    `condition` = ?,
                    location = ?,
                    category_id = ?
                WHERE product_id = ? AND seller_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'ssdissiii',
            $data['title'],
            $data['description'],
            $data['price'],
            $data['stock_quantity'],
            $data['condition'],
            $data['location'],
            $data['category_id'],
            $id,
            $sellerId
        );

        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Product updated.'];
        }

        $stmt->close();
        return ['success' => false, 'message' => 'Failed to update product.'];
    }

    /**
     * Update product status (activate/suspend).
     *
     * @param int    $id       Product ID
     * @param int    $sellerId Seller ID (for verification)
     * @param string $action   'activate' or 'suspend'
     * @return array           ['success' => bool, 'message' => string]
     */
    public function updateProductStatus(int $id, int $sellerId, string $action): array
    {
        $checkSql = "SELECT product_id, status FROM products
                     WHERE product_id = ? AND seller_id = ? AND status != 'deleted'";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bind_param('ii', $id, $sellerId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            return ['success' => false, 'message' => 'Product not found.'];
        }

        $product = $checkResult->fetch_assoc();
        $checkStmt->close();

        $newStatus = ($action === 'activate') ? 'active' : 'suspended';

        if ($product['status'] === $newStatus) {
            $statusText = ($action === 'activate') ? 'active' : 'suspended';
            return ['success' => false, 'message' => "Product is already $statusText."];
        }

        $updateSql = "UPDATE products SET status = ? WHERE product_id = ? AND seller_id = ?";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->bind_param('sii', $newStatus, $id, $sellerId);

        if ($updateStmt->execute()) {
            $updateStmt->close();
            $message = ($action === 'activate') ? 'Product activated.' : 'Product suspended.';
            return ['success' => true, 'message' => $message];
        }

        $updateStmt->close();
        return ['success' => false, 'message' => 'Failed to update product status.'];
    }

    /**
     * Delete product (soft delete — sets status to 'deleted').
     * Also removes gallery images from disk and database.
     *
     * @param int $id       Product ID
     * @param int $sellerId Seller ID (for verification)
     * @return array        ['success' => bool, 'message' => string]
     */
    public function deleteSellerProduct(int $id, int $sellerId): array
    {
        $checkSql = "SELECT product_id, image_url FROM products
                     WHERE product_id = ? AND seller_id = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bind_param('ii', $id, $sellerId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            return ['success' => false, 'message' => 'Product not found.'];
        }

        $product = $checkResult->fetch_assoc();
        $checkStmt->close();

        // Delete gallery image files
        $gallerySql = "SELECT image_url FROM product_images WHERE product_id = ?";
        $galleryStmt = $this->db->prepare($gallerySql);
        $galleryStmt->bind_param('i', $id);
        $galleryStmt->execute();
        $galleryResult = $galleryStmt->get_result();
        while ($img = $galleryResult->fetch_assoc()) {
            $this->deleteProductImage($img['image_url']);
        }
        $galleryStmt->close();

        // Delete gallery records
        $delGallery = $this->db->prepare("DELETE FROM product_images WHERE product_id = ?");
        $delGallery->bind_param('i', $id);
        $delGallery->execute();
        $delGallery->close();

        // Soft delete the product
        $deleteSql = "UPDATE products SET status = 'deleted' WHERE product_id = ? AND seller_id = ?";
        $deleteStmt = $this->db->prepare($deleteSql);
        $deleteStmt->bind_param('ii', $id, $sellerId);

        if ($deleteStmt->execute()) {
            $deleteStmt->close();

            if (!empty($product['image_url'])) {
                $this->deleteProductImage($product['image_url']);
            }

            return ['success' => true, 'message' => 'Product deleted.'];
        }

        $deleteStmt->close();
        return ['success' => false, 'message' => 'Failed to delete product.'];
    }

    // ============================================================
    //  STOCK MANAGEMENT
    // ============================================================

    /**
     * Update product stock quantity (add or subtract).
     *
     * @param int $productId Product ID
     * @param int $qty       Quantity to add (negative to subtract)
     * @return bool
     */
    public function updateStock(int $productId, int $qty): bool
    {
        if ($qty >= 0) {
            $stmt = $this->db->prepare(
                "UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?"
            );
            $stmt->bind_param('ii', $qty, $productId);
        } else {
            $positive = abs($qty);
            $stmt = $this->db->prepare(
                "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?"
            );
            $stmt->bind_param('iii', $positive, $productId, $positive);
        }

        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Decrease product stock after purchase.
     *
     * @param int $productId Product ID
     * @param int $qty       Quantity ordered
     * @return bool
     */
    public function decreaseProductStock(int $productId, int $qty): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE products SET stock_quantity = stock_quantity - ?
             WHERE product_id = ? AND stock_quantity >= ?"
        );
        $stmt->bind_param('iii', $qty, $productId, $qty);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Restore stock when an order is cancelled.
     *
     * @param int $orderId Order ID
     * @return bool
     */
    public function restoreOrderStock(int $orderId): bool
    {
        $itemsSql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $itemsStmt = $this->db->prepare($itemsSql);
        $itemsStmt->bind_param('i', $orderId);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();

        $success = true;
        while ($item = $itemsResult->fetch_assoc()) {
            $stockSql = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?";
            $stockStmt = $this->db->prepare($stockSql);
            $stockStmt->bind_param('ii', $item['quantity'], $item['product_id']);
            if (!$stockStmt->execute()) {
                $success = false;
            }
            $stockStmt->close();
        }
        $itemsStmt->close();

        return $success;
    }

    /**
     * Get product stock quantity.
     *
     * @param int $productId Product ID
     * @return int
     */
    public function getProductStock(int $productId): int
    {
        $stmt = $this->db->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stock = (int) ($row['stock_quantity'] ?? 0);
        $stmt->close();
        return $stock;
    }

    // ============================================================
    //  GALLERY IMAGES (product_images table)
    // ============================================================

    /**
     * Get all gallery images for a product.
     *
     * @param int $productId Product ID
     * @return array
     */
    public function getProductGallery(int $productId): array
    {
        $stmt = $this->db->prepare(
            "SELECT image_id, image_url, is_primary, sort_order
             FROM product_images
             WHERE product_id = ?
             ORDER BY sort_order ASC, image_id ASC"
        );
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
     * Get primary image URL for a product.
     *
     * @param int $productId Product ID
     * @return string|null
     */
    public function getProductPrimaryImage(int $productId): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT image_url FROM product_images
             WHERE product_id = ? AND is_primary = 1
             LIMIT 1"
        );
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row['image_url'];
        }
        $stmt->close();

        // Fallback to products table
        $stmt2 = $this->db->prepare("SELECT image_url FROM products WHERE product_id = ?");
        $stmt2->bind_param('i', $productId);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $image = ($row2 = $result2->fetch_assoc()) ? $row2['image_url'] : null;
        $stmt2->close();

        return $image;
    }

    /**
     * Get the best available display image for a product.
     *
     * @param int $productId Product ID
     * @return string|null
     */
    public function getProductDisplayImage(int $productId): ?string
    {
        $primary = $this->getProductPrimaryImage($productId);
        if ($primary) {
            return $primary;
        }

        $stmt = $this->db->prepare(
            "SELECT image_url FROM product_images
             WHERE product_id = ?
             ORDER BY sort_order ASC
             LIMIT 1"
        );
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row['image_url'];
        }
        $stmt->close();

        return null;
    }

    /**
     * Add a gallery image to a product.
     *
     * @param int    $id        Product ID
     * @param string $url       Image path
     * @param bool   $isPrimary Whether this is the primary image
     * @param int    $sortOrder Sort order
     * @return bool
     */
    public function addProductGalleryImage(
        int $id,
        string $url,
        bool $isPrimary = false,
        int $sortOrder = 0
    ): bool {
        $primaryInt = $isPrimary ? 1 : 0;
        $stmt = $this->db->prepare(
            "INSERT INTO product_images (product_id, image_url, is_primary, sort_order)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('isii', $id, $url, $primaryInt, $sortOrder);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Add multiple gallery images to a product.
     *
     * @param int   $id   Product ID
     * @param array $urls Array of image paths
     * @return int        Number of images successfully added
     */
    public function addProductGalleryImages(int $id, array $urls): int
    {
        $count = 0;

        $stmt = $this->db->prepare(
            "SELECT MAX(sort_order) as max_sort FROM product_images WHERE product_id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $sortOrder = (int) (($row['max_sort'] ?? -1) + 1);
        $stmt->close();

        foreach ($urls as $url) {
            if ($this->addProductGalleryImage($id, $url, false, $sortOrder)) {
                $count++;
                $sortOrder++;
            }
        }

        return $count;
    }

    /**
     * Remove a gallery image from a product.
     *
     * @param int $imageId   Image ID
     * @param int $productId Product ID (for verification)
     * @return bool
     */
    public function removeProductGalleryImage(int $imageId, int $productId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT image_url FROM product_images WHERE image_id = ? AND product_id = ?"
        );
        $stmt->bind_param('ii', $imageId, $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $this->deleteProductImage($row['image_url']);
        }
        $stmt->close();

        $stmt2 = $this->db->prepare(
            "DELETE FROM product_images WHERE image_id = ? AND product_id = ?"
        );
        $stmt2->bind_param('ii', $imageId, $productId);
        $result = $stmt2->execute();
        $stmt2->close();

        return $result;
    }

    /**
     * Set a gallery image as the primary image for a product.
     *
     * @param int $productId Product ID
     * @param int $imageId   Image ID to set as primary
     * @return bool
     */
    public function setProductPrimaryImage(int $productId, int $imageId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE product_images SET is_primary = 0 WHERE product_id = ?"
        );
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $stmt->close();

        $stmt2 = $this->db->prepare(
            "UPDATE product_images SET is_primary = 1 WHERE image_id = ? AND product_id = ?"
        );
        $stmt2->bind_param('ii', $imageId, $productId);
        $result = $stmt2->execute();
        $stmt2->close();

        return $result;
    }

    // ============================================================
    //  IMAGE FILE OPERATIONS
    // ============================================================

    /**
     * Convert an uploaded image to WebP format.
     *
     * @param array  $file         The uploaded file from $_FILES
     * @param int    $sellerId     The seller's ID
     * @param string $productTitle The product title (used in filename)
     * @param string $prefix       Optional filename prefix
     * @return string|false        Relative path or false on failure
     */
    public function convertToWebP(array $file, int $sellerId, string $productTitle, string $prefix = 'main')
    {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/uploads/products/';

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $timestamp   = time();
        $safeTitle   = preg_replace('/[^a-zA-Z0-9_-]/', '_', $productTitle);
        $safeTitle   = substr($safeTitle, 0, 50);
        $filename    = "{$sellerId}_{$timestamp}_{$prefix}_{$safeTitle}.webp";
        $destination = $uploadDir . $filename;

        $source    = $file['tmp_name'];
        $imageInfo = getimagesize($source);

        if (!$imageInfo) {
            return false;
        }

        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source);
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($source);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($source);
                break;
            default:
                return false;
        }

        if (!$image) {
            return false;
        }

        $origWidth  = imagesx($image);
        $origHeight = imagesy($image);
        $maxDim     = 1200;

        if ($origWidth > $maxDim || $origHeight > $maxDim) {
            $ratio     = min($maxDim / $origWidth, $maxDim / $origHeight);
            $newWidth  = (int) round($origWidth * $ratio);
            $newHeight = (int) round($origHeight * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);

            if ($imageInfo['mime'] === 'image/png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled(
                $resized, $image,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $origWidth, $origHeight
            );
            $image = $resized;
        }

        $success = imagewebp($image, $destination, 80);
        imagedestroy($image);

        if ($success) {
            return 'uploads/products/' . $filename;
        }

        return false;
    }

    /**
     * Delete a product image file from disk.
     *
     * @param string $imagePath Relative path to the image
     * @return bool
     */
    public function deleteProductImage(string $imagePath): bool
    {
        if (empty($imagePath)) {
            return true;
        }

        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $imagePath;

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return true;
    }

    /**
     * Get the full URL for a product image, with fallback to default.
     *
     * @param string $imagePath The stored image path
     * @return string           Full URL to the image
     */
    public function getProductImageUrl(string $imagePath): string
    {
        $baseUrl = getBaseUrl();

        if (empty($imagePath)) {
            return $baseUrl . 'images/default-product.png';
        }

        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $imagePath;
        if (file_exists($fullPath)) {
            return $baseUrl . $imagePath;
        }

        return $baseUrl . 'images/default-product.png';
    }

    /**
     * Get public product listings with filters, sorting, and pagination.
     *
     * @param array $filters  Associative array: categories, price_range, location, sort, limit, offset
     * @return array           ['products' => array, 'total' => int]
     */
    public function getPublicProducts(array $filters = []): array
    {
    $categories  = $filters['categories'] ?? [];
    $priceRange  = $filters['price_range'] ?? '';
    $location    = $filters['location'] ?? '';
    $sort        = $filters['sort'] ?? 'newest';
    $limit       = $filters['limit'] ?? 12;
    $offset      = $filters['offset'] ?? 0;

    $sql = "SELECT p.product_id, p.title as product_name, p.price, p.image_url,
                   p.location, p.condition, p.stock_quantity, p.created_at,
                   COALESCE(pi.image_url, p.image_url) AS display_image,
                   u.full_name as seller_name, u.user_id as seller_id,
                   u.profile_image, u.id_verified as is_verified
            FROM products p
            JOIN users u ON p.seller_id = u.user_id
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
            WHERE p.status = 'active'";

    $params = [];
    $types  = "";

    // Category filter
    if (!empty($categories) && $categories[0] !== '') {
        $placeholders = implode(',', array_fill(0, count($categories), '?'));
        $sql .= " AND p.category_id IN ($placeholders)";
        foreach ($categories as $cat) {
            $params[] = (int) $cat;
            $types  .= "i";
        }
    }

    // Price range filter
    if (!empty($priceRange)) {
        switch ($priceRange) {
            case 'under100':  $sql .= " AND p.price < 100"; break;
            case '100-500':   $sql .= " AND p.price BETWEEN 100 AND 500"; break;
            case '500-1000':  $sql .= " AND p.price BETWEEN 500 AND 1000"; break;
            case 'over1000':  $sql .= " AND p.price > 1000"; break;
        }
    }

    // Location filter
    if (!empty($location)) {
        $sql    .= " AND p.location LIKE ?";
        $params[] = "%$location%";
        $types  .= "s";
    }

    // Get total count (before LIMIT)
    $countSql    = $sql;
    $countParams = $params;
    $countTypes  = $types;

    // Sorting
    switch ($sort) {
        case 'price_low':  $sql .= " ORDER BY p.price ASC"; break;
        case 'price_high': $sql .= " ORDER BY p.price DESC"; break;
        default:           $sql .= " ORDER BY p.created_at DESC";
    }

    // Pagination
    $sql    .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types  .= "ii";

    // Execute main query
    $stmt = $this->db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id'             => (int) $row['product_id'],
            'name'           => $row['product_name'],
            'price'          => (float) $row['price'],
            'image'          => $row['display_image'] ?? $row['image_url'],
            'seller_name'    => $row['seller_name'],
            'seller_id'      => (int) $row['seller_id'],
            'location'       => $row['location'] ?? 'South Africa',
            'condition'      => $row['condition'] ?? 'Good',
            'stock_quantity' => (int) $row['stock_quantity'],
            'is_verified'    => (bool) $row['is_verified'],
            'profile_image'  => $row['profile_image'],
            'created_at'     => $row['created_at']
        ];
    }
    $stmt->close();

    // Execute count query
    $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM ($countSql) as subquery");
    if (!empty($countParams)) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
    $countStmt->execute();
    $total = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $countStmt->close();

    return [
        'products' => $products,
        'total'    => $total
    ];
    }

    /**
     * Search public products with filters, sorting, and pagination.
     *
     * @param string $search  Search term (matches title and description)
     * @param array  $filters Associative array: categories, price_range, location, sort, limit, offset
     * @return array           ['products' => array, 'total' => int]
     */
    public function searchProducts(string $search, array $filters = []): array
    {
        $categories  = $filters['categories'] ?? [];
        $priceRange  = $filters['price_range'] ?? '';
        $location    = $filters['location'] ?? '';
        $sort        = $filters['sort'] ?? 'newest';
        $limit       = $filters['limit'] ?? 12;
        $offset      = $filters['offset'] ?? 0;

        $sql = "SELECT p.product_id, p.title as product_name, p.price, p.image_url,
                    p.location, p.condition, p.stock_quantity, p.created_at,
                    u.full_name as seller_name, u.user_id as seller_id,
                    u.profile_image, u.id_verified as is_verified
                FROM products p
                JOIN users u ON p.seller_id = u.user_id
                WHERE p.status = 'active'
                AND (p.title LIKE ? OR p.description LIKE ?)";

        $params = ["%$search%", "%$search%"];
        $types  = "ss";

        // Category filter
        if (!empty($categories) && $categories[0] !== '') {
            $placeholders = implode(',', array_fill(0, count($categories), '?'));
            $sql .= " AND p.category_id IN ($placeholders)";
            foreach ($categories as $cat) {
                $params[] = (int) $cat;
                $types  .= "i";
            }
        }

        // Price range filter
        if (!empty($priceRange)) {
            switch ($priceRange) {
                case 'under100':  $sql .= " AND p.price < 100"; break;
                case '100-500':   $sql .= " AND p.price BETWEEN 100 AND 500"; break;
                case '500-1000':  $sql .= " AND p.price BETWEEN 500 AND 1000"; break;
                case 'over1000':  $sql .= " AND p.price > 1000"; break;
            }
        }

        // Location filter
        if (!empty($location)) {
            $sql    .= " AND p.location LIKE ?";
            $params[] = "%$location%";
            $types  .= "s";
        }

        // Sorting
        switch ($sort) {
            case 'price_low':  $sql .= " ORDER BY p.price ASC"; break;
            case 'price_high': $sql .= " ORDER BY p.price DESC"; break;
            default:           $sql .= " ORDER BY p.created_at DESC";
        }

        // Pagination
        $sql    .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types  .= "ii";

        // Execute main query
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['products' => [], 'total' => 0];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $imagePath = $row['image_url'];
            if (empty($imagePath)) {
                $imagePath = 'images/default-product.png';
            }

            $products[] = [
                'id'             => (int) $row['product_id'],
                'name'           => $row['product_name'],
                'price'          => (float) $row['price'],
                'image'          => $imagePath,
                'seller_name'    => $row['seller_name'],
                'seller_id'      => (int) $row['seller_id'],
                'location'       => $row['location'] ?? 'South Africa',
                'condition'      => $row['condition'] ?? 'Good',
                'stock_quantity' => (int) ($row['stock_quantity'] ?? 1),
                'is_verified'    => (bool) $row['is_verified'],
                'profile_image'  => $row['profile_image'] ?? null
            ];
        }
        $stmt->close();

        // Count query (same filters, no LIMIT)
        $countSql = "SELECT COUNT(*) as total
                    FROM products p
                    JOIN users u ON p.seller_id = u.user_id
                    WHERE p.status = 'active'
                    AND (p.title LIKE ? OR p.description LIKE ?)";

        // Rebuild count params without limit/offset
        $countParams = ["%$search%", "%$search%"];
        $countTypes  = "ss";

        if (!empty($categories) && $categories[0] !== '') {
            $placeholders = implode(',', array_fill(0, count($categories), '?'));
            $countSql .= " AND p.category_id IN ($placeholders)";
            foreach ($categories as $cat) {
                $countParams[] = (int) $cat;
                $countTypes  .= "i";
            }
        }
        if (!empty($priceRange)) {
            switch ($priceRange) {
                case 'under100':  $countSql .= " AND p.price < 100"; break;
                case '100-500':   $countSql .= " AND p.price BETWEEN 100 AND 500"; break;
                case '500-1000':  $countSql .= " AND p.price BETWEEN 500 AND 1000"; break;
                case 'over1000':  $countSql .= " AND p.price > 1000"; break;
            }
        }
        if (!empty($location)) {
            $countSql    .= " AND p.location LIKE ?";
            $countParams[] = "%$location%";
            $countTypes  .= "s";
        }

        $countStmt = $this->db->prepare($countSql);
        if ($countStmt) {
            $countStmt->bind_param($countTypes, ...$countParams);
            $countStmt->execute();
            $total = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
            $countStmt->close();
        } else {
            $total = 0;
        }

        return [
            'products' => $products,
            'total'    => $total
        ];
    }

}