<?php
/*
 * ConsuTrade - My Products (Seller)
 * Author: Kamogelo Phale
 * 
 * This page displays all products for the logged-in seller
 * Sellers can edit, delete, and manage their product listings
 */

session_start();

$baseUrl = "/www/consutrade/";

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Check if user is a seller
if ($_SESSION['role'] !== 'seller') {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

require_once dirname(__DIR__) . '/php/config.php';

$seller_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Set current page for active sidebar link
$current_page = 'products';

// Handle product deletion (soft delete)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    
    $check_sql = "SELECT product_id FROM products WHERE product_id = ? AND seller_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ii', $product_id, $seller_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $delete_sql = "UPDATE products SET status = 'deleted' WHERE product_id = ? AND seller_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param('ii', $product_id, $seller_id);
        
        if ($delete_stmt->execute()) {
            $success_message = 'Product deleted successfully!';
        } else {
            $error_message = 'Failed to delete product.';
        }
        $delete_stmt->close();
    } else {
        $error_message = 'Product not found or does not belong to you.';
    }
    $check_stmt->close();
}

// Handle status update (activate/suspend)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    $check_sql = "SELECT product_id, status FROM products WHERE product_id = ? AND seller_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ii', $product_id, $seller_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $product_data = $check_result->fetch_assoc();
        $new_status = ($action === 'activate') ? 'active' : 'suspended';
        
        if (($action === 'activate' && $product_data['status'] === 'active') ||
            ($action === 'suspend' && $product_data['status'] === 'suspended')) {
            $error_message = ($action === 'activate') ? 'Product is already active.' : 'Product is already suspended.';
        } else {
            $status_sql = "UPDATE products SET status = ? WHERE product_id = ? AND seller_id = ?";
            $status_stmt = $conn->prepare($status_sql);
            $status_stmt->bind_param('sii', $new_status, $product_id, $seller_id);
            
            if ($status_stmt->execute()) {
                $success_message = ($action === 'activate') ? 'Product activated successfully!' : 'Product suspended successfully!';
            } else {
                $error_message = 'Failed to update product status.';
            }
            $status_stmt->close();
        }
    } else {
        $error_message = 'Product not found or does not belong to you.';
    }
    $check_stmt->close();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$sql = "SELECT product_id, title as product_name, price, image_url, status, created_at, category_id
        FROM products 
        WHERE seller_id = ? AND status != 'deleted'";

$params = [$seller_id];
$types = "i";

