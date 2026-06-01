<?php

/**
 * ConsuTrade - Application Initialization
 * 
 * @author Kamogelo Phale
 * @version 2.0.0
 */

// Session settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.cookie_samesite', 'Strict');
}

require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/classes/Database.php';

// Database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Load repositories
require_once __DIR__ . '/php/classes/UserRepository.php';
require_once __DIR__ . '/php/classes/CategoryRepository.php';
require_once __DIR__ . '/php/classes/ProductRepository.php';
require_once __DIR__ . '/php/classes/ProductImageRepository.php';
require_once __DIR__ . '/php/classes/OrderRepository.php';
require_once __DIR__ . '/php/classes/CartRepository.php';
require_once __DIR__ . '/php/classes/ReviewRepository.php';

$userRepo = new UserRepository($conn);
$categoryRepo = new CategoryRepository($conn);
$productRepo = new ProductRepository($conn);
$productImageRepo = new ProductImageRepository($conn);
$orderRepo = new OrderRepository($conn);
$cartRepo = new CartRepository($conn);
$reviewRepo = new ReviewRepository($conn);

// Load domain models
require_once __DIR__ . '/php/classes/Product.php';
require_once __DIR__ . '/php/classes/OrderItem.php';
require_once __DIR__ . '/php/classes/Order.php';
require_once __DIR__ . '/php/classes/Cart.php';
require_once __DIR__ . '/php/classes/Transaction.php';
require_once __DIR__ . '/php/classes/Review.php';
require_once __DIR__ . '/php/classes/SellerVerification.php';
require_once __DIR__ . '/php/classes/ProductImage.php';
require_once __DIR__ . '/php/classes/Category.php';
require_once __DIR__ . '/php/classes/User.php';
require_once __DIR__ . '/php/classes/Buyer.php';
require_once __DIR__ . '/php/classes/Seller.php';
require_once __DIR__ . '/php/classes/Admin.php';

// Load Auth
require_once __DIR__ . '/php/classes/Auth.php';
$auth = new Auth($conn);

// Initialize session
$session = $auth->initSession();
$currentUser = $session['user'];
$isLoggedIn = $session['is_logged_in'];

// Global variables for endpoints
$GLOBALS['conn'] = $conn;
$GLOBALS['auth'] = $auth;
$GLOBALS['userRepo'] = $userRepo;
$GLOBALS['categoryRepo'] = $categoryRepo;
$GLOBALS['productRepo'] = $productRepo;
$GLOBALS['productImageRepo'] = $productImageRepo;
$GLOBALS['orderRepo'] = $orderRepo;
$GLOBALS['cartRepo'] = $cartRepo;
$GLOBALS['reviewRepo'] = $reviewRepo;
$GLOBALS['currentUser'] = $currentUser;
$GLOBALS['isLoggedIn'] = $isLoggedIn;
$GLOBALS['baseUrl'] = getBaseUrl();

// Prevent caching for authenticated pages
if ($isLoggedIn) {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}
