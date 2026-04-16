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

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    
    // Soft delete - update status to 'deleted'
    $delete_sql = "UPDATE products SET status = 'deleted' WHERE product_id = ? AND seller_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param('ii', $product_id, $seller_id);
    
    if ($delete_stmt->execute()) {
        $success_message = 'Product deleted successfully!';
    } else {
        $error_message = 'Failed to delete product.';
    }
    $delete_stmt->close();
}

// Handle status update (activate/suspend)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    $new_status = ($action === 'activate') ? 'active' : 'suspended';
    
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

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$sql = "SELECT product_id, title as product_name, price, image_url, status, created_at, category_id
        FROM products 
        WHERE seller_id = ?";

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
              FROM products WHERE seller_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param('i', $seller_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - ConsuTrade Seller</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/header.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/login-signup.css">
    <link rel="stylesheet" href="css/my-products.css">
</head>
<body class="my-products-page">
    <?php include dirname(__DIR__) . '/header.php'; ?>

    <main>
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
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                <div class="stat-card active">
                    <div class="stat-value"><?php echo $stats['active']; ?></div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-card suspended">
                    <div class="stat-value"><?php echo $stats['suspended']; ?></div>
                    <div class="stat-label">Suspended</div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="action-bar">
                <a href="add-product.php" class="add-product-btn">
                    + Add New Product
                </a>
                
                <div class="filters">
                    <div class="status-filters">
                        <a href="?status=all" class="filter-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
                        <a href="?status=active" class="filter-btn <?php echo $status_filter === 'active' ? 'active' : ''; ?>">Active</a>
                        <a href="?status=suspended" class="filter-btn <?php echo $status_filter === 'suspended' ? 'active' : ''; ?>">Suspended</a>
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

            <!-- Products Table -->
            <div class="products-table-wrapper">
                <?php if (count($products) > 0): ?>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Listed Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td class="product-image-cell">
                                        <div class="product-thumb">
                                            <?php
                                            // Check if image_url exists and is valid
                                            $imagePath = '';
                                            if (!empty($product['image_url'])) {
                                                // Check if the image file actually exists on the server
                                                $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $product['image_url'];
                                                if (file_exists($fullPath)) {
                                                    $imagePath = $baseUrl . $product['image_url'];
                                                }
                                            }
                                            
                                            // If no valid image, use default PNG
                                            if (empty($imagePath)) {
                                                $imagePath = $baseUrl . 'images/default-product.png';
                                            }
                                            ?>
                                            <img src="<?php echo $imagePath; ?>" 
                                                alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                        </div>
                                    </td>
                                    <td class="product-name-cell">
                                        <?php echo htmlspecialchars($product['product_name']); ?>
                                    </td>
                                    <td class="product-price-cell">
                                        R <?php echo number_format($product['price'], 2); ?>
                                    </td>
                                    <td class="product-status-cell">
                                        <span class="status-badge status-<?php echo $product['status']; ?>">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                    <td class="product-date-cell">
                                        <?php echo date('d M Y', strtotime($product['created_at'])); ?>
                                    </td>
                                    <td class="product-actions-cell">
                                        <a href="edit-product.php?id=<?php echo $product['product_id']; ?>" class="action-btn edit-btn" title="Edit">
                                            <img src="<?php echo $baseUrl; ?>images/icons/edit-svgrepo-com.svg" width="16px" height="16px" alt="Edit">
                                        </a>
                                        
                                        <?php if ($product['status'] === 'active'): ?>
                                            <a href="?action=suspend&id=<?php echo $product['product_id']; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_term); ?>" 
                                               class="action-btn suspend-btn" 
                                               title="Suspend"
                                               onclick="return confirm('Are you sure you want to suspend this product? It will no longer be visible to buyers.');">
                                                <img src="<?php echo $baseUrl; ?>images/icons/hide-svgrepo-com.svg" width="16px" height="16px" alt="Suspend">
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=activate&id=<?php echo $product['product_id']; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_term); ?>" 
                                               class="action-btn activate-btn" 
                                               title="Activate"
                                               onclick="return confirm('Are you sure you want to activate this product? It will be visible to buyers.');">
                                                <img src="<?php echo $baseUrl; ?>images/icons/show-svgrepo-com.svg" width="16px" height="16px" alt="Activate">
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="?delete=<?php echo $product['product_id']; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_term); ?>" 
                                           class="action-btn delete-btn" 
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">
                                            <img src="<?php echo $baseUrl; ?>images/icons/delete-svgrepo-com.svg" width="16px" height="16px" alt="Delete">
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-products">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="64px" height="64px" alt="No products">
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

    <?php include dirname(__DIR__) . '/footer.php'; ?>
    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
</body>
</html>