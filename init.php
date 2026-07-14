<?php

/**
 * ConsuTrade - Application Initialization
 * 
 * This file bootstraps the entire application.
 * It should be included at the top of every page.
 *
 * @author Kamogelo Phale
 * @version 2.4.0
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
// PHPMailer
// ============================================================
require_once __DIR__ . '/php/lib/PHPMailer/Exception.php';
require_once __DIR__ . '/php/lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/php/lib/PHPMailer/SMTP.php';

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

if (isset($_SERVER['PHP_AUTH_USER']) && !$auth->isLoggedIn()) {
    $email = $_SERVER['PHP_AUTH_USER'];
    $user = $userRepo->findByEmail($email);

    if ($user && $user->canLogin()) {
        $auth->loginWithHtaccessUser($user);
        $currentUser = $user;
    }
}

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

$adminService = new AdminService(
    $conn,
    $userRepo,
    $userService
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
// RATE LIMITER
// ============================================================
require_once __DIR__ . '/php/classes/core/RateLimiter.php';
$rateLimiter = new RateLimiter($conn);
$GLOBALS['rateLimiter'] = $rateLimiter;

// ============================================================
// SESSION & USER
// ============================================================
$currentUser = $auth->getCurrentUser();
$isLoggedIn = $auth->isLoggedIn();
$currentUserRole = $auth->getCurrentUserRole();
$baseUrl = getBaseUrl();

// ============================================================
// SESSION TIMEOUT CHECK
// ============================================================
if ($isLoggedIn) {
    if (!$auth->checkSessionTimeout()) {
        if (!headers_sent()) {
            header('Location: ' . $baseUrl . 'index.php?timeout=1');
            exit;
        }
    }
}

// Include translations
require_once __DIR__ . '/includes/translations.php';

// ============================================================
// AUTO-TRANSLATE ALL OUTPUT
// ============================================================

/**
 * Auto-translate the entire page output
 * Replaces English text with the selected language
 */
function autoTranslatePage($content)
{
    $lang = getCurrentLanguage();

    // Skip if English (no translation needed)
    if ($lang === 'en') {
        return $content;
    }

    $translations = getTranslations()[$lang] ?? [];
    $english = getTranslations()['en'] ?? [];

    // Build translation map
    $map = [];
    foreach ($english as $key => $value) {
        if (isset($translations[$key]) && $translations[$key] !== $value) {
            $map[$value] = $translations[$key];
        }
    }

    // Sort by length (longest first) to avoid partial matches
    uksort($map, function ($a, $b) {
        return strlen($b) - strlen($a);
    });

    // Replace text in HTML
    $search = array_keys($map);
    $replace = array_values($map);

    // Only translate text content (not inside HTML tags)
    return preg_replace_callback('/>([^<]*)</', function ($matches) use ($search, $replace) {
        $text = $matches[1];
        // Don't translate empty text or if it's numbers
        if (trim($text) === '' || is_numeric(trim($text))) {
            return '>' . $text . '<';
        }
        $text = str_replace($search, $replace, $text);
        return '>' . $text . '<';
    }, $content);
}

// Start output buffering
ob_start();

// Auto-translate when page finishes
register_shutdown_function(function () {
    $content = ob_get_clean();
    echo autoTranslatePage($content);
});

// ============================================================
// DAILY MAINTENANCE
// ============================================================
$cleanupKey = 'last_cleanup_' . date('Y-m-d');
if (!isset($_SESSION[$cleanupKey])) {
    require_once __DIR__ . '/php/cron/cleanup-orders.php';
    $_SESSION[$cleanupKey] = true;
}

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

/**
 * Apply rate limiting to endpoint.
 * Exits with 429 if limit exceeded.
 */
function rateLimit(string $endpoint, int $maxRequests, int $windowSeconds): void
{
    global $rateLimiter;

    $result = $rateLimiter->check($endpoint, $maxRequests, $windowSeconds);

    if (!$result['allowed']) {
        $rateLimiter->sendRateLimitResponse($result['retry_after']);
        exit;
    }

    header('X-RateLimit-Limit: ' . $maxRequests);
    header('X-RateLimit-Remaining: ' . $result['remaining']);
    header('X-RateLimit-Reset: ' . (time() + $result['retry_after']));
}

// Cache control headers for logged-in users
if ($isLoggedIn) {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}