if ($status_filter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search_term)) {
    $sql .= " AND (title LIKE ? OR product_id LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();

// Get product statistics
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended
              FROM products WHERE seller_id = ? AND status != 'deleted'";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param('i', $seller_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

$conn->close();

$filter_params = '';
if ($status_filter !== 'all') {
    $filter_params .= '&status=' . $status_filter;
}
if (!empty($search_term)) {
    $filter_params .= '&search=' . urlencode($search_term);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - ConsuTrade Seller</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/seller-dashboard.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/my-products.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
</head>
<body class="my-products-page seller-dashboard-page">

<?php include 'includes/seller-sidebar.php'; ?>

        <!-- Products Content -->
        <div class="products-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>My Products</h1>
                <p>Manage all your product listings</p>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Total">
                    </div>
                    <div class="stat-info">
                        <h3>Total Products</h3>
                        <p class="stat-number"><?php echo $stats['total']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" alt="Active">
                    </div>
                    <div class="stat-info">
                        <h3>Active</h3>
                        <p class="stat-number active"><?php echo $stats['active']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/hide-svgrepo-com.svg" alt="Suspended">
                    </div>
                    <div class="stat-info">
                        <h3>Suspended</h3>
                        <p class="stat-number suspended"><?php echo $stats['suspended']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="action-bar">
                <a href="add-product.php" class="add-product-btn">
                    <img src="<?php echo $baseUrl; ?>images/icons/add-svgrepo-com.svg" alt="Add">
                    Add New Product
                </a>
                
                <div class="filters">
                    <div class="status-filters">
                        <a href="?status=all<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" class="filter-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
                        <a href="?status=active<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" class="filter-btn <?php echo $status_filter === 'active' ? 'active' : ''; ?>">Active</a>
                        <a href="?status=suspended<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" class="filter-btn <?php echo $status_filter === 'suspended' ? 'active' : ''; ?>">Suspended</a>
                    </div>
                    
                    <div class="search-bar">
                        <form method="GET" action="">
                            <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                            <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_term); ?>">
                            <button type="submit">
                                <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="16px" height="16px" alt="Search">
                            </button>
                            <?php if (!empty($search_term)): ?>
                                <a href="?status=<?php echo $status_filter; ?>" class="clear-search">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="products-grid">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php
                                $imagePath = '';
                                if (!empty($product['image_url'])) {
                                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $product['image_url'];
                                    if (file_exists($fullPath)) {
                                        $imagePath = $baseUrl . $product['image_url'];
                                    }
                                }
                                if (empty($imagePath)) {
                                    $imagePath = $baseUrl . 'images/default-product.png';
                                }
                                ?>
                                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                <div class="product-status-badge status-<?php echo $product['status']; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </div>
                            </div>
                            <div class="product-details">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                <p class="product-price">R <?php echo number_format($product['price'], 2); ?></p>
                                <p class="product-date">Listed: <?php echo date('d M Y', strtotime($product['created_at'])); ?></p>
                                <div class="product-actions">
                                    <a href="edit-product.php?id=<?php echo $product['product_id']; ?><?php echo $filter_params; ?>" class="action-btn edit-btn" title="Edit">
                                        <img src="<?php echo $baseUrl; ?>images/icons/edit-svgrepo-com.svg" alt="Edit">
                                        <span>Edit</span>
                                    </a>
                                    
                                    <?php if ($product['status'] === 'active'): ?>
                                        <a href="?action=suspend&id=<?php echo $product['product_id']; ?><?php echo $filter_params; ?>" 
                                           class="action-btn suspend-btn" 
                                           title="Suspend"
                                           onclick="return confirm('Are you sure you want to suspend this product? It will no longer be visible to buyers.');">
                                            <img src="<?php echo $baseUrl; ?>images/icons/hide-svgrepo-com.svg" alt="Suspend">
                                            <span>Suspend</span>
                                        </a>
                                    <?php elseif ($product['status'] === 'suspended'): ?>
                                        <a href="?action=activate&id=<?php echo $product['product_id']; ?><?php echo $filter_params; ?>" 
                                           class="action-btn activate-btn" 
                                           title="Activate"
                                           onclick="return confirm('Are you sure you want to activate this product? It will be visible to buyers.');">
                                            <img src="<?php echo $baseUrl; ?>images/icons/show-svgrepo-com.svg" alt="Activate">
                                            <span>Activate</span>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="?delete=<?php echo $product['product_id']; ?><?php echo $filter_params; ?>" 
                                       class="action-btn delete-btn" 
                                       title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">
                                        <img src="<?php echo $baseUrl; ?>images/icons/delete-svgrepo-com.svg" alt="Delete">
                                        <span>Delete</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-products">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="No products">
                        <h3>No Products Found</h3>
                        <p><?php echo !empty($search_term) ? 'No products match your search criteria.' : 'You haven\'t listed any products yet.'; ?></p>
                        <?php if (!empty($search_term)): ?>
                            <a href="?status=<?php echo $status_filter; ?>" class="clear-btn">Clear Search</a>
                        <?php else: ?>
                            <a href="add-product.php" class="add-btn">Add Your First Product</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script src="<?php echo $baseUrl; ?>js/main.js"></script>
<script src="<?php echo $baseUrl; ?>admin/js/seller-dashboard.js"></script>
</body>
</html>