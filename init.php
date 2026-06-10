<?php

/**
 * ConsuTrade - Application Initialization
 * 
 * This file bootstraps the entire application.
 * It should be included at the top of every page.
 *
 * @author Kamogelo Phale
 * @version 2.1.0
 */

// Session settings - use single session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.cookie_samesite', 'Lax');
}

require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/classes/Database.php';

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
require_once __DIR__ . '/php/classes/TransactionRepository.php';
require_once __DIR__ . '/php/classes/ReportRepository.php';

// Create repository instances
$userRepo = new UserRepository($conn);
$categoryRepo = new CategoryRepository($conn);
$productRepo = new ProductRepository($conn);
$productImageRepo = new ProductImageRepository($conn);
$orderRepo = new OrderRepository($conn);
$cartRepo = new CartRepository($conn);
$reviewRepo = new ReviewRepository($conn);
$transactionRepo = new TransactionRepository($conn);
$reportRepo = new ReportRepository($conn, $productRepo);

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
require_once __DIR__ . '/php/classes/Report.php';
require_once __DIR__ . '/php/classes/PayFastService.php';
require_once __DIR__ . '/php/classes/Auth.php';

// Create Auth instance (with UserRepository injected)
$auth = new Auth($conn, $userRepo);

// Create PayFastService instance (needed for checkout)
$payfastService = new PayFastService(
    $conn,
    $orderRepo,
    $productRepo,
    $cartRepo,
    $transactionRepo
);

// Start session and get user
$currentUser = $auth->getCurrentUser();
$isLoggedIn = $auth->isLoggedIn();
$currentUserRole = $auth->getCurrentUserRole();

$baseUrl = getBaseUrl();

// Set global variables for easy access in templates
$GLOBALS['conn'] = $conn;
$GLOBALS['db'] = $db;
$GLOBALS['auth'] = $auth;
$GLOBALS['userRepo'] = $userRepo;
$GLOBALS['categoryRepo'] = $categoryRepo;
$GLOBALS['productRepo'] = $productRepo;
$GLOBALS['productImageRepo'] = $productImageRepo;
$GLOBALS['orderRepo'] = $orderRepo;
$GLOBALS['cartRepo'] = $cartRepo;
$GLOBALS['reviewRepo'] = $reviewRepo;
$GLOBALS['transactionRepo'] = $transactionRepo;
$GLOBALS['reportRepo'] = $reportRepo;
$GLOBALS['payfastService'] = $payfastService;
$GLOBALS['currentUser'] = $currentUser;
$GLOBALS['currentUserRole'] = $currentUserRole;
$GLOBALS['isLoggedIn'] = $isLoggedIn;
$GLOBALS['baseUrl'] = $baseUrl;

// Set cache control headers for logged-in users to prevent back-button issues
if ($isLoggedIn) {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}
