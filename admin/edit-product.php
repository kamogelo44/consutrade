<?php
/*
 * ConsuTrade - Edit Product Page
 * Author: Kamogelo Phale
 * 
 * Allows sellers to edit existing products
 * Uses gallery functions from dashboard.js
 */

require_once dirname(__DIR__) . '/init.php';
// Check maintenance mode (one line!)
checkMaintenanceMode();

// Use hasRole() instead of isSeller() for multi-role support
if (!$auth->hasRole('seller')) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$seller_id = $currentUser->getUserId();

// Get the product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: my-products.php');
    exit;
}

// If user is logged in but active role is not seller, switch to seller
if (!$auth->isSeller()) {
    $auth->switchRole('seller');
    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $product_id);
    exit;
}

$product = $productRepo->findById($product_id);

if (!$product || $product->getSellerId() != $seller_id) {
    header('Location: my-products.php');
    exit;
}

$gallery_images = $productImageRepo->findByProductId($product_id);
$categories = $categoryRepo->findAll();

// Prepare existing images for JavaScript gallery
$existingImagesForJs = [];
$defaultImageUrl = $baseUrl . 'images/default-product.png';

// Check if product has a real main image
$mainImageUrl = $product->getImageUrl();
$isDefaultImage = strpos($mainImageUrl, 'default-product.png') !== false;

// Add real gallery images (skip duplicates and default)
foreach ($gallery_images as $img) {
    $imgUrl = $productRepo->getImageUrl($img['image_url']);
    $isDefault = strpos($imgUrl, 'default-product.png') !== false;

    if (!$isDefault && $imgUrl !== $mainImageUrl && $imgUrl !== $defaultImageUrl) {
        $existingImagesForJs[] = [
            'url' => $imgUrl,
            'is_primary' => (bool)$img['is_primary'],
            'image_id' => $img['image_id']
        ];
    }
}

// Set the main image to the first image if none is marked primary
$hasPrimary = false;
foreach ($existingImagesForJs as $img) {
    if ($img['is_primary']) {
        $hasPrimary = true;
        break;
    }
}
if (!$hasPrimary && !empty($existingImagesForJs)) {
    $existingImagesForJs[0]['is_primary'] = true;
}

// Calculate available slots for new images
$realImageCount = count($existingImagesForJs);
$availableSlots = 4 - $realImageCount;
if ($availableSlots < 0) $availableSlots = 0;

// Breadcrumb
$breadcrumbItems = [
    ['url' => 'my-products.php', 'label' => 'My Products'],
    ['label' => 'Edit Product']
];

