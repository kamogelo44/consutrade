<?php
/*
 * ConsuTrade - Edit Product Page
 * Author: Kamogelo Phale
 * 
 * Allows sellers to edit existing products
 * Uses same gallery interface as add-product.php
 */

require_once dirname(__DIR__) . '/init.php';

if (!$auth->isSeller()) {
    header('Location: login.php');
    exit;
}

$seller_id = $currentUser->getUserId();
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: my-products.php');
    exit;
}

$product = $productRepo->getProductObject($product_id);

if (!$product || $product->getSellerId() != $seller_id) {
    header('Location: my-products.php');
    exit;
}

$gallery_images = $productImageRepo->getByProductId($product_id);
$categories = $categoryRepo->getAll();

// Prepare existing images for JavaScript gallery (same format as add-product expects)
$existingImagesForJs = [];

// First, add the main image from product table
$mainImageUrl = $productRepo->getImageUrl($product->getImageUrl());
$existingImagesForJs[] = [
    'url' => $mainImageUrl,
    'is_primary' => true,
    'image_id' => 0
];

// Then add gallery images (I want to avoid duplicate if main image is also in gallery)
foreach ($gallery_images as $img) {
    $imgUrl = $productRepo->getImageUrl($img['image_url']);
    if ($imgUrl !== $mainImageUrl) {
        $existingImagesForJs[] = [
            'url' => $imgUrl,
            'is_primary' => (bool)$img['is_primary'],
            'image_id' => $img['image_id']
        ];
    }
}

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
                <form id="edit-product-form" action="<?php echo $baseUrl; ?>php/endpoints/edit-product.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?php echo $product->getProductId(); ?>">

                    <div class="form-group">
                        <label>Product Title *</label>
                        <input type="text" name="title" required value="<?php echo htmlspecialchars($product->getTitle()); ?>">
                    </div>

                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category_id" required>
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
                            <label>Price (R) *</label>
                            <input type="number" name="price" step="0.01" min="0" required value="<?php echo $product->getPrice(); ?>">
                        </div>
                        <div class="form-group">
                            <label>Stock Quantity *</label>
                            <input type="number" name="stock_quantity" min="1" required value="<?php echo $product->getStockQuantity(); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Condition</label>
                            <select name="condition">
                                <option value="">Not Specified</option>
                                <option value="New" <?php echo $product->getCondition() == 'New' ? 'selected' : ''; ?>>New</option>
                                <option value="Like New" <?php echo $product->getCondition() == 'Like New' ? 'selected' : ''; ?>>Like New</option>
                                <option value="Good" <?php echo $product->getCondition() == 'Good' ? 'selected' : ''; ?>>Good</option>
                                <option value="Fair" <?php echo $product->getCondition() == 'Fair' ? 'selected' : ''; ?>>Fair</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" value="<?php echo htmlspecialchars($product->getLocation()); ?>" placeholder="e.g., Johannesburg, Cape Town">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description *</label>
                        <textarea name="description" rows="5" required><?php echo htmlspecialchars($product->getDescription()); ?></textarea>
                    </div>

                    <!-- Unified Image Gallery (same as add-product.php) -->
                    <div class="form-group">
                        <label>Product Images (Max 4 total)</label>
                        <input type="file" name="new_product_images[]" accept="image/*" multiple onchange="addNewImagesToGallery(this)">
                        <small>Click on any image to set it as the main product photo. Click × to delete. You can add up to <?php echo 4 - count($existingImagesForJs); ?> new images.</small>

                        <!-- Gallery Container -->
                        <div id="image-gallery-container" style="margin-top: var(--spacing-lg);">
                            <div class="main-image-container">
                                <img id="gallery-main-preview" src="" alt="Main preview">
                            </div>
                            <div id="gallery-thumbnails" style="display: flex; gap: var(--spacing-md); flex-wrap: wrap; margin-top: var(--spacing-md); justify-content: center;">
                            </div>
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
        // Existing images passed from PHP
        var existingImages = <?php echo json_encode($existingImagesForJs); ?>;
        var newImageFiles = [];
        var imagesToDelete = [];
        var allDisplayImages = [];

        // Initialize gallery with existing images
        function initGallery() {
            allDisplayImages = [];
            for (var i = 0; i < existingImages.length; i++) {
                allDisplayImages.push({
                    url: existingImages[i].url,
                    is_primary: existingImages[i].is_primary,
                    image_id: existingImages[i].image_id,
                    source: 'existing'
                });
            }

            // Ensure at least one primary
            var hasPrimary = false;
            for (var i = 0; i < allDisplayImages.length; i++) {
                if (allDisplayImages[i].is_primary) hasPrimary = true;
            }
            if (!hasPrimary && allDisplayImages.length > 0) {
                allDisplayImages[0].is_primary = true;
            }

            renderGallery();
        }

        // Render thumbnails and main preview
        function renderGallery() {
            var $container = $('#gallery-thumbnails');
            $container.empty();

            for (var i = 0; i < allDisplayImages.length; i++) {
                var img = allDisplayImages[i];
                var isPrimary = img.is_primary;
                var borderColor = isPrimary ? 'var(--primary-color)' : 'var(--border-light)';
                var label = isPrimary ? 'Main' : 'Gallery';
                var imgSrc = img.source === 'existing' ? img.url : (img.previewUrl || '');

                var $thumb = $(
                    '<div class="gallery-thumb" data-index="' + i + '" style="cursor: pointer; width: 80px; text-align: center; position: relative;">' +
                    '<img src="' + imgSrc + '" style="width: 100%; height: 80px; object-fit: cover; border-radius: var(--radius-md); border: 2px solid ' + borderColor + ';">' +
                    '<div style="font-size: 10px; margin-top: 4px;">' + label + '</div>' +
                    '<div class="remove-image-btn" onclick="event.stopPropagation(); removeImage(' + i + ')">×</div>' +
                    '</div>'
                );
                $container.append($thumb);
            }

            // Find and display primary image
            for (var i = 0; i < allDisplayImages.length; i++) {
                if (allDisplayImages[i].is_primary) {
                    var primaryImg = allDisplayImages[i];
                    var src = primaryImg.source === 'existing' ? primaryImg.url : (primaryImg.previewUrl || '');
                    $('#gallery-main-preview').attr('src', src);
                    break;
                }
            }

            // Attach click handlers
            $('.gallery-thumb').off('click').on('click', function() {
                var index = $(this).data('index');
                for (var i = 0; i < allDisplayImages.length; i++) {
                    allDisplayImages[i].is_primary = (i === index);
                }
                renderGallery();
            });
        }

        // Remove image from gallery
        function removeImage(index) {
            var img = allDisplayImages[index];
            if (img.source === 'existing' && img.image_id > 0) {
                imagesToDelete.push(img.image_id);
            }
            allDisplayImages.splice(index, 1);
            if (allDisplayImages.length === 0) {
                $('#gallery-thumbnails').empty();
                $('#gallery-main-preview').attr('src', '');
                return;
            }
            // Ensure at least one primary
            var hasPrimary = false;
            for (var i = 0; i < allDisplayImages.length; i++) {
                if (allDisplayImages[i].is_primary) hasPrimary = true;
            }
            if (!hasPrimary) allDisplayImages[0].is_primary = true;
            renderGallery();
        }

        // Add new images from file input
        function addNewImagesToGallery(input) {
            var files = Array.from(input.files);
            var available = 4 - allDisplayImages.length;
            if (files.length > available) {
                alert('You can only add ' + available + ' more images (max 4 total).');
                files = files.slice(0, available);
            }

            for (var i = 0; i < files.length; i++) {
                var previewUrl = URL.createObjectURL(files[i]);
                allDisplayImages.push({
                    file: files[i],
                    previewUrl: previewUrl,
                    is_primary: false,
                    source: 'new'
                });
            }

            // If no primary, set first as primary
            var hasPrimary = false;
            for (var i = 0; i < allDisplayImages.length; i++) {
                if (allDisplayImages[i].is_primary) hasPrimary = true;
            }
            if (!hasPrimary && allDisplayImages.length > 0) {
                allDisplayImages[0].is_primary = true;
            }

            renderGallery();
            input.value = '';
        }

        // Prepare form data before submit
        function prepareSubmit() {
            var orderData = [];
            for (var i = 0; i < allDisplayImages.length; i++) {
                var img = allDisplayImages[i];
                orderData.push({
                    is_primary: img.is_primary,
                    image_id: img.image_id || 0,
                    is_new: img.source === 'new'
                });
            }
            $('<input type="hidden" name="image_order" value=\'' + JSON.stringify(orderData) + '\'>').appendTo('#edit-product-form');
            if (imagesToDelete.length > 0) {
                $('<input type="hidden" name="delete_images" value=\'' + JSON.stringify(imagesToDelete) + '\'>').appendTo('#edit-product-form');
            }
            return true;
        }

        $(document).ready(function() {
            initGallery();
            $('#edit-product-form').on('submit', prepareSubmit);
        });
    </script>
</body>

</html>