<?php
/*
 * ConsuTrade - Central Authentication Handler
 * Author: Kamogelo Phale
 * 
 * SINGLE SOURCE OF TRUTH for all session and user management
 */

// Start session with consistent name if not already started
function initAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('CONSUTRADE_USER_SESSION');
        session_start();
    }
}

// Get current authenticated user
function getCurrentUser() {
    initAuth();
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}

// Check if user is logged in
function isLoggedIn() {
    $user = getCurrentUser();
    return $user !== null;
}

// Get user ID (convenience function)
function getCurrentUserId() {
    $user = getCurrentUser();
    return $user ? $user['user_id'] : null;
}

// Login user
function loginUser($user_id, $full_name, $email, $role) {
    initAuth();
    
    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user_id;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['logged_in'] = true;
    
    // Update cart count in session
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
}

// Logout user
function logoutUser() {
    initAuth();
    
    $_SESSION = array();
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

// Update cart count in session
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