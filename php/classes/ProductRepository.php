<?php

/**
 * ConsuTrade - ProductRepository
 *
 * Handles all product database operations and image uploads.
 * Images are stored in uploads/products/ directory as WebP format.
 *
 * @author Kamogelo Phale
 * @version 2.1.0
 */

class ProductRepository
{
    /** @var mysqli Database connection */
    private $db;

    /** @var string Absolute path to upload directory */
    private string $uploadPath;

    /** @var string Web-accessible URL path to uploads */
    private string $uploadUrl;

    /**
     * Constructor.
     *
     * @param mysqli $db Database connection
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->initializeUploadPaths();
    }

    /**
     * Initialize upload directory paths.
     * Uses project root relative paths for reliability.
     */
    private function initializeUploadPaths(): void
    {
        // Detect project root (where init.php lives)
        $projectRoot = $this->detectProjectRoot();

        $this->uploadPath = $projectRoot . '/uploads/products/';
        $this->uploadUrl = '/uploads/products/';

        // Create directory if it doesn't exist
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Detect the project root directory.
     * Traverses up from current file location until finding init.php or uploads folder.
     *
     * @return string Absolute path to project root
     */
    private function detectProjectRoot(): string
    {
        $currentDir = __DIR__;

        // Look for uploads directory or init.php as markers
        for ($i = 0; $i < 5; $i++) {
            if (is_dir($currentDir . '/uploads') || file_exists($currentDir . '/init.php')) {
                return $currentDir;
            }
            $currentDir = dirname($currentDir);
        }

        // Fallback to document root (less reliable but works on most hosts)
        return rtrim($_SERVER['DOCUMENT_ROOT'], '/');
    }

    // ============================================================
    //  IMAGE UPLOAD METHODS
    // ============================================================

    /**
     * Upload and convert product image to WebP.
     * 
     * @param array $file The uploaded file from $_FILES
     * @param int $sellerId The seller's ID (used in filename)
     * @param string $productTitle The product title (used in filename)
     * @param string $prefix Filename prefix (main, gallery, thumb)
     * @return string|false Relative path on success, false on failure
     */
    public function uploadProductImage(array $file, int $sellerId, string $productTitle, string $prefix = 'main'): string|false
    {
        // Validate upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log("[ProductRepository] Upload error: " . $this->getUploadErrorMessage($file['error']));
            return false;
        }

        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            error_log("[ProductRepository] File too large: " . $file['size'] . " bytes");
            return false;
        }

        // Validate image type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $image = $this->loadImageFromFile($file['tmp_name'], $mimeType);
        if (!$image) {
            error_log("[ProductRepository] Failed to load image: " . $mimeType);
            return false;
        }

        // Resize if needed (max 1200px)
        $image = $this->resizeImage($image, 1200);

