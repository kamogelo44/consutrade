<?php
/*
 * ConsuTrade - Helper Functions
 * Author: Kamogelo Phale
 * 
 * Centralized helper functions to avoid duplication
 * NOTE: Session/auth functions are now in auth.php
 */

// ========== PRODUCT IMAGE FUNCTIONS ==========

/**
 * Get product image URL
 * 
 * @param string $image_path The stored image path
 * @return string The full URL to the image
 */
function getProductImageUrl($image_path) {
    $baseUrl = getBaseUrl();
    
    if (empty($image_path)) {
        return $baseUrl . 'images/default-product.png';
    }
    
    $full_path = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $image_path;
    if (file_exists($full_path)) {
        return $baseUrl . $image_path;
    }
    
    return $baseUrl . 'images/default-product.png';
}

/**
 * Convert uploaded image to WebP format
 * 
 * @param array $file The uploaded file from $_FILES
 * @param int $seller_id The seller's ID
 * @param string $product_title The product title for filename
 * @param string $prefix Optional prefix for filename
 * @return string|false The path to the WebP image or false on failure
 */
function convertToWebP($file, $seller_id, $product_title, $prefix = 'main') {
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/uploads/products/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $timestamp = time();
    $safe_title = preg_replace('/[^a-zA-Z0-9_-]/', '_', $product_title);
    $safe_title = substr($safe_title, 0, 50);
    $filename = $seller_id . '_' . $timestamp . '_' . $prefix . '_' . $safe_title . '.webp';
    $destination = $upload_dir . $filename;
    
    $source = $file['tmp_name'];
    $image_info = getimagesize($source);
    
    if (!$image_info) {
        return false;
    }
    
    switch ($image_info['mime']) {
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
    
    $orig_width = imagesx($image);
    $orig_height = imagesy($image);
    $max_dimension = 1200;
    
    if ($orig_width > $max_dimension || $orig_height > $max_dimension) {
        $ratio = min($max_dimension / $orig_width, $max_dimension / $orig_height);
        $new_width = round($orig_width * $ratio);
        $new_height = round($orig_height * $ratio);
        
        $resized = imagecreatetruecolor($new_width, $new_height);
        
        if ($image_info['mime'] === 'image/png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
        }
        
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
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
 * Delete product image file
 * 
 * @param string $image_path The path to the image (relative to root)
 * @return bool True if deleted or doesn't exist, false on failure
 */
function deleteProductImage($image_path) {
    if (empty($image_path)) {
        return true;
    }
    
    $full_path = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $image_path;
    
    if (file_exists($full_path)) {
        return unlink($full_path);
    }
    
    return true;
}

// ========== USER HELPER FUNCTIONS ==========

/**
 * Get user by ID (any role)
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array|null User data or null if not found
 */
function getUserById($conn, $user_id) {
    $sql = "SELECT user_id, full_name, email, role, location, phone, created_at, id_verified, profile_image 
            FROM users 
            WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

/**
 * Get seller by ID
 * 
 * @param mysqli $conn Database connection
 * @param int $seller_id Seller ID
 * @return array|null Seller data or null if not found
 */
function getSellerById($conn, $seller_id) {
    $sql = "SELECT user_id, full_name, email, location, phone, created_at, id_verified, profile_image 
            FROM users 
            WHERE user_id = ? AND role = 'seller'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $seller = $result->fetch_assoc();
    $stmt->close();
    return $seller;
}

/**
 * Get user profile image URL with fallback
 * 
 * @param string|null $profile_image Profile image path
 * @return string Full URL to profile image
 */
function getUserProfileImage($profile_image) {
    $baseUrl = getBaseUrl();
    
    if (!empty($profile_image) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $profile_image)) {
        return $baseUrl . $profile_image;
    }
    return $baseUrl . 'images/icons/profile-svgrepo-com.svg';
}

/**
 * Get seller rating
 * 
 * @param mysqli $conn Database connection
 * @param int $seller_id Seller ID
 * @return array ['avg_rating' => float, 'review_count' => int]
 */
function getSellerRating($conn, $seller_id) {
    $rating_sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE seller_id = ?";
    $rating_stmt = $conn->prepare($rating_sql);
    if ($rating_stmt) {
        $rating_stmt->bind_param('i', $seller_id);
        $rating_stmt->execute();
        $rating_result = $rating_stmt->get_result();
        $rating_data = $rating_result->fetch_assoc();
        $rating_stmt->close();
        return [
            'avg_rating' => round($rating_data['avg_rating'] ?? 0, 1),
            'review_count' => (int)($rating_data['review_count'] ?? 0)
        ];
    }
    return ['avg_rating' => 0, 'review_count' => 0];
}

// ========== FORMATTING FUNCTIONS ==========

/**
 * Format date nicely
 * 
 * @param string|null $date Date string
 * @return string Formatted date or 'N/A'
 */
function formatDate($date) {
    if (!$date) return 'N/A';
    return date('M Y', strtotime($date));
}

/**
 * Get full absolute URL for PayFast
 * 
 * @param string $path Relative path (e.g., 'order-confirmation.php')
 * @return string Full absolute URL
 */
function getAbsoluteUrl($path) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/www/consutrade/' . ltrim($path, '/');
}

// ========== CART HELPER FUNCTIONS ==========

/**
 * Get cart items for a user
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array Cart items with product details
 */
function getCartItems($conn, $user_id) {
    $sql = "SELECT c.cart_id, c.quantity, c.added_at,
                   p.product_id, p.title, p.price, p.image_url, p.seller_id, p.stock_quantity,
                   u.full_name as seller_name
            FROM cart c
            JOIN products p ON c.product_id = p.product_id
            JOIN users u ON p.seller_id = u.user_id
            WHERE c.user_id = ?
            ORDER BY c.added_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $row['subtotal'] = $row['price'] * $row['quantity'];
        $row['image_url'] = getProductImageUrl($row['image_url']);
        $items[] = $row;
    }
    $stmt->close();
    
    return $items;
}

/**
 * Calculate cart totals
 * 
 * @param array $cart_items Array of cart items
 * @return array ['subtotal' => float, 'delivery_fee' => float, 'total' => float]
 */
function calculateCartTotals($cart_items) {
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    // Free delivery over R500, otherwise R50
    $delivery_fee = ($subtotal > 0 && $subtotal < 500) ? 50 : 0;
    $total = $subtotal + $delivery_fee;
    
    return [
        'subtotal' => $subtotal,
        'delivery_fee' => $delivery_fee,
        'total' => $total
    ];
}

/**
 * Update cart item quantity
 * 
 * @param mysqli $conn Database connection
 * @param int $cart_id Cart item ID
 * @param int $user_id User ID (for verification)
 * @param int $quantity New quantity
 * @return bool Success or failure
 */
function updateCartQuantity($conn, $cart_id, $user_id, $quantity) {
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
    $stmt->bind_param('iii', $quantity, $cart_id, $user_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Remove item from cart by product_id
 * 
 * @param mysqli $conn Database connection
 * @param int $product_id Product ID
 * @param int $user_id User ID (for verification)
 * @return bool Success or failure
 */
function removeCartItemByProductId($conn, $product_id, $user_id) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE product_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $product_id, $user_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Get cart count (total quantity) for a user
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return int Total number of items in cart (sum of quantities)
 */
function getCartCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = (int)($row['total'] ?? 0);
    $stmt->close();
    return $count;
}

/**
 * Get product stock quantity
 * 
 * @param mysqli $conn Database connection
 * @param int $product_id Product ID
 * @return int Stock quantity
 */
function getProductStock($conn, $product_id) {
    $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stock = (int)($row['stock_quantity'] ?? 0);
    $stmt->close();
    return $stock;
}

/**
 * Update product stock after purchase
 * 
 * @param mysqli $conn Database connection
 * @param int $product_id Product ID
 * @param int $quantity_ordered Quantity ordered
 * @return bool Success or failure
 */
function decreaseProductStock($conn, $product_id, $quantity_ordered) {
    $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?");
    $stmt->bind_param('iii', $quantity_ordered, $product_id, $quantity_ordered);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Restore stock when order is cancelled
 * 
 * @param mysqli $conn Database connection
 * @param int $order_id Order ID
 * @return bool Success or failure
 */
function restoreOrderStock($conn, $order_id) {
    $items_sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param('i', $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $success = true;
    while ($item = $items_result->fetch_assoc()) {
        $stock_sql = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?";
        $stock_stmt = $conn->prepare($stock_sql);
        $stock_stmt->bind_param('ii', $item['quantity'], $item['product_id']);
        if (!$stock_stmt->execute()) {
            $success = false;
        }
        $stock_stmt->close();
    }
    $items_stmt->close();
    
    return $success;
}

// ========== ORDER HELPER FUNCTIONS ==========

/**
 * Get buyer orders with filters
 * 
 * @param mysqli $conn Database connection
 * @param int $buyer_id The buyer's user ID
 * @param string $status_filter Filter by status (pending, processing, completed, cancelled, all)
 * @param string $search_term Search by order ID or seller name
 * @return array Array of orders
 */
function getBuyerOrders($conn, $buyer_id, $status_filter = 'all', $search_term = '') {
    $sql = "SELECT o.order_id, o.total_price, o.status, o.created_at,
            u.full_name as seller_name, u.user_id as seller_id,
            (SELECT GROUP_CONCAT(DISTINCT p.title SEPARATOR ', ') 
             FROM order_items oi 
             JOIN products p ON oi.product_id = p.product_id 
             WHERE oi.order_id = o.order_id) as product_names,
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
            FROM orders o
            JOIN users u ON o.seller_id = u.user_id
            WHERE o.buyer_id = ?";
    
    $params = [$buyer_id];
    $types = "i";
    
    if ($status_filter !== 'all') {
        $sql .= " AND o.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
    
    if (!empty($search_term)) {
        $sql .= " AND (u.full_name LIKE ? OR o.order_id LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }
    
    $sql .= " ORDER BY 
                CASE o.status 
                    WHEN 'pending' THEN 1
                    WHEN 'processing' THEN 2
                    WHEN 'shipped' THEN 3
                    WHEN 'completed' THEN 4
                    WHEN 'cancelled' THEN 5
                    ELSE 6
                END,
                o.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
    
    return $orders;
}

/**
 * Get seller orders with filters (for seller dashboard)
 * 
 * @param mysqli $conn Database connection
 * @param int $seller_id The seller's user ID
 * @param string $status_filter Filter by status (all, pending, processing, shipped, completed, cancelled)
 * @param string $search_term Search by order ID or buyer name
 * @return array Array of orders
 */
function getSellerOrders($conn, $seller_id, $status_filter = 'all', $search_term = '') {
    $sql = "SELECT o.order_id, o.total_price, o.status, o.created_at,
            u.full_name as buyer_name, u.email as buyer_email, u.user_id as buyer_id,
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
            FROM orders o
            JOIN users u ON o.buyer_id = u.user_id
            WHERE o.seller_id = ?";
    
    $params = [$seller_id];
    $types = "i";
    
    if ($status_filter !== 'all') {
        $sql .= " AND o.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
    
    if (!empty($search_term)) {
        $sql .= " AND (u.full_name LIKE ? OR o.order_id LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }
    
    $sql .= " ORDER BY 
                CASE o.status 
                    WHEN 'pending' THEN 1
                    WHEN 'processing' THEN 2
                    WHEN 'shipped' THEN 3
                    WHEN 'completed' THEN 4
                    WHEN 'cancelled' THEN 5
                    ELSE 6
                END,
                o.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
    
    return $orders;
}

/**
 * Get single order details with items
 * 
 * @param mysqli $conn Database connection
 * @param int $order_id Order ID
 * @param int $user_id User ID (for verification)
 * @param string $role User role (buyer or seller)
 * @return array|null Order details or null if not found
 */
function getOrderDetails($conn, $order_id, $user_id, $role) {
    $id_column = ($role === 'buyer') ? 'buyer_id' : 'seller_id';
    
    $sql = "SELECT o.order_id, o.total_price, o.status, o.created_at, o.payment_id,
            u.full_name as other_party_name
            FROM orders o
            JOIN users u ON " . ($role === 'buyer' ? "o.seller_id = u.user_id" : "o.buyer_id = u.user_id") . "
            WHERE o.order_id = ? AND o.$id_column = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $items_sql = "SELECT oi.quantity, oi.price, p.title as product_name, p.image_url
                      FROM order_items oi
                      JOIN products p ON oi.product_id = p.product_id
                      WHERE oi.order_id = ?";
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->bind_param('i', $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        $items = [];
        while ($item = $items_result->fetch_assoc()) {
            $items[] = $item;
        }
        $items_stmt->close();
        
        $row['items'] = $items;
        $stmt->close();
        return $row;
    }
    
    $stmt->close();
    return null;
}

/**
 * Update order status (for sellers)
 * 
 * @param mysqli $conn Database connection
 * @param int $order_id Order ID
 * @param int $seller_id Seller ID (for verification)
 * @param string $new_status New status (processing, shipped, completed, cancelled)
 * @return array ['success' => bool, 'message' => string]
 */
function updateSellerOrderStatus($conn, $order_id, $seller_id, $new_status) {
    $allowed_statuses = ['processing', 'shipped', 'completed', 'cancelled'];
    if (!in_array($new_status, $allowed_statuses)) {
        return ['success' => false, 'message' => 'Invalid status.'];
    }
    
    $check_sql = "SELECT status FROM orders WHERE order_id = ? AND seller_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ii', $order_id, $seller_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        $check_stmt->close();
        return ['success' => false, 'message' => 'Order not found or does not belong to you.'];
    }
    
    $order = $check_result->fetch_assoc();
    $current_status = $order['status'];
    $check_stmt->close();
    
    $valid_transitions = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => []
    ];
    
    if (!in_array($new_status, $valid_transitions[$current_status])) {
        return ['success' => false, 'message' => "Cannot change status from '$current_status' to '$new_status'."];
    }
    
    $update_sql = "UPDATE orders SET status = ? WHERE order_id = ? AND seller_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('sii', $new_status, $order_id, $seller_id);
    
    if ($update_stmt->execute()) {
        $update_stmt->close();
        
        if ($new_status === 'cancelled') {
            restoreOrderStock($conn, $order_id);
        }
        
        return ['success' => true, 'message' => "Order status updated to " . ucfirst($new_status)];
    }
    
    $update_stmt->close();
    return ['success' => false, 'message' => 'Failed to update order status.'];
}

/**
 * Cancel an order (for buyers)
 * 
 * @param mysqli $conn Database connection
 * @param int $order_id Order ID
 * @param int $buyer_id Buyer ID (for verification)
 * @return bool Success or failure
 */
function cancelBuyerOrder($conn, $order_id, $buyer_id) {
    $check_sql = "SELECT status FROM orders WHERE order_id = ? AND buyer_id = ? AND status = 'pending'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ii', $order_id, $buyer_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        $check_stmt->close();
        return false;
    }
    $check_stmt->close();
    
    $update_sql = "UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND buyer_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('ii', $order_id, $buyer_id);
    $result = $update_stmt->execute();
    $update_stmt->close();
    
    return $result;
}

// ========== PRODUCT MANAGEMENT HELPER FUNCTIONS ==========

/**
 * Get seller products with filters
 * 
 * @param mysqli $conn Database connection
 * @param int $seller_id Seller ID
 * @param string $status_filter Filter by status (all, active, suspended)
 * @param string $search_term Search by product name or ID
 * @param int $limit Maximum products to return (0 for all)
 * @param int $offset Pagination offset
 * @return array Array of products
 */
function getSellerProducts($conn, $seller_id, $status_filter = 'all', $search_term = '', $limit = 0, $offset = 0) {
    $sql = "SELECT p.product_id, p.title, p.price, p.image_url, p.status, p.stock_quantity, p.created_at,
            c.category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE p.seller_id = ? AND p.status != 'deleted'";
    
    $params = [$seller_id];
    $types = "i";
    
    if ($status_filter !== 'all') {
        $sql .= " AND p.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
    
    if (!empty($search_term)) {
        $sql .= " AND (p.title LIKE ? OR p.product_id LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    if ($limit > 0) {
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => (int)$row['product_id'],
            'title' => $row['title'],
            'price' => (float)$row['price'],
            'image' => $row['image_url'],
            'status' => $row['status'],
            'stock_quantity' => (int)($row['stock_quantity'] ?? 1),
            'created_at' => $row['created_at'],
            'category_name' => $row['category_name'] ?? 'General'
        ];
    }
    $stmt->close();
    
    return $products;
}

/**
 * Update product status (activate/suspend)
 * 
 * @param mysqli $conn Database connection
 * @param int $product_id Product ID
 * @param int $seller_id Seller ID (for verification)
 * @param string $action 'activate' or 'suspend'
 * @return array ['success' => bool, 'message' => string]
 */
function updateProductStatus($conn, $product_id, $seller_id, $action) {
    $check_sql = "SELECT product_id, status FROM products WHERE product_id = ? AND seller_id = ? AND status != 'deleted'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ii', $product_id, $seller_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        $check_stmt->close();
        return ['success' => false, 'message' => 'Product not found or does not belong to you.'];
    }
    
    $product = $check_result->fetch_assoc();
    $check_stmt->close();
    
    $new_status = ($action === 'activate') ? 'active' : 'suspended';
    
    if ($product['status'] === $new_status) {
        $status_text = ($action === 'activate') ? 'active' : 'suspended';
        return ['success' => false, 'message' => "Product is already $status_text."];
    }
    
    $update_sql = "UPDATE products SET status = ? WHERE product_id = ? AND seller_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('sii', $new_status, $product_id, $seller_id);
    
    if ($update_stmt->execute()) {
        $update_stmt->close();
        $message = ($action === 'activate') ? 'Product activated successfully!' : 'Product suspended successfully!';
        return ['success' => true, 'message' => $message];
    }
    
    $update_stmt->close();
    return ['success' => false, 'message' => 'Failed to update product status.'];
}

/**
 * Delete product (soft delete)
 * 
 * @param mysqli $conn Database connection
 * @param int $product_id Product ID
 * @param int $seller_id Seller ID (for verification)
 * @return array ['success' => bool, 'message' => string]
 */
function deleteSellerProduct($conn, $product_id, $seller_id) {
    $check_sql = "SELECT product_id, image_url FROM products WHERE product_id = ? AND seller_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ii', $product_id, $seller_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        $check_stmt->close();
        return ['success' => false, 'message' => 'Product not found or does not belong to you.'];
    }
    
    $product = $check_result->fetch_assoc();
    $check_stmt->close();
    
    $delete_sql = "UPDATE products SET status = 'deleted' WHERE product_id = ? AND seller_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param('ii', $product_id, $seller_id);
    
    if ($delete_stmt->execute()) {
        $delete_stmt->close();
        
        if (!empty($product['image_url'])) {
            deleteProductImage($product['image_url']);
        }
        
        return ['success' => true, 'message' => 'Product deleted successfully.'];
    }
    
    $delete_stmt->close();
    return ['success' => false, 'message' => 'Failed to delete product.'];
}

/**
 * Get single product for editing
 * 
 * @param mysqli $conn Database connection
 * @param int $product_id Product ID
 * @param int $seller_id Seller ID (for verification)
 * @return array|null Product data or null if not found
 */
function getProductForEdit($conn, $product_id, $seller_id) {
    $sql = "SELECT p.product_id, p.title, p.description, p.price, p.stock_quantity, 
            p.condition, p.location, p.category_id, p.image_url, p.gallery_images, p.status
            FROM products p
            WHERE p.product_id = ? AND p.seller_id = ? AND p.status != 'deleted'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $product_id, $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $row['gallery_images'] = $row['gallery_images'] ? json_decode($row['gallery_images'], true) : [];
        $stmt->close();
        return $row;
    }
    
    $stmt->close();
    return null;
}

/**
 * Update product information
 * 
 * @param mysqli $conn Database connection
 * @param int $product_id Product ID
 * @param int $seller_id Seller ID (for verification)
 * @param array $data Product data (title, description, price, stock, etc.)
 * @return array ['success' => bool, 'message' => string]
 */
function updateSellerProduct($conn, $product_id, $seller_id, $data) {
    $sql = "UPDATE products SET 
            title = ?, 
            description = ?, 
            price = ?, 
            stock_quantity = ?,
            `condition` = ?,
            location = ?,
            category_id = ?
            WHERE product_id = ? AND seller_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssdissiii', 
        $data['title'], 
        $data['description'], 
        $data['price'], 
        $data['stock_quantity'],
        $data['condition'],
        $data['location'],
        $data['category_id'],
        $product_id, 
        $seller_id
    );
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Product updated successfully!'];
    }
    
    $stmt->close();
    return ['success' => false, 'message' => 'Failed to update product.'];
}

