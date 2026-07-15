<?php

/**
 * ConsuTrade - ProductRepository
 *
 * Handles all product database operations ONLY.
 * Image operations are delegated to ProductImageService.
 * Business logic moved to ProductService.
 *
 * @author Kamogelo Phale
 * @version 2.1.0
 */

class ProductRepository
{
    /** @var mysqli Database connection */
    private $db;

    /** @var ProductImageService Image service for file operations */
    private ProductImageService $imageService;

    /**
     * Constructor.
     *
     * @param mysqli $db Database connection
     * @param ProductImageService|null $imageService Image service (optional)
     */
    public function __construct($db, ?ProductImageService $imageService = null)
    {
        $this->db = $db;
        $this->imageService = $imageService ?? new ProductImageService();
    }

    // ============================================================
    // IMAGE DELEGATION METHODS (Simple passthrough)
    // ============================================================

    public function getImageUrl(?string $imageUrl): string
    {
        return $this->imageService->getImageUrl($imageUrl);
    }

    public function deleteImageFile(?string $imageUrl): bool
    {
        return $this->imageService->deleteImageFile($imageUrl);
    }

    public function uploadImage(array $file, int $sellerId, string $productTitle, string $prefix = 'main'): string|false
    {
        return $this->imageService->uploadImage($file, $sellerId, $productTitle, $prefix);
    }

    // ============================================================
    // CREATE (C)
    // ============================================================

    /**
     * Create a new product from Product object.
     *
     * @param Product $product Product object (without product_id)
     * @return int|false Insert ID or false on failure
     */
    public function create(Product $product): int|false
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
    // READ (R)
    // ============================================================

    /**
     * Get single product as Product object.
     *
     * @param int $productId Product ID
     * @return Product|null
     */
    public function findById(int $productId): ?Product
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
     * Get product data for display (lightweight).
     *
     * @param int $productId Product ID
     * @return array|null
     */
    public function findForDisplay(int $productId): ?array
    {
        $sql = "SELECT product_id, title, image_url
                FROM products
                WHERE product_id = ? AND status != 'deleted'";

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
     * Get product for editing (with ownership verification).
     *
     * @param int $productId Product ID
     * @param int $sellerId Seller ID
     * @return array|null
     */
    public function findForEdit(int $productId, int $sellerId): ?array
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
     * Get seller products with filters.
     *
     * @param int $sellerId Seller ID
     * @param string $filter Status filter
     * @param string $search Search term
     * @param int $limit Maximum products
     * @param int $offset Pagination offset
     * @return array
     */
    public function findBySeller(int $sellerId, string $filter = 'all', string $search = '', int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT p.product_id, p.title, p.price, p.image_url, p.status,
                       p.stock_quantity, p.created_at, p.suspended_by, p.suspended_reason,
                       c.category_name,
                       COALESCE(pi.image_url, p.image_url) AS display_image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                WHERE p.seller_id = ? AND p.status != 'deleted'";

        $params = [$sellerId];
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
     * Get seller products for display on seller dashboard.
     *
     * @param int $sellerId Seller ID
     * @param bool $isOwner Whether the viewer is the product owner
     * @param int $limit Maximum products to return (0 for all)
     * @return array
     */
    public function findBySellerForDisplay(int $sellerId, bool $isOwner = false, int $limit = 0): array
    {
        $sql = "SELECT p.product_id as id, p.title as name, p.price, p.status,
                       p.stock_quantity, p.created_at,
                       COALESCE(pi.image_url, p.image_url) AS display_image,
                       c.category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                WHERE p.seller_id = ? AND p.status != 'deleted'
                ORDER BY p.created_at DESC";

        $params = [$sellerId];
        $types = "i";

        if ($limit > 0) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
            $types .= "i";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
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
                'category_name'  => $row['category_name'] ?? 'General'
            ];
        }
        $stmt->close();

        return $products;
    }

