<?php
/*
 * ConsuTrade - My Products (Seller)
 * Author: Kamogelo Phale
 * 
 * Displays seller's products with primary image from product_images table
 */

require_once dirname(__DIR__) . '/init.php';

if (!$auth->isSeller()) {
    header('Location: login.php');
    exit;
}
$seller_id = $user_id = $currentUser->getUserId();

// Handle status changes using ProductRepository
if (isset($_GET['action']) && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    $action = $_GET['action'];
    $result = $productRepo->updateProductStatus($product_id, $seller_id, $action);
    if ($result['success']) {
        $_SESSION['flash'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }
    header("Location: my-products.php");
    exit;
}

$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Get products using ProductRepository
$products = $productRepo->getSellerProducts($seller_id, $status, $search);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - ConsuTrade Seller</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/admin.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="seller-main-content">
        <div class="dashboard-content">
            <div class="page-header">
                <h1>My Products</h1>
                <p>Manage your product listings</p>
            </div>

            <?php if (isset($_SESSION['flash'])): ?>
                <div class="flash-message"><?php echo $_SESSION['flash'];
                                            unset($_SESSION['flash']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message"><?php echo $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="action-bar">
                <a href="add-product.php" class="add-product-btn">
                    <span>+</span> Add Product
                </a>

                <div class="filter-buttons">
                    <a href="?status=all" class="filter-btn <?php echo $status == 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?status=active" class="filter-btn <?php echo $status == 'active' ? 'active' : ''; ?>">Active</a>
                    <a href="?status=suspended" class="filter-btn <?php echo $status == 'suspended' ? 'active' : ''; ?>">Suspended</a>
                </div>

                <form method="GET" class="search-form">
                    <input type="hidden" name="status" value="<?php echo $status; ?>">
                    <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Go</button>
                    <?php if (!empty($search)): ?>
                        <a href="?status=<?php echo $status; ?>" class="clear-search">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <p>No products found.</p>
                        <a href="add-product.php" class="add-product-link">Add your first product →</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo $productRepo->getProductImageUrl($product['display_image'] ?? $product['image']); ?>" class="product-image" onerror="this.src='<?php echo $baseUrl; ?>images/default-product.png'">
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                                <p class="product-price">R <?php echo number_format($product['price'], 2); ?></p>
                                <p class="product-stock">Stock: <?php echo $product['stock_quantity']; ?></p>
                                <p class="product-status">Status: <span class="status-badge status-<?php echo $product['status']; ?>"><?php echo ucfirst($product['status']); ?></span></p>

                                <div class="btn-group">
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn-edit">Edit</a>
                                    <?php if ($product['status'] == 'active'): ?>
                                        <a href="?action=suspend&id=<?php echo $product['id']; ?>&status=<?php echo $status; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn-suspend" onclick="return confirm('Suspend this product? It will be hidden from buyers.')">Suspend</a>
                                    <?php else: ?>
                                        <a href="?action=activate&id=<?php echo $product['id']; ?>&status=<?php echo $status; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn-activate" onclick="return confirm('Activate this product? It will be visible to buyers.')">Activate</a>
                                    <?php endif; ?>
                                    <button class="btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>)">Delete</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';

        function deleteProduct(id) {
            if (confirm('Delete this product? This action cannot be undone.')) {
                $.ajax({
                    url: baseUrl + 'php/endpoints/delete-product.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        product_id: id
                    }),
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            showSuccessToast('Product deleted successfully');
                            location.reload();
                        } else {
                            showErrorToast(data.message || 'Error deleting product');
                        }
                    },
                    error: function() {
                        showErrorToast('Something went wrong');
                    }
                });
            }
        }
    </script>
    <script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>

</body>

</html>