// ========== CHECKOUT HELPER FUNCTIONS ==========

/**
 * Verify all cart items have sufficient stock before checkout
 * 
 * @param mysqli $conn Database connection
 * @param array $cart_items Array of cart items
 * @return array Array of stock errors (empty if no errors)
 */
function verifyCartStock($conn, $cart_items) {
    $errors = [];
    
    foreach ($cart_items as $item) {
        $current_stock = getProductStock($conn, $item['product_id']);
        
        if ($current_stock < $item['quantity']) {
            $errors[] = $item['title'] . ': Only ' . $current_stock . ' available (you have ' . $item['quantity'] . ' in cart)';
        }
    }
    
    return $errors;
}

/**
 * Create orders for all sellers in cart
 * 
 * @param mysqli $conn Database connection
 * @param int $buyer_id The buyer's user ID
 * @param array $cart_items Array of cart items
 * @param array $seller_ids Array of unique seller IDs
 * @return array|null Array of order IDs on success, null on failure
 */
function createOrdersFromCart($conn, $buyer_id, $cart_items, $seller_ids) {
    $order_ids = [];
    $payment_id = time() . '_' . $buyer_id;
    
    foreach ($seller_ids as $seller_id) {
        $seller_subtotal = 0;
        foreach ($cart_items as $item) {
            if ($item['seller_id'] == $seller_id) {
                $seller_subtotal += $item['price'] * $item['quantity'];
            }
        }
        
        $seller_delivery = ($seller_subtotal > 0 && $seller_subtotal < 500) ? 50 : 0;
        $seller_total = $seller_subtotal + $seller_delivery;
        
        $order_sql = "INSERT INTO orders (buyer_id, seller_id, total_price, status, payment_id, created_at) 
                      VALUES (?, ?, ?, 'pending', ?, NOW())";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param('iids', $buyer_id, $seller_id, $seller_total, $payment_id);
        
        if (!$order_stmt->execute()) {
            return null;
        }
        
        $order_id = $order_stmt->insert_id;
        $order_ids[] = $order_id;
        
        foreach ($cart_items as $item) {
            if ($item['seller_id'] == $seller_id) {
                $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                             VALUES (?, ?, ?, ?)";
                $item_stmt = $conn->prepare($item_sql);
                $item_stmt->bind_param('iiid', $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $item_stmt->execute();
                $item_stmt->close();
            }
        }
        $order_stmt->close();
    }
    
    return ['order_ids' => $order_ids, 'payment_id' => $payment_id];
}

/**
 * Clear the user's cart after successful checkout
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return bool Success or failure
 */
function clearUserCart($conn, $user_id) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Prepare PayFast payment data
 * 
 * @param array $order_info Contains order_ids, payment_id, total, user info
 * @param string $baseUrl Base URL of the site
 * @return array PayFast form data
 */
function preparePayFastData($order_info, $baseUrl) {
    return [
        'merchant_id' => PAYFAST_MERCHANT_ID,
        'merchant_key' => PAYFAST_MERCHANT_KEY,
        'return_url' => getAbsoluteUrl('order-confirmation.php'),
        'cancel_url' => getAbsoluteUrl('cart.php'),
        'notify_url' => getAbsoluteUrl('php/payfast-notify.php'),
        'm_payment_id' => $order_info['payment_id'],
        'amount' => number_format($order_info['total'], 2, '.', ''),
        'item_name' => 'ConsuTrade Order #' . $order_info['primary_order_id'],
        'item_description' => 'Order from ConsuTrade',
        'name_first' => $order_info['buyer_name'],
        'email_address' => $order_info['buyer_email']
    ];
}

/**
 * Process checkout and create orders
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param array $cart_items Cart items
 * @return array|null Checkout result with orders and payment info, or null on failure
 */
function processCheckout($conn, $user_id, $cart_items) {
    $stock_errors = verifyCartStock($conn, $cart_items);
    if (!empty($stock_errors)) {
        return ['success' => false, 'errors' => $stock_errors];
    }
    
    $seller_ids = array_unique(array_column($cart_items, 'seller_id'));
    
    $conn->begin_transaction();
    
    try {
        $order_result = createOrdersFromCart($conn, $user_id, $cart_items, $seller_ids);
        
        if (!$order_result) {
            throw new Exception('Failed to create orders');
        }
        
        clearUserCart($conn, $user_id);
        
        $conn->commit();
        
        return [
            'success' => true,
            'order_ids' => $order_result['order_ids'],
            'payment_id' => $order_result['payment_id'],
            'primary_order_id' => $order_result['order_ids'][0]
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'errors' => ['Failed to process checkout']];
    }
}

/**
 * Get user checkout info (name, email, phone)
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array|null User info or null if not found
 */
function getUserCheckoutInfo($conn, $user_id) {
    $sql = "SELECT full_name, email, phone FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}
?>