<?php
/*
 * ConsuTrade - Add Product Page
 * Author: Kamogelo Phale
 * 
 * Allows sellers to add new products to the marketplace
 * Uses unified image gallery (first image = main, rest = gallery)
 */

require_once dirname(__DIR__) . '/init.php';
include dirname(__DIR__) . '/includes/session-vars.php';

// Use hasRole() instead of isSeller() for multi-role support
if (!$auth->hasRole('seller')) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// If user is logged in but active role is not seller, switch to seller
if (!$auth->isSeller()) {
    $auth->switchRole('seller');
}

$seller_id = $currentUser->getUserId();
$user_name = $currentUser->getFullName();
$profile_image = $currentUser->getProfileImageUrl();
$categories = $categoryRepo->findAll();

// Breadcrumb for subpage navigation
$breadcrumbItems = [
    ['url' => 'my-products.php', 'label' => 'My Products'],
    ['label' => 'Add New Product']
];

// Get flash messages from session
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Add New Product - ConsuTrade</title>

    <!-- CSS Imports -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-layout.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script src="<?php echo $baseUrl; ?>js/image-compressor.js"></script>
    <style>
        /* Tiny page-specific override - only if needed */
        .gallery-thumb {
            transition: all var(--transition-fast);
        }

        .gallery-thumb:hover {
            transform: translateY(-2px);
        }

        /* Compression Progress */
        #compression-progress {
            margin-top: var(--spacing-md);
        }

        #compression-progress .progress-bar {
            background: var(--gray-bg);
            border-radius: var(--radius-md);
            height: 20px;
            overflow: hidden;
        }

        #compression-progress .progress-bar .progress-fill {
            background: var(--primary-color);
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
            border-radius: var(--radius-md);
        }

        #compression-progress .progress-text {
            font-size: var(--font-sm);
            color: var(--gray-medium);
            margin-top: var(--spacing-xs);
        }
    </style>
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main-content">
        <div class="dashboard-content">
            <div class="page-header">
                <?php include dirname(__DIR__) . '/includes/breadcrumb.php'; ?>
                <h1>Add New Product</h1>
                <p>Fill in the details below to list your product</p>
            </div>

            <!-- Flash messages using global component -->
            <?php include dirname(__DIR__) . '/includes/flash-message.php'; ?>

            <div class="form-container">
                <form id="product-form" action="<?php echo $baseUrl; ?>php/endpoints/products/add-product.php" method="post" enctype="multipart/form-data">

                    <div class="form-group">
                        <label>Product Title *</label>
                        <input type="text" name="title" required>
                    </div>

                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Price (R) *</label>
                            <input type="number" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Stock Quantity *</label>
                            <input type="number" name="stock_quantity" value="1" min="1" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Condition</label>
                            <select name="condition">
                                <option value="">Not Specified</option>
                                <option value="New">New</option>
                                <option value="Like New">Like New</option>
                                <option value="Good">Good</option>
                                <option value="Fair">Fair</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" placeholder="e.g., Johannesburg, Cape Town">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description *</label>
                        <textarea name="description" rows="5" required placeholder="Describe your product in detail..."></textarea>
                    </div>

                    <!-- Unified Image Gallery -->
                    <div class="form-group">
                        <label>Product Images * (Max 4 total)</label>
                        <input type="file" name="product_images[]" accept="image/*" multiple required onchange="initImageGalleryFromInput(this)">
                        <small>Select 1-4 images. The first image becomes your main product photo shown in listings and cart. Click thumbnails to preview.</small>

                        <!-- Gallery Container - same layout as product details page -->
                        <div id="image-gallery-container" style="margin-top: var(--spacing-lg); display: none;">
                            <!-- Large preview area -->
                            <div class="main-image-container">
                                <img id="gallery-main-preview" src="" alt="Main preview">
                            </div>

                            <!-- Thumbnails area -->
                            <div id="gallery-thumbnails" style="display: flex; gap: var(--spacing-md); flex-wrap: wrap; margin-top: var(--spacing-md); justify-content: center;">
                            </div>
                        </div>

                        <!-- Compression Progress -->
                        <div id="compression-progress" style="display: none; margin-top: var(--spacing-md);">
                            <div class="progress-bar">
                                <div class="progress-fill" id="compression-progress-bar"></div>
                            </div>
                            <p class="progress-text" id="compression-progress-text">Compressing images...</p>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Publish Product</button>
                        <a href="my-products.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';

        $(document).ready(function() {
            $('#product-form').on('submit', function() {
                // Show progress if there are images selected
                var fileInput = $('input[name="product_images[]"]')[0];
                if (fileInput && fileInput.files && fileInput.files.length > 0) {
                    $('#compression-progress').show();
                    $('#compression-progress-text').text('Compressing ' + fileInput.files.length + ' images...');
                    $('#compression-progress-bar').css('width', '50%');
                }
                return true;
            });
        });
    </script>

</body>

</html>