$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-layout.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script src="<?php echo $baseUrl; ?>js/image-compressor.js"></script>
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            border: 1px solid var(--border-light);
        }

        .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .form-group label {
            display: block;
            font-weight: var(--font-semibold);
            margin-bottom: var(--spacing-sm);
            color: var(--dark-bg);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: var(--font-md);
            transition: all var(--transition-fast);
            background: var(--white);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 0, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group small {
            display: block;
            margin-top: var(--spacing-xs);
            font-size: var(--font-xs);
            color: var(--gray-medium);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-md);
        }

        .form-actions {
            margin-top: var(--spacing-xl);
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        .btn-submit {
            background: var(--primary-color);
            color: var(--white);
            padding: 12px 24px;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: var(--font-bold);
            transition: all var(--transition-fast);
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: var(--gray-bg-light);
            color: var(--gray-dark);
            padding: 12px 24px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            text-decoration: none;
            transition: all var(--transition-fast);
            display: inline-block;
            text-align: center;
        }

        .btn-cancel:hover {
            background: var(--border-light);
            transform: translateY(-2px);
        }

        .main-image-container {
            background: var(--gray-bg-light);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
            text-align: center;
            margin-bottom: var(--spacing-md);
        }

        .main-image-container img {
            width: 100%;
            max-width: 400px;
            height: 300px;
            object-fit: cover;
            border-radius: var(--radius-lg);
        }

        #gallery-thumbnails {
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
            margin-top: var(--spacing-md);
            justify-content: center;
        }

        .gallery-thumb {
            cursor: pointer;
            width: 80px;
            text-align: center;
            position: relative;
            transition: all var(--transition-fast);
        }

        .gallery-thumb:hover {
            transform: translateY(-2px);
        }

        .gallery-thumb img {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: var(--radius-md);
            border: 2px solid var(--border-light);
            background: var(--gray-bg);
        }

        .gallery-thumb .remove-image-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--error);
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 12px;
            text-align: center;
            line-height: 20px;
            cursor: pointer;
            z-index: 10;
            font-weight: var(--font-bold);
        }

        .gallery-thumb .remove-image-btn:hover {
            background: var(--error-dark);
            transform: scale(1.1);
        }

        .gallery-thumb .gallery-label {
            font-size: 10px;
            margin-top: 4px;
            color: var(--gray-medium);
        }

        .gallery-thumb .gallery-label.main {
            color: var(--primary-color);
            font-weight: var(--font-bold);
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

        @media (max-width: 768px) {
            .form-container {
                padding: var(--spacing-md);
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .main-image-container img {
                height: 200px;
            }

            .gallery-thumb {
                width: 60px;
            }

            .gallery-thumb img {
                height: 60px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-submit,
            .btn-cancel {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .form-container {
                padding: var(--spacing-sm);
            }

            .main-image-container img {
                height: 150px;
            }

            .gallery-thumb {
                width: 50px;
            }

            .gallery-thumb img {
                height: 50px;
            }

            .gallery-thumb .remove-image-btn {
                width: 18px;
                height: 18px;
                font-size: 10px;
                line-height: 16px;
                top: -6px;
                right: -6px;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main-content">
        <div class="dashboard-content">
            <div class="page-header">
                <?php include dirname(__DIR__) . '/includes/breadcrumb.php'; ?>
                <h1>Edit Product</h1>
                <p>Update your product information</p>
            </div>

            <?php include dirname(__DIR__) . '/includes/flash-message.php'; ?>

            <div class="form-container">
                <form id="edit-product-form" action="<?php echo $baseUrl; ?>php/endpoints/products/edit-product.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?php echo $product->getProductId(); ?>">

                    <div class="form-group">
                        <label for="title">Product Title *</label>
                        <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($product->getTitle()); ?>">
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $product->getCategoryId() ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price (R) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required value="<?php echo $product->getPrice(); ?>">
                        </div>
                        <div class="form-group">
                            <label for="stock_quantity">Stock Quantity *</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" min="1" required value="<?php echo $product->getStockQuantity(); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="condition">Condition</label>
                            <select id="condition" name="condition">
                                <option value="">Not Specified</option>
                                <option value="New" <?php echo $product->getCondition() == 'New' ? 'selected' : ''; ?>>New</option>
                                <option value="Like New" <?php echo $product->getCondition() == 'Like New' ? 'selected' : ''; ?>>Like New</option>
                                <option value="Good" <?php echo $product->getCondition() == 'Good' ? 'selected' : ''; ?>>Good</option>
                                <option value="Fair" <?php echo $product->getCondition() == 'Fair' ? 'selected' : ''; ?>>Fair</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($product->getLocation()); ?>" placeholder="e.g., Johannesburg, Cape Town">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($product->getDescription()); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Product Images (Max 4 total)</label>
                        <input type="file" name="new_product_images[]" accept="image/*" multiple onchange="addNewImagesToGallery(this)">
                        <small>Click on any image to set it as the main product photo. Click × to delete. <span id="image-counter">You can add up to <?php echo $availableSlots; ?> new images.</span></small>

                        <div id="image-gallery-container" style="margin-top: var(--spacing-lg);">
                            <div class="main-image-container">
                                <img id="gallery-main-preview" src="" alt="Main preview">
                            </div>
                            <div id="gallery-thumbnails"></div>
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
                        <button type="submit" class="btn-submit">Save Changes</button>
                        <a href="my-products.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';
        var existingImages = <?php echo json_encode($existingImagesForJs); ?>;

        $(document).ready(function() {
            initImageGalleryFromExisting(existingImages);

            $('#edit-product-form').on('submit', function() {
                // Show progress if there are new images
                if (newImageFiles && newImageFiles.length > 0) {
                    $('#compression-progress').show();
                    $('#compression-progress-text').text('Uploading ' + newImageFiles.length + ' images...');
                    $('#compression-progress-bar').css('width', '50%');
                }
                return prepareEditFormData();
            });
        });
    </script>
</body>

</html>