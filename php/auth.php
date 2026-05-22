<?php
/*
 * ConsuTrade - Central Authentication Handler
 * Author: Kamogelo Phale
 * 
 * SINGLE SOURCE OF TRUTH for all session and user management
 */

// Start session with appropriate name based on context
function initAuth($context = 'user') {
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

// Start main website session (buyers)
function initUserAuth() {
    initAuth('user');
}

// Start admin session
function initAdminAuth() {
    initAuth('admin');
}

// Start seller session
function initSellerAuth() {
    initAuth('seller');
}

// AUTO-DETECT AND INITIALIZE APPROPRIATE SESSION BASED ON PAGE
function initAppSession() {
    $script_path = $_SERVER['SCRIPT_NAME'];
    
    // Admin login page - NO SESSION AT ALL
    if (strpos($script_path, 'admin/login.php') !== false) {
        return ['current_user' => null, 'is_logged_in' => false, 'current_user_id' => null];
    }
    
    // Check if this is a seller page (BEFORE checking /admin/)
    $is_seller_page = (
        strpos($script_path, 'seller-dashboard.php') !== false ||
        strpos($script_path, 'seller-profile.php') !== false ||
        strpos($script_path, 'admin/my-orders.php') !==false ||
        strpos($script_path, 'my-products.php') !== false ||
        strpos($script_path, 'add-product.php') !== false ||
        strpos($script_path, 'edit-product.php') !== false ||
        // Endpoints used by sellers
        strpos($script_path, 'get-seller-products.php') !== false ||
        strpos($script_path, 'get-seller-orders.php') !== false ||
        strpos($script_path, 'get-seller-recent-orders.php') !== false ||
        strpos($script_path, 'update-order-status.php') !== false ||
        strpos($script_path, 'delete-product.php') !== false
    );
    
    // Seller pages
    if ($is_seller_page) {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('CONSUTRADE_SELLER_SESSION');
            session_start();
        }
        $current_user = getCurrentSeller();
        return [
            'current_user' => $current_user,
            'is_logged_in' => isSellerLoggedIn(),
            'current_user_id' => $current_user['user_id'] ?? null
        ];
    }
    
    // Admin pages (excluding login and seller pages)
    if (strpos($script_path, '/admin/') !== false) {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('CONSUTRADE_ADMIN_SESSION');
            session_start();
        }
        $current_user = getCurrentAdmin();
        return [
            'current_user' => $current_user,
            'is_logged_in' => isAdminLoggedIn(),
            'current_user_id' => $current_user['user_id'] ?? null
        ];
    }
    
    // Main website - user session (buyers)
    if (session_status() === PHP_SESSION_NONE) {
        session_name('CONSUTRADE_USER_SESSION');
        session_start();
    }
    $current_user = getCurrentUser();
    $is_logged_in = isLoggedIn();
    $user_id = getCurrentUserId();
    
    if ($is_logged_in) {
        updateCartCount();
    }
    
    return [
        'current_user' => $current_user,
        'is_logged_in' => $is_logged_in,
        'current_user_id' => $user_id
    ];
}

// Get current authenticated user (main website - buyers)
function getCurrentUser() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $current_session_name = session_name();
        if ($current_session_name === 'CONSUTRADE_ADMIN_SESSION' || 
            $current_session_name === 'CONSUTRADE_SELLER_SESSION') {
            return null;
        }
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_name('CONSUTRADE_USER_SESSION');
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return null;
    }
    
    if (isset($_SESSION['role']) && $_SESSION['role'] !== 'buyer') {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}

// Check if user is logged in (main website - buyers)
function isLoggedIn() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $current_session_name = session_name();
        if ($current_session_name === 'CONSUTRADE_ADMIN_SESSION' || 
            $current_session_name === 'CONSUTRADE_SELLER_SESSION') {
            return false;
        }
    }
    
    $user = getCurrentUser();
    return $user !== null && $user['role'] === 'buyer';
}

// Get user ID (convenience function)
function getCurrentUserId() {
    $user = getCurrentUser();
    return $user ? $user['user_id'] : null;
}

// Login user (main website - buyers only)
function loginUser($user_id, $full_name, $email, $role) {
    if ($role !== 'buyer') {
        return false;
    }
    
    initUserAuth();
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user_id;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['logged_in'] = true;
    
    global $conn;
    if (isset($conn)) {
        $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $_SESSION['cart_count'] = (int)($row['total'] ?? 0);
        $stmt->close();
    }
    
    return true;
}

// Login admin
function loginAdmin($user_id, $full_name, $email, $role) {
    // Destroy any existing session
    if (session_status() !== PHP_SESSION_NONE) {
        session_destroy();
    }
    
    session_name('CONSUTRADE_ADMIN_SESSION');
    session_start();
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user_id;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['logged_in'] = true;
    
    return true;
}

// Login seller
function loginSeller($user_id, $full_name, $email, $role) {
    // Destroy any existing session
    if (session_status() !== PHP_SESSION_NONE) {
        session_destroy();
    }
    
    session_name('CONSUTRADE_SELLER_SESSION');
    session_start();
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user_id;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['logged_in'] = true;
    
    return true;
}

// Check if admin is logged in
function isAdminLoggedIn() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $current_session_name = session_name();
        if ($current_session_name !== 'CONSUTRADE_ADMIN_SESSION') {
            return false;
        }
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_name('CONSUTRADE_ADMIN_SESSION');
        session_start();
    }
    
    return isset($_SESSION['logged_in']) && 
           $_SESSION['logged_in'] === true && 
           isset($_SESSION['role']) && 
           $_SESSION['role'] === 'admin';
}

// Check if seller is logged in
function isSellerLoggedIn() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $current_session_name = session_name();
        if ($current_session_name !== 'CONSUTRADE_SELLER_SESSION') {
            return false;
        }
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_name('CONSUTRADE_SELLER_SESSION');
        session_start();
    }
    
    return isset($_SESSION['logged_in']) && 
           $_SESSION['logged_in'] === true && 
           isset($_SESSION['role']) && 
           $_SESSION['role'] === 'seller';
}

// Get current admin
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

// Get current seller
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

// Logout user (any role)
function logoutUser() {
    $session_name = session_name();
    session_unset();
    
    if (isset($_COOKIE[$session_name])) {
        setcookie($session_name, '', time() - 3600, '/');
    }
    
    session_destroy();
}

// Update cart count in session (main website only)
function updateCartCount() {
    $user_id = getCurrentUserId();
    if ($user_id) {
        global $conn;
        $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $_SESSION['cart_count'] = (int)($row['total'] ?? 0);
        $stmt->close();
    }
}
?>