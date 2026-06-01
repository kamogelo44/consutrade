<?php
/*
 * ConsuTrade - Application Initialization
 * Included in every page
 * Author: Kamogelo Phale
 */

// Session settings MUST be set before any session starts
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_domain', '');
    ini_set('session.cookie_lifetime', 0);
    ini_set('session.gc_maxlifetime', 7200);
}

// Load required files
require_once __DIR__ . '/php/config.php';

// Load all class files
require_once __DIR__ . '/php/classes/Database.php';
require_once __DIR__ . '/php/classes/Auth.php';
require_once __DIR__ . '/php/classes/UserRepository.php';
require_once __DIR__ . '/php/classes/CategoryRepository.php';
require_once __DIR__ . '/php/classes/ProductRepository.php';
require_once __DIR__ . '/php/classes/ProductImageRepository.php';
require_once __DIR__ . '/php/classes/OrderRepository.php';
require_once __DIR__ . '/php/classes/CartRepository.php';
require_once __DIR__ . '/php/classes/ReviewRepository.php';

// Domain classes
require_once __DIR__ . '/php/classes/Product.php';
require_once __DIR__ . '/php/classes/OrderItem.php';
require_once __DIR__ . '/php/classes/Order.php';
require_once __DIR__ . '/php/classes/Cart.php';
require_once __DIR__ . '/php/classes/Transaction.php';
require_once __DIR__ . '/php/classes/Review.php';
require_once __DIR__ . '/php/classes/SellerVerification.php';
require_once __DIR__ . '/php/classes/ProductImage.php';
require_once __DIR__ . '/php/classes/Category.php';

// User hierarchy
require_once __DIR__ . '/php/classes/User.php';
require_once __DIR__ . '/php/classes/Buyer.php';
require_once __DIR__ . '/php/classes/Seller.php';
require_once __DIR__ . '/php/classes/Admin.php';
//
// ------------------------------------------------------------------
// Instantiation of shared services
// ------------------------------------------------------------------

$auth = new Auth($conn);

$categoryRepo = new CategoryRepository($conn);
$productRepo = new ProductRepository($conn);
$productImageRepo = new ProductImageRepository($conn);
$orderRepo   = new OrderRepository($conn);
$cartRepo    = new CartRepository($conn);
$reviewRepo  = new ReviewRepository($conn);
$userRepo    = new UserRepository($conn);

// ------------------------------------------------------------------
// Auto-detect and start appropriate session
// ------------------------------------------------------------------

$session_data = $auth->initAppSession();

// ------------------------------------------------------------------
// Backward-compatible global variables (existing pages use these)
// ------------------------------------------------------------------

$current_user    = $session_data['current_user'];
$is_logged_in    = $session_data['is_logged_in'];
$current_user_id = $session_data['current_user_id'];
$baseUrl         = getBaseUrl();

// ------------------------------------------------------------------
// Prevent browser caching for logged-in users
// ------------------------------------------------------------------
if ($is_logged_in) {
    // Prevent browser from caching authenticated pages
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// ------------------------------------------------------------------
// Create the current user object based on role
// ------------------------------------------------------------------

$currentUser = null;

if ($is_logged_in && $current_user) {
    $role = $current_user['role'] ?? '';

    switch ($role) {
        case 'buyer':
            $currentUser = new Buyer($current_user, $cartRepo, $orderRepo);
            $currentUser->refreshCartCount();
            break;

        case 'seller':
            // Load verification data if available
            $verification = null;
            $verSql = "SELECT * FROM seller_verification WHERE seller_id = ?";
            $verStmt = $conn->prepare($verSql);
            $verStmt->bind_param('i', $current_user_id);
            $verStmt->execute();
            $verResult = $verStmt->get_result();
            if ($verRow = $verResult->fetch_assoc()) {
                $verification = new SellerVerification($verRow);
            }
            $verStmt->close();

            $currentUser = new Seller($current_user, $productRepo, $orderRepo, $verification);
            break;

        case 'admin':
            $currentUser = new Admin($current_user, $conn);
            break;
    }
}
