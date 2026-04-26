<?php
/*
 * ConsuTrade - Helper Functions
 * Author: Kamogelo Phale
 * 
 * Centralized helper functions to avoid duplication
 */

// ========== SESSION CONFIGURATION ==========
// Set session cookie parameters BEFORE any session starts
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');
ini_set('session.cookie_lifetime', 0);
ini_set('session.gc_maxlifetime', 7200);

// Get base URL
function getBaseUrl() {
    return "/www/consutrade/";
}

/**
 * Start the appropriate session based on context
 * Only call this ONCE at the beginning of each page
 * 
 * @param string $context 'user' for main website, 'admin' for admin dashboard, 'seller' for seller dashboard
 */
function startSession($context = 'user') {
    // Only set session name if no session is active yet
    if (session_status() === PHP_SESSION_NONE) {
        if ($context === 'admin') {
            session_name('CONSUTRADE_ADMIN_SESSION');
        } elseif ($context === 'seller') {
            session_name('CONSUTRADE_SELLER_SESSION');
        } else {
            session_name('CONSUTRADE_USER_SESSION');
        }
        session_start();
    }
}

/**
 * Get current session name without starting a new session
 * 
 * @return string|null Current session name or null
 */
function getCurrentSessionName() {
    return session_name() ?: null;
}

/**
 * Destroy session based on context
 * 
 * @param string $context 'user' for main website, 'admin' for admin dashboard, 'seller' for seller dashboard
 */
function destroySession($context = 'user') {
    // Set session name before starting
    if ($context === 'admin') {
        session_name('CONSUTRADE_ADMIN_SESSION');
    } elseif ($context === 'seller') {
        session_name('CONSUTRADE_SELLER_SESSION');
    } else {
        session_name('CONSUTRADE_USER_SESSION');
    }
    
    // Start session if not already active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION = array();
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

/**
 * Check if user is logged in (main website - ONLY buyers)
 * 
 * @return bool
 */
function isUserLoggedIn() {
    // Check if we're in the correct session context
    if (session_status() === PHP_SESSION_ACTIVE && session_name() !== 'CONSUTRADE_USER_SESSION') {
        return false;
    }
    
    // Only start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_name('CONSUTRADE_USER_SESSION');
        session_start();
    }
    
    return isset($_SESSION['logged_in']) && 
           $_SESSION['logged_in'] === true && 
           isset($_SESSION['role']) && 
           $_SESSION['role'] === 'buyer';
}

/**
 * Check if admin is logged in to dashboard
 * 
 * @return bool
 */
function isAdminLoggedIn() {
    // Check if we're in the correct session context
    if (session_status() === PHP_SESSION_ACTIVE && session_name() !== 'CONSUTRADE_ADMIN_SESSION') {
        return false;
    }
    
    // Only start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_name('CONSUTRADE_ADMIN_SESSION');
        session_start();
    }
    
    return isset($_SESSION['logged_in']) && 
           $_SESSION['logged_in'] === true && 
           isset($_SESSION['role']) && 
           $_SESSION['role'] === 'admin';
}

/**
 * Check if seller is logged in to dashboard
 * 
 * @return bool
 */
function isSellerLoggedIn() {
    // Check if we're in the correct session context
    if (session_status() === PHP_SESSION_ACTIVE && session_name() !== 'CONSUTRADE_SELLER_SESSION') {
        return false;
    }
    
    // Only start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_name('CONSUTRADE_SELLER_SESSION');
        session_start();
    }
    
    return isset($_SESSION['logged_in']) && 
           $_SESSION['logged_in'] === true && 
           isset($_SESSION['role']) && 
           $_SESSION['role'] === 'seller';
}

/**
 * Get current user session data (main website)
 * 
 * @return array|null
 */
function getCurrentUser() {
    if (!isUserLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}

/**
 * Get current admin session data
 * 
 * @return array|null
 */
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}

/**
 * Get current seller session data
 * 
 * @return array|null
 */
function getCurrentSeller() {
    if (!isSellerLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
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

// Get user profile image URL with fallback
function getUserProfileImage($profile_image) {
    $baseUrl = getBaseUrl();
    
    if (!empty($profile_image) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $profile_image)) {
        return $baseUrl . $profile_image;
    }
    return $baseUrl . 'images/icons/profile-svgrepo-com.svg';
}

// Get seller by ID
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

// Get user by ID (any role)
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

// Format date nicely
function formatDate($date) {
    if (!$date) return 'N/A';
    return date('M Y', strtotime($date));
}

// Get seller rating
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

// ========== PRODUCT IMAGE FUNCTIONS ==========

/**
 * Convert uploaded image to WebP format
 * 
 * @param array $file The uploaded file from $_FILES
 * @param int $seller_id The seller's ID
 * @param string $product_title The product title for filename
 * @return string|false The path to the WebP image or false on failure
 */
function convertToWebP($file, $seller_id, $product_title) {
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/uploads/products/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $timestamp = time();
    $safe_title = preg_replace('/[^a-zA-Z0-9_-]/', '_', $product_title);
    $safe_title = substr($safe_title, 0, 50);
    $filename = $seller_id . '_' . $timestamp . '_' . $safe_title . '.webp';
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
 * Authenticate user and start session (Main website - buyers only)
 * 
 * @param mysqli $conn Database connection
 * @param string $email User email
 * @param string $password User password
 * @return array|false User data on success, false on failure
 */
function authenticateUser($conn, $email, $password) {
    $sql = "SELECT user_id, full_name, email, password, role FROM users WHERE email = ? AND role = 'buyer'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $stmt->close();
            return $user;
        }
    }
    
    $stmt->close();
    return false;
}

/**
 * Authenticate admin for dashboard login
 * 
 * @param mysqli $conn Database connection
 * @param string $email User email
 * @param string $password User password
 * @return array|false User data on success, false on failure
 */
function authenticateAdmin($conn, $email, $password) {
    $sql = "SELECT user_id, full_name, email, password, role FROM users WHERE email = ? AND role = 'admin'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $stmt->close();
            return $user;
        }
    }
    
    $stmt->close();
    return false;
}

/**
 * Authenticate seller for dashboard login
 * 
 * @param mysqli $conn Database connection
 * @param string $email User email
 * @param string $password User password
 * @return array|false User data on success, false on failure
 */
function authenticateSeller($conn, $email, $password) {
    $sql = "SELECT user_id, full_name, email, password, role FROM users WHERE email = ? AND role = 'seller'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $stmt->close();
            return $user;
        }
    }
    
    $stmt->close();
    return false;
}

/**
 * Start user session after successful authentication (Main website)
 * 
 * @param array $user User data from authenticateUser()
 */
function startUserSession($user) {
    startSession('user');
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
}

/**
 * Start admin session after successful authentication
 * 
 * @param array $user User data from authenticateAdmin()
 */
function startAdminSession($user) {
    // Close any existing session first
    if (session_status() !== PHP_SESSION_NONE) {
        session_write_close();
    }
    
    session_name('CONSUTRADE_ADMIN_SESSION');
    session_start();
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    session_write_close();
}

/**
 * Start seller session after successful authentication
 * 
 * @param array $user User data from authenticateSeller()
 */
function startSellerSession($user) {
    // Close any existing session first
    if (session_status() !== PHP_SESSION_NONE) {
        session_write_close();
    }
    
    session_name('CONSUTRADE_SELLER_SESSION');
    session_start();
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    session_write_close();
}
?>