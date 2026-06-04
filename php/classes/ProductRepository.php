<?php

/**
 * ConsuTrade - ProductRepository
 *
 * Handles all product database operations.
 * NOTE: Product images are handled by ProductImageRepository.
 *
 * @author     Kamogelo Phale
 * @module     ITECA3-12 Web Development and e-Commerce
 * @institution Eduvos
 * @version    2.0.0
 * @since      2026
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
    //  PRODUCT OBJECT METHODS (OOP)
    // ============================================================

    /**
     * Get product as Product object.
     *
     * @param int $productId Product ID
     * @return Product|null
     */
    public function getProductObject(int $productId): ?Product
    {
        $sql = "SELECT p.product_id, p.seller_id, p.category_id, p.title, p.description, 
                       p.price, p.stock_quantity, p.`condition`, p.location, p.image_url, 
                       p.status, p.created_at, p.suspended_by, p.suspended_reason
                FROM products p
                WHERE p.product_id = ? AND p.status != 'deleted'";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return new Product($row);
        }
        $stmt->close();
        return null;
    }

    /**
     * Get public products as Product objects (not arrays)
     *
     * @param array $filters Associative array of filters
     * @return Product[]
     */
    public function getPublicProductObjects(array $filters = []): array
    {
        $result = $this->getPublicProducts($filters);
        $products = [];

        foreach ($result['products'] as $productData) {
            // Map the array data to match Product constructor expectations
            $mappedData = [
                'product_id' => $productData['id'],
                'seller_id' => $productData['seller_id'],
                'title' => $productData['name'],
                'price' => $productData['price'],
                'image_url' => $productData['image'],  // Map 'image' to 'image_url'
                'condition' => $productData['condition'],
                'stock_quantity' => $productData['stock_quantity'],
                'location' => $productData['location'],
                'status' => 'active'
            ];
            $products[] = new Product($mappedData);
        }

        return $products;
    }

    /**
     * Get seller products as Product objects.
     *
     * @param int $sellerId Seller ID
     * @param string $filter Status filter
     * @param string $search Search term
     * @param int $limit Limit
     * @param int $offset Offset
     * @return Product[]
     */
    public function getSellerProductObjects(
        int $sellerId,
        string $filter = 'all',
        string $search = '',
        int $limit = 0,
        int $offset = 0
    ): array {
        $sql = "SELECT p.product_id, p.seller_id, p.category_id, p.title, p.description, 
                       p.price, p.stock_quantity, p.`condition`, p.location, p.image_url, 
                       p.status, p.created_at, p.suspended_by, p.suspended_reason
                FROM products p
                WHERE p.seller_id = ? AND p.status != 'deleted'";

        $params = [$sellerId];
        $types = "i";

        if ($filter !== 'all') {
            $sql .= " AND p.status = ?";
            $params[] = $filter;
            $types .= "s";
        }

        if (!empty($search)) {
            $sql .= " AND (p.title LIKE ? OR p.product_id LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "ss";
        }

        $sql .= " ORDER BY p.created_at DESC";

        if ($limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = new Product($row);
        }
        $stmt->close();

        return $products;
    }

    /**
     * Save product changes to database.
     *
     * @param Product $product Product object
     * @return bool
     */
    public function saveProduct(Product $product): bool
    {
        $title = $product->getTitle();
        $description = $product->getDescription();
        $price = $product->getPrice();
        $stockQuantity = $product->getStockQuantity();
        $condition = $product->getCondition();
        $location = $product->getLocation();
        $categoryId = $product->getCategoryId();
        $imageUrl = $product->getImageUrl();
        $status = $product->getStatus();
        $productId = $product->getProductId();
        $sellerId = $product->getSellerId();

        $sql = "UPDATE products SET 
                    title = ?,
                    description = ?,
                    price = ?,
                    stock_quantity = ?,
                    `condition` = ?,
                    location = ?,
                    category_id = ?,
                    image_url = ?,
                    status = ?
                WHERE product_id = ? AND seller_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'ssdisssssii',
            $title,
            $description,
            $price,
            $stockQuantity,
            $condition,
            $location,
            $categoryId,
            $imageUrl,
            $status,
            $productId,
            $sellerId
        );

        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Create a new product from Product object.
     *
     * @param Product $product Product object (without product_id)
     * @return int|false Insert ID or false on failure
     */
    public function createProduct(Product $product)
    {
        $sellerId = $product->getSellerId();
        $categoryId = $product->getCategoryId();
        $title = $product->getTitle();
        $description = $product->getDescription();
        $price = $product->getPrice();
        $stockQuantity = $product->getStockQuantity();
        $condition = $product->getCondition();
        $location = $product->getLocation();
        $imageUrl = $product->getImageUrl();
        $status = $product->getStatus();

        $sql = "INSERT INTO products (seller_id, category_id, title, description, price, stock_quantity, `condition`, location, image_url, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'iissdissss',
            $sellerId,
            $categoryId,
            $title,
            $description,
            $price,
            $stockQuantity,
            $condition,
            $location,
            $imageUrl,
            $status
        );

        if ($stmt->execute()) {
            $productId = $stmt->insert_id;
            $stmt->close();
            return $productId;
        }
        $stmt->close();
        return false;
    }

    // ============================================================
    //  PRODUCT QUERIES (ARRAY BASED)
    // ============================================================

    /**
     * Get seller products with filters (includes primary image and suspension info).
     *
     * @param int    $id      Seller ID
     * @param string $filter  Status filter
     * @param string $search  Search term
     * @param int    $limit   Maximum products
     * @param int    $offset  Pagination offset
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
                       p.stock_quantity, p.created_at, p.suspended_by, p.suspended_reason,
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
                'category_name'  => $row['category_name'] ?? 'General',
                'suspended_by'   => $row['suspended_by'],
                'suspended_reason' => $row['suspended_reason']
            ];
        }
        $stmt->close();

        return $products;
    }

    /**
     * Update product status with suspension tracking.
     *
     * @param int    $id              Product ID
     * @param int    $sellerId        Seller ID
     * @param string $action          'activate' or 'suspend'
     * @param string $suspendedBy     Who is performing the action ('seller' or 'admin')
     * @param string $suspendedReason Optional reason for suspension
     * @return array
     */
    public function updateProductStatus(int $id, int $sellerId, string $action, string $suspendedBy = 'seller', string $suspendedReason = ''): array
    {
        $checkSql = "SELECT product_id, status, suspended_by FROM products
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

        if ($action === 'activate') {
            // Check if product was admin-suspended
            if ($product['suspended_by'] === 'admin' && $suspendedBy !== 'admin') {
                return ['success' => false, 'message' => 'This product was suspended by an admin. Only an admin can reactivate it.'];
            }

            $newStatus = 'active';
            $updateSql = "UPDATE products SET status = ?, suspended_by = NULL, suspended_reason = NULL 
                          WHERE product_id = ? AND seller_id = ?";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->bind_param('sii', $newStatus, $id, $sellerId);
        } else {
            // Suspend action
            $newStatus = 'suspended';
            $updateSql = "UPDATE products SET status = ?, suspended_by = ?, suspended_reason = ? 
                          WHERE product_id = ? AND seller_id = ?";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->bind_param('sssii', $newStatus, $suspendedBy, $suspendedReason, $id, $sellerId);
        }

        if ($updateStmt->execute()) {
            $updateStmt->close();
            $message = ($action === 'activate') ? 'Product activated.' : 'Product suspended.';
            return ['success' => true, 'message' => $message];
        }

        $updateStmt->close();
        return ['success' => false, 'message' => 'Failed to update product status.'];
    }

    /**
     * Get product data for display (lightweight, for breadcrumb).
     *
     * @param int $productId Product ID
     * @return array|null
     */
    public function getProductForDisplay(int $productId): ?array
    {
        $sql = "SELECT p.product_id, p.title, p.image_url
                FROM products p
                WHERE p.product_id = ? AND p.status = 'active'";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return [
                'id' => (int)$row['product_id'],
                'title' => $row['title'],
                'image_url' => $row['image_url']
            ];
        }

        $stmt->close();
        return null;
    }

    /**
     * Get single product for editing (with ownership verification).
     *
     * @param int $productId Product ID
     * @param int $sellerId  Seller ID
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
     * @param int   $sellerId Seller ID
     * @param array $data     Product data
     * @return array
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
     * Delete product (soft delete).
     *
     * @param int $id       Product ID
     * @param int $sellerId Seller ID
     * @return array
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
     * Update product stock quantity.
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

    /**
     * Count active products for a user (seller).
     *
     * @param int $userId User ID
     * @return int
     */
    public function countUserProducts(int $userId): int
    {
        $sql = "SELECT COUNT(*) as total FROM products WHERE seller_id = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = (int)($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        return $total;
    }

    // ============================================================
    //  IMAGE FILE OPERATIONS
    // ============================================================

    /**
     * Helper to get full system path for an image.
     *
     * @param string $imagePath Relative image path
     * @return string
     */
    private function getFullPath(string $imagePath): string
    {
        $basePaths = [
            $_SERVER['DOCUMENT_ROOT'] . '/',
            $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/',
            dirname(__DIR__, 2) . '/',
            __DIR__ . '/../../',
        ];

        foreach ($basePaths as $basePath) {
            $fullPath = rtrim($basePath, '/') . '/' . ltrim($imagePath, '/');
            if (file_exists(dirname($fullPath))) {
                return $fullPath;
            }
        }

        return $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($imagePath, '/');
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

        $fullPath = $this->getFullPath($imagePath);

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return true;
    }

    /**
     * Get the full URL for a product image, with fallback to default.
     *
     * @param string $imagePath The stored image path
     * @return string
     */
    public function getProductImageUrl(string $imagePath): string
    {
        $baseUrl = getBaseUrl();

        if (empty($imagePath)) {
            return $baseUrl . 'images/default-product.png';
        }

        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return $imagePath;
        }

        if (str_starts_with($imagePath, '/')) {
            $imagePath = ltrim($imagePath, '/');
        }

        $fullPath = $this->getFullPath($imagePath);
        if (file_exists($fullPath)) {
            return $baseUrl . $imagePath;
        }

        return $baseUrl . 'images/default-product.png';
    }

    /**
     * Convert an uploaded image to WebP format.
     *
     * @param array  $file         The uploaded file from $_FILES
     * @param int    $sellerId     The seller's ID
     * @param string $productTitle The product title
     * @param string $prefix       Optional filename prefix
     * @return string|false
     */
    public function convertToWebP(array $file, int $sellerId, string $productTitle, string $prefix = 'main')
    {
        $uploadPaths = [
            $_SERVER['DOCUMENT_ROOT'] . '/uploads/products/',
            $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/uploads/products/',
            dirname(__DIR__, 2) . '/uploads/products/',
            __DIR__ . '/../../uploads/products/',
        ];

        $uploadDir = '';
        foreach ($uploadPaths as $path) {
            if (is_dir(dirname($path)) || mkdir(dirname($path), 0777, true)) {
                $uploadDir = $path;
                break;
            }
        }

        if (empty($uploadDir)) {
            $uploadDir = __DIR__ . '/../../uploads/products/';
        }

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
                $resized,
                $image,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $origWidth,
                $origHeight
            );
            $image = $resized;
        }

        $success = imagewebp($image, $destination, 80);

        if ($success) {
            return 'uploads/products/' . $filename;
        }

        return false;
    }

    // ============================================================
    //  PUBLIC PRODUCT LISTINGS
    // ============================================================

    /**
     * Get public product listings with filters, sorting, and pagination.
     *
     * @param array $filters Associative array of filters
     * @return array
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

        if (!empty($categories) && $categories[0] !== '') {
            $placeholders = implode(',', array_fill(0, count($categories), '?'));
            $sql .= " AND p.category_id IN ($placeholders)";
            foreach ($categories as $cat) {
                $params[] = (int) $cat;
                $types  .= "i";
            }
        }

        if (!empty($priceRange)) {
            switch ($priceRange) {
                case 'under100':
                    $sql .= " AND p.price < 100";
                    break;
                case '100-500':
                    $sql .= " AND p.price BETWEEN 100 AND 500";
                    break;
                case '500-1000':
                    $sql .= " AND p.price BETWEEN 500 AND 1000";
                    break;
                case 'over1000':
                    $sql .= " AND p.price > 1000";
                    break;
            }
        }

        if (!empty($location)) {
            $sql    .= " AND p.location LIKE ?";
            $params[] = "%$location%";
            $types  .= "s";
        }

        $countSql = $sql;
        $countParams = $params;
        $countTypes = $types;

        switch ($sort) {
            case 'price_low':
                $sql .= " ORDER BY p.price ASC";
                break;
            case 'price_high':
                $sql .= " ORDER BY p.price DESC";
                break;
            default:
                $sql .= " ORDER BY p.created_at DESC";
        }

        $sql    .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types  .= "ii";

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
     * Search public products with filters.
     *
     * @param string $search  Search term
     * @param array  $filters Filters
     * @return array
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

        if (!empty($categories) && $categories[0] !== '') {
            $placeholders = implode(',', array_fill(0, count($categories), '?'));
            $sql .= " AND p.category_id IN ($placeholders)";
            foreach ($categories as $cat) {
                $params[] = (int) $cat;
                $types  .= "i";
            }
        }

        if (!empty($priceRange)) {
            switch ($priceRange) {
                case 'under100':
                    $sql .= " AND p.price < 100";
                    break;
                case '100-500':
                    $sql .= " AND p.price BETWEEN 100 AND 500";
                    break;
                case '500-1000':
                    $sql .= " AND p.price BETWEEN 500 AND 1000";
                    break;
                case 'over1000':
                    $sql .= " AND p.price > 1000";
                    break;
            }
        }

        if (!empty($location)) {
            $sql    .= " AND p.location LIKE ?";
            $params[] = "%$location%";
            $types  .= "s";
        }

        switch ($sort) {
            case 'price_low':
                $sql .= " ORDER BY p.price ASC";
                break;
            case 'price_high':
                $sql .= " ORDER BY p.price DESC";
                break;
            default:
                $sql .= " ORDER BY p.created_at DESC";
        }

        $sql    .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types  .= "ii";

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

        // Count query
        $countSql = "SELECT COUNT(*) as total
                    FROM products p
                    JOIN users u ON p.seller_id = u.user_id
                    WHERE p.status = 'active'
                    AND (p.title LIKE ? OR p.description LIKE ?)";

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
                case 'under100':
                    $countSql .= " AND p.price < 100";
                    break;
                case '100-500':
                    $countSql .= " AND p.price BETWEEN 100 AND 500";
                    break;
                case '500-1000':
                    $countSql .= " AND p.price BETWEEN 500 AND 1000";
                    break;
                case 'over1000':
                    $countSql .= " AND p.price > 1000";
                    break;
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

    // ============================================================
    //  ADMIN PRODUCT MANAGEMENT
    // ============================================================

    /**
     * Get all products for admin (across all sellers) with filters and pagination.
     *
     * @param string $status Status filter (all, active, suspended)
     * @param string $search Search term (product title, seller name)
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     * @return array
     */
    public function getAllProductsForAdmin(string $status = 'all', string $search = '', int $limit = 12, int $offset = 0): array
    {
        $sql = "SELECT p.product_id as id, p.title as name, p.price, p.status,
                p.stock_quantity, p.created_at,
                COALESCE(pi.image_url, p.image_url) AS display_image,
                u.full_name as seller_name
                FROM products p
                LEFT JOIN users u ON p.seller_id = u.user_id
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                WHERE p.status != 'deleted'";

        $params = [];
        $types = "";

        if ($status !== 'all') {
            $sql .= " AND p.status = ?";
            $params[] = $status;
            $types .= "s";
        }

        if (!empty($search)) {
            $sql .= " AND (p.title LIKE ? OR u.full_name LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "ss";
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = [
                'id'             => (int) $row['id'],
                'name'           => $row['name'],
                'price'          => (float) $row['price'],
                'status'         => $row['status'],
                'stock_quantity' => (int) $row['stock_quantity'],
                'created_at'     => date('d M Y', strtotime($row['created_at'])),
                'display_image'  => $this->getProductImageUrl($row['display_image']),
                'image'          => $this->getProductImageUrl($row['display_image']),
                'seller_name'    => $row['seller_name'] ?? 'Unknown'
            ];
        }
        $stmt->close();
        return $products;
    }

    /**
     * Get total count of products for admin with filters for pagination.
     *
     * @param string $status Status filter (all, active, suspended)
     * @param string $search Search term (product title, seller name)
     * @return int
     */
    public function getProductsCountForAdmin(string $status = 'all', string $search = ''): int
    {
        $sql = "SELECT COUNT(*) as total FROM products p
                LEFT JOIN users u ON p.seller_id = u.user_id
                WHERE p.status != 'deleted'";

        $params = [];
        $types = "";

        if ($status !== 'all') {
            $sql .= " AND p.status = ?";
            $params[] = $status;
            $types .= "s";
        }

        if (!empty($search)) {
            $sql .= " AND (p.title LIKE ? OR u.full_name LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "ss";
        }

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $total = (int) ($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        return $total;
    }

    // ============================================================
    //  SELLER DASHBOARD PRODUCT DISPLAY
    // ============================================================

    /**
     * Get seller products for dashboard or public view.
     *
     * @param int $sellerId Seller ID
     * @param bool $isOwner Whether the viewer is the seller themselves
     * @param int $limit Maximum products to return (0 for all)
     * @return array
     */
    public function getSellerProductsForDisplay(int $sellerId, bool $isOwner = false, int $limit = 0): array
    {
        if ($isOwner) {
            $sql = "SELECT p.product_id as id, p.title as name, p.price, p.image_url as image,
                       p.condition, p.stock_quantity, p.created_at, p.status, p.suspended_by, p.suspended_reason,
                       COALESCE(pi.image_url, p.image_url) AS display_image,
                       c.category_name
                FROM products p
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE p.seller_id = ? AND p.status != 'deleted'
                ORDER BY p.created_at DESC";
        } else {
            $sql = "SELECT p.product_id as id, p.title as name, p.price, p.image_url as image,
                       p.condition, p.stock_quantity, p.created_at,
                       COALESCE(pi.image_url, p.image_url) AS display_image,
                       c.category_name
                FROM products p
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE p.seller_id = ? AND p.status = 'active'
                ORDER BY p.created_at DESC";
        }

        if ($limit > 0) {
            $sql .= " LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $sellerId, $limit);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $sellerId);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $imagePath = $row['display_image'] ?? $row['image'];
            if ($imagePath && !preg_match('/^http/', $imagePath)) {
                $imagePath = getBaseUrl() . $imagePath;
            }

            $productData = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'price' => (float)$row['price'],
                'image' => $imagePath ?: getBaseUrl() . 'images/default-product.png',
                'image_url' => $imagePath ?: getBaseUrl() . 'images/default-product.png',
                'display_image' => $imagePath ?: getBaseUrl() . 'images/default-product.png',
                'condition' => ucfirst($row['condition'] ?? 'Good'),
                'stock_quantity' => (int)($row['stock_quantity'] ?? 0),
                'created_at' => $row['created_at'],
                'category_name' => $row['category_name'] ?? 'General'
            ];

            if ($isOwner) {
                $productData['status'] = $row['status'];
                $productData['suspended_by'] = $row['suspended_by'] ?? null;
                $productData['suspended_reason'] = $row['suspended_reason'] ?? null;
            }

            $products[] = $productData;
        }
        $stmt->close();

        return $products;
    }
}