    /**
     * Get all products for admin with filters.
     *
     * @param string $status Status filter
     * @param string $search Search term
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     * @return array
     */
    public function findAll(string $status = 'all', string $search = '', int $limit = 12, int $offset = 0): array
    {
        $sql = "SELECT p.product_id as id, p.title as name, p.price, p.status,
                       p.stock_quantity, p.created_at,
                       COALESCE(pi.image_url, p.image_url) AS display_image,
                       u.full_name as seller_name,
                       u.id_verified as seller_is_verified,
                       u.profile_image as seller_profile_image,
                       u.location as seller_location
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
                'id'                     => (int) $row['id'],
                'name'                   => $row['name'],
                'price'                  => (float) $row['price'],
                'status'                 => $row['status'],
                'stock_quantity'         => (int) $row['stock_quantity'],
                'created_at'             => date('d M Y', strtotime($row['created_at'])),
                'display_image'          => $this->getImageUrl($row['display_image']),
                'image'                  => $this->getImageUrl($row['display_image']),
                'seller_name'            => $row['seller_name'] ?? 'Unknown',
                'seller_is_verified'     => (int) ($row['seller_is_verified'] ?? 0),
                'seller_profile_image'   => $row['seller_profile_image'] ?? null,
                'seller_location'        => $row['seller_location'] ?? 'South Africa'
            ];
        }
        $stmt->close();
        return $products;
    }

    /**
     * Get public product listings with filters.
     *
     * @param array $filters Associative array of filters
     * @return array
     */
    public function findPublic(array $filters = []): array
    {
        $categories  = $filters['categories'] ?? [];
        $priceRange  = $filters['price_range'] ?? '';
        $location    = $filters['location'] ?? '';
        $sort        = $filters['sort'] ?? 'newest';
        $limit       = $filters['limit'] ?? 12;
        $offset      = $filters['offset'] ?? 0;
        $sellerId    = $filters['seller_id'] ?? 0;

        $sql = "SELECT p.product_id, p.title as product_name, p.price, p.image_url,
                       p.location, p.condition, p.stock_quantity, p.created_at,
                       COALESCE(pi.image_url, p.image_url) AS display_image,
                       u.full_name as seller_name, u.user_id as seller_id,
                       u.profile_image, u.id_verified as is_verified,
                       u.last_active
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

        if ($sellerId > 0) {
            $sql .= " AND p.seller_id = ?";
            $params[] = $sellerId;
            $types .= "i";
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
    public function search(string $search, array $filters = []): array
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
                       u.profile_image, u.id_verified as is_verified,
                       u.last_active
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
                'is_online' => isset($row['last_active']) && $row['last_active']
                    ? (time() - strtotime($row['last_active']) < 900)
                    : false,
                'profile_image'  => $row['profile_image'] ?? null
            ];
        }
        $stmt->close();

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
    // UPDATE (U)
    // ============================================================

    /**
     * Save product changes to database.
     *
     * @param Product $product Product object
     * @return bool
     */
    public function update(Product $product): bool
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
     * Decrease product stock after purchase.
     *
     * @param int $productId Product ID
     * @param int $quantity Quantity to decrease by
     * @return bool True on success, false on failure (insufficient stock)
     */
    public function decreaseStock(int $productId, int $quantity): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?"
        );
        $stmt->bind_param('iii', $quantity, $productId, $quantity);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Increase product stock (for restoring stock on payment failure or cancellation).
     *
     * @param int $productId Product ID
     * @param int $quantity Quantity to increase by
     * @return bool
     */
    public function increaseStock(int $productId, int $quantity): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?"
        );
        $stmt->bind_param('ii', $quantity, $productId);
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
    public function restoreStockFromOrder(int $orderId): bool
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
     * Update product status (database only).
     *
     * @param int $id Product ID
     * @param int $sellerId Seller ID
     * @param string $newStatus New status
     * @param string|null $suspendedBy Who suspended (if suspending)
     * @param string|null $suspendedReason Reason for suspension
     * @return bool
     */
    public function updateStatusDirect(int $id, int $sellerId, string $newStatus, ?string $suspendedBy = null, ?string $suspendedReason = null): bool
    {
        if ($newStatus === 'suspended' && $suspendedBy !== null) {
            $sql = "UPDATE products SET status = ?, suspended_by = ?, suspended_reason = ? 
                    WHERE product_id = ? AND seller_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sssii', $newStatus, $suspendedBy, $suspendedReason, $id, $sellerId);
        } else {
            $sql = "UPDATE products SET status = ?, suspended_by = NULL, suspended_reason = NULL 
                    WHERE product_id = ? AND seller_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sii', $newStatus, $id, $sellerId);
        }

        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ============================================================
    // DELETE (D)
    // ============================================================

    /**
     * Soft delete product (mark as deleted).
     *
     * @param int $id Product ID
     * @param int $sellerId Seller ID
     * @return bool
     */
    public function delete(int $id, int $sellerId): bool
    {
        $sql = "UPDATE products SET status = 'deleted' WHERE product_id = ? AND seller_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $id, $sellerId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Delete all products for a seller (soft delete).
     *
     * @param int $sellerId Seller ID
     * @return bool
     */
    public function deleteBySeller(int $sellerId): bool
    {
        $stmt = $this->db->prepare("UPDATE products SET status = 'deleted' WHERE seller_id = ?");
        $stmt->bind_param('i', $sellerId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ============================================================
    // COUNTS / STATISTICS
    // ============================================================

    public function countAll(): int
    {
        $result = $this->db->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
        $row = $result->fetch_assoc();
        return (int)($row['count'] ?? 0);
    }

    public function countByUser(int $userId): int
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

    public function countUserProducts(int $userId): int
    {
        return $this->countByUser($userId);
    }

    public function countBySeller(int $sellerId, string $filter = 'all', string $search = ''): int
    {
        $sql = "SELECT COUNT(*) as total FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
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

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = (int) ($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        return $total;
    }

    public function countForAdmin(string $status = 'all', string $search = ''): int
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
    // STOCK MANAGEMENT (Data Access)
    // ============================================================

    /**
     * Get current stock quantity for a product.
     *
     * @param int $productId Product ID
     * @return int Current stock quantity
     */
    public function getStock(int $productId): int
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
}