        // Generate unique filename
        $safeTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $productTitle);
        $safeTitle = substr($safeTitle, 0, 50) ?: 'product';
        $filename = sprintf('%d_%d_%s_%s.webp', $sellerId, time(), $prefix, $safeTitle);
        $destination = $this->uploadPath . $filename;

        // Save as WebP with 80% quality
        $success = imagewebp($image, $destination, 80);
        imagedestroy($image);

        if ($success && file_exists($destination)) {
            return $this->uploadUrl . $filename;
        }

        error_log("[ProductRepository] Failed to save WebP: " . $destination);
        return false;
    }

    /**
     * Load image from file based on MIME type.
     *
     * @param string $filePath Path to uploaded file
     * @param string $mimeType MIME type of the file
     * @return GdImage|false
     */
    private function loadImageFromFile(string $filePath, string $mimeType): mixed
    {
        return match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($filePath),
            'image/png' => $this->loadPngWithAlpha($filePath),
            'image/webp' => imagecreatefromwebp($filePath),
            'image/gif' => imagecreatefromgif($filePath),
            default => false,
        };
    }

    /**
     * Load PNG image preserving transparency.
     *
     * @param string $filePath Path to PNG file
     * @return GdImage|false
     */
    private function loadPngWithAlpha(string $filePath): mixed
    {
        $image = imagecreatefrompng($filePath);
        if ($image) {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }
        return $image;
    }

    /**
     * Resize image to fit within max dimensions.
     *
     * @param GdImage $image Source image
     * @param int $maxSize Maximum width/height in pixels
     * @return GdImage Resized image (original if no resize needed)
     */
    private function resizeImage($image, int $maxSize): mixed
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxSize && $height <= $maxSize) {
            return $image;
        }

        $ratio = min($maxSize / $width, $maxSize / $height);
        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    /**
     * Delete a product image file from disk.
     *
     * @param string|null $imageUrl Relative URL of the image
     * @return bool True if deleted or not needed, false on failure
     */
    public function deleteImageFile(?string $imageUrl): bool
    {
        if (empty($imageUrl)) {
            return true;
        }

        // Extract filename from URL
        $filename = basename($imageUrl);
        $filePath = $this->uploadPath . $filename;

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return true; // File doesn't exist, nothing to delete
    }

    /**
     * Get full URL for a product image.
     *
     * @param string|null $imageUrl Stored image path
     * @return string Full URL with fallback to default
     */
    public function getImageUrl(?string $imageUrl): string
    {
        $baseUrl = getBaseUrl();

        if (empty($imageUrl)) {
            return $baseUrl . 'images/default-product.png';
        }

        // Already absolute URL
        if (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://')) {
            return $imageUrl;
        }

        // Check if file actually exists
        $filename = basename($imageUrl);
        if (file_exists($this->uploadPath . $filename)) {
            return $baseUrl . ltrim($imageUrl, '/');
        }

        return $baseUrl . 'images/default-product.png';
    }

    /**
     * Get user-friendly upload error message.
     *
     * @param int $errorCode PHP upload error code
     * @return string
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File too large (max 5MB)',
            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
            default => 'Unknown upload error',
        };
    }

    // ============================================================
    //  PRODUCT QUERIES (ARRAY BASED)
    // ============================================================

    /**
     * Get seller products with filters.
     *
     * @param int $id Seller ID
     * @param string $filter Status filter
     * @param string $search Search term
     * @param int $limit Maximum products
     * @param int $offset Pagination offset
     * @return array
     */
    public function getSellerProducts(int $id, string $filter = 'all', string $search = '', int $limit = 0, int $offset = 0): array
    {
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
     * Get single product as Product object.
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
     * Update product status with suspension tracking.
     *
     * @param int $id Product ID
     * @param int $sellerId Seller ID
     * @param string $action 'activate' or 'suspend'
     * @param string $suspendedBy Who is performing the action
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
     * Save product changes to database.
     *
     * @param Product $product Product object
     * @return bool
     */
    public function saveProduct(Product $product): bool
    {
        // Store values in variables first
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
    public function createProduct(Product $product): int|false
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

    /**
     * Get product for editing (with ownership verification).
     *
     * @param int $productId Product ID
     * @param int $sellerId Seller ID
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
     * Delete product (soft delete).
     *
     * @param int $id Product ID
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
                $this->deleteImageFile($product['image_url']);
            }

            return ['success' => true, 'message' => 'Product deleted.'];
        }

        $deleteStmt->close();
        return ['success' => false, 'message' => 'Failed to delete product.'];
    }

    // ============================================================
    //  STOCK MANAGEMENT METHODS
    // ============================================================

    /**
     * Update product stock quantity.
     *
     * @param int $productId Product ID
     * @param int $qty Quantity to add (negative to subtract)
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
     * Get current stock quantity for a product.
     *
     * @param int $productId Product ID
     * @return int Current stock quantity
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
     * Decrease product stock after purchase.
     * This method ensures we don't go below zero stock.
     *
     * @param int $productId Product ID
     * @param int $quantity Quantity to decrease by
     * @return bool True on success, false on failure (insufficient stock)
     */
    public function decreaseProductStock(int $productId, int $quantity): bool
    {
        // First check if we have enough stock
        $currentStock = $this->getProductStock($productId);

        if ($currentStock < $quantity) {
            error_log("Insufficient stock for product ID: $productId. Stock: $currentStock, Requested: $quantity");
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?"
        );
        $stmt->bind_param('iii', $quantity, $productId, $quantity);
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
                error_log("Failed to restore stock for product ID: {$item['product_id']}");
            }
            $stockStmt->close();
        }
        $itemsStmt->close();

        return $success;
    }

    /**
     * Count active products for a seller.
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
     * @param string $search Search term
     * @param array $filters Filters
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
            $products[] = [
                'id'             => (int) $row['product_id'],
                'name'           => $row['product_name'],
                'price'          => (float) $row['price'],
                'image'          => $row['image_url'] ?: 'images/default-product.png',
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
     * Get all products for admin with filters and pagination.
     *
     * @param string $status Status filter
     * @param string $search Search term
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
                'display_image'  => $this->getImageUrl($row['display_image']),
                'image'          => $this->getImageUrl($row['display_image']),
                'seller_name'    => $row['seller_name'] ?? 'Unknown'
            ];
        }
        $stmt->close();
        return $products;
    }

    /**
     * Get total count of products for admin with filters.
     *
     * @param string $status Status filter
     * @param string $search Search term
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
    //  LEGACY METHODS (for backward compatibility)
    // ============================================================

    /**
     * Legacy method - use uploadProductImage() instead.
     * 
     * @deprecated Use uploadProductImage() instead
     */
    public function convertToWebP($file, $sellerId, $productTitle, $prefix = 'main')
    {
        return $this->uploadProductImage($file, $sellerId, $productTitle, $prefix);
    }

    /**
     * Legacy method - use getImageUrl() instead.
     * 
     * @deprecated Use getImageUrl() instead
     */
    public function getProductImageUrl($imagePath)
    {
        return $this->getImageUrl($imagePath);
    }

    /**
     * Legacy method - use deleteImageFile() instead.
     * 
     * @deprecated Use deleteImageFile() instead
     */
    public function deleteProductImage($imagePath)
    {
        return $this->deleteImageFile($imagePath);
    }
}
