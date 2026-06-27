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

// Session settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.cookie_samesite', 'Lax');
}

require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/classes/core/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// ============================================================
// LOAD REPOSITORIES
// ============================================================
require_once __DIR__ . '/php/classes/repositories/UserRepository.php';
require_once __DIR__ . '/php/classes/repositories/CategoryRepository.php';
require_once __DIR__ . '/php/classes/repositories/ProductRepository.php';
require_once __DIR__ . '/php/classes/repositories/ProductImageRepository.php';
require_once __DIR__ . '/php/classes/repositories/OrderRepository.php';
require_once __DIR__ . '/php/classes/repositories/CartRepository.php';
require_once __DIR__ . '/php/classes/repositories/ReviewRepository.php';
require_once __DIR__ . '/php/classes/repositories/TransactionRepository.php';
require_once __DIR__ . '/php/classes/repositories/ReportRepository.php';

// ============================================================
// LOAD DOMAIN MODELS
// ============================================================
require_once __DIR__ . '/php/classes/domain/Product.php';
require_once __DIR__ . '/php/classes/domain/OrderItem.php';
require_once __DIR__ . '/php/classes/domain/Order.php';
require_once __DIR__ . '/php/classes/domain/Cart.php';
require_once __DIR__ . '/php/classes/domain/Transaction.php';
require_once __DIR__ . '/php/classes/domain/Review.php';
require_once __DIR__ . '/php/classes/domain/SellerVerification.php';
require_once __DIR__ . '/php/classes/domain/ProductImage.php';
require_once __DIR__ . '/php/classes/domain/Category.php';
require_once __DIR__ . '/php/classes/domain/User.php';
require_once __DIR__ . '/php/classes/domain/Buyer.php';
require_once __DIR__ . '/php/classes/domain/Seller.php';
require_once __DIR__ . '/php/classes/domain/Admin.php';
require_once __DIR__ . '/php/classes/domain/Report.php';

// ============================================================
// LOAD SERVICES
// ============================================================
require_once __DIR__ . '/php/classes/services/ProductImageService.php';
require_once __DIR__ . '/php/classes/services/PayFastService.php';
require_once __DIR__ . '/php/classes/services/PaymentStatusService.php';
require_once __DIR__ . '/php/classes/services/CartService.php';
require_once __DIR__ . '/php/classes/services/OrderService.php';
require_once __DIR__ . '/php/classes/services/ProductService.php';
require_once __DIR__ . '/php/classes/services/UserService.php';
require_once __DIR__ . '/php/classes/services/AdminService.php';

// ============================================================
// LOAD CORE
// ============================================================
require_once __DIR__ . '/php/classes/core/Auth.php';

// ============================================================
// CREATE REPOSITORY INSTANCES
// ============================================================
$userRepo = new UserRepository($conn);
$categoryRepo = new CategoryRepository($conn);

// ProductImageService is now available because we loaded services first
$productImageService = new ProductImageService();
$productRepo = new ProductRepository($conn, $productImageService);
$productImageRepo = new ProductImageRepository($conn);
$orderRepo = new OrderRepository($conn);
$cartRepo = new CartRepository($conn);
$reviewRepo = new ReviewRepository($conn);
$transactionRepo = new TransactionRepository($conn);
$reportRepo = new ReportRepository($conn, $productRepo);

// ============================================================
// CREATE SERVICE INSTANCES
// ============================================================
$auth = new Auth($conn, $userRepo);

// UserService
$userService = new UserService(
    $conn,
    $userRepo,
    $cartRepo,
    $orderRepo,
    $productRepo,
    $reviewRepo,
    $reportRepo,
    $auth
);

// AdminService
$adminService = new AdminService(
    $conn,
    $userRepo
);

$payfastService = new PayFastService(
    $conn,
    $orderRepo,
    $cartRepo,
    $transactionRepo
);

$paymentStatusService = new PaymentStatusService(
    $orderRepo,
    $transactionRepo
);

$cartService = new CartService(
    $conn,
    $productRepo,
    $orderRepo,
    $transactionRepo
);

$orderService = new OrderService(
    $conn,
    $orderRepo,
    $productRepo,
    $transactionRepo
);

$productService = new ProductService($productRepo);

// ============================================================
// SESSION & USER
// ============================================================
$currentUser = $auth->getCurrentUser();
$isLoggedIn = $auth->isLoggedIn();
$currentUserRole = $auth->getCurrentUserRole();
$baseUrl = getBaseUrl();

// ============================================================
// GLOBAL VARIABLES
// ============================================================
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
$GLOBALS['paymentStatusService'] = $paymentStatusService;
$GLOBALS['cartService'] = $cartService;
$GLOBALS['orderService'] = $orderService;
$GLOBALS['productService'] = $productService;
$GLOBALS['userService'] = $userService;
$GLOBALS['adminService'] = $adminService;
$GLOBALS['currentUser'] = $currentUser;
$GLOBALS['currentUserRole'] = $currentUserRole;
$GLOBALS['isLoggedIn'] = $isLoggedIn;
$GLOBALS['baseUrl'] = $baseUrl;

// Cache control headers for logged-in users
if ($isLoggedIn) {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}
