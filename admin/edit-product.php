<?php
/*
 * ConsuTrade - Edit Product Page
 * Author: Kamogelo Phale
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

// Breadcrumb for subpage navigation
$breadcrumbItems = [
    ['url' => 'admin/my-products.php', 'label' => 'My Products'],
    ['label' => 'Edit Product']
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Edit Product - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <style>
        .admin-main-content {
            margin-left: 280px;
            padding: var(--spacing-xl);
            min-height: 100vh;
            background: var(--gray-bg);
            transition: margin-left var(--transition-normal);
        }

        .dashboard-content {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: var(--spacing-xl);
        }

        .page-header h1 {
            font-size: var(--font-2xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-xs);
            color: var(--dark-bg);
        }

        .page-header p {
            color: var(--gray-medium);
        }

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
            padding: 10px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: var(--font-md);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 0, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-md);
        }

        .form-group small {
            display: block;
            margin-top: var(--spacing-xs);
            font-size: var(--font-xs);
            color: var(--gray-medium);
        }

        .gallery-grid {
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
            margin-top: var(--spacing-md);
        }

        .gallery-item {
            position: relative;
            width: 100px;
            height: 100px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            overflow: hidden;
            background: var(--gray-bg);
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .gallery-item .remove-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            background: var(--error);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            text-align: center;
            line-height: 18px;
            font-size: 12px;
            text-decoration: none;
            cursor: pointer;
            z-index: 1;
        }

        .gallery-item .remove-btn:hover {
            background: var(--error-dark);
            transform: scale(1.1);
        }

        .primary-badge {
            position: absolute;
            bottom: 4px;
            left: 4px;
            background: var(--primary-color);
            color: white;
            padding: 2px 6px;
            border-radius: var(--radius-sm);
            font-size: 10px;
            font-weight: var(--font-medium);
            z-index: 1;
        }

        .set-primary-btn {
            position: absolute;
            bottom: 4px;
            left: 4px;
            background: var(--primary-color);
            color: white;
            padding: 2px 6px;
            border-radius: var(--radius-sm);
            font-size: 10px;
            text-decoration: none;
            cursor: pointer;
            z-index: 1;
        }

        .set-primary-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.02);
        }

        .current-image {
            margin-bottom: var(--spacing-md);
            padding: var(--spacing-md);
            background: var(--gray-bg-light);
            border-radius: var(--radius-md);
        }

        .current-image img {
            max-width: 150px;
            border-radius: var(--radius-md);
        }

        .current-image p {
            font-size: var(--font-sm);
            color: var(--gray-medium);
            margin-top: var(--spacing-sm);
        }

        .gallery-section {
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-lg);
            border-top: 1px solid var(--border-light);
        }

        .gallery-section>label {
            display: block;
            font-weight: var(--font-semibold);
            margin-bottom: var(--spacing-sm);
            color: var(--dark-bg);
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
            margin-left: var(--spacing-sm);
            transition: all var(--transition-fast);
            display: inline-block;
            text-align: center;
        }

        .btn-cancel:hover {
            background: var(--border-light);
        }

        .error-msg {
            background: var(--error-light);
            color: var(--error);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            border-left: 4px solid var(--error);
        }

        .success-msg {
            background: var(--success-light);
            color: var(--success);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            border-left: 4px solid var(--success);
        }

        @media (max-width: 1024px) {
            .admin-main-content {
                margin-left: 0;
                width: 100%;
                padding: var(--spacing-md);
                padding-top: 70px;
            }
        }

        @media (max-width: 768px) {
            .admin-main-content {
                padding: var(--spacing-md);
                padding-top: 70px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: var(--spacing-md);
            }

            .form-container {
                padding: var(--spacing-lg);
            }

            .gallery-grid {
                justify-content: center;
            }

            .btn-cancel {
                margin-left: 0;
                margin-top: var(--spacing-sm);
            }

            .form-actions {
                display: flex;
                flex-direction: column;
                gap: var(--spacing-sm);
            }
        }

        @media (max-width: 480px) {
            .admin-main-content {
                padding: var(--spacing-sm);
                padding-top: 60px;
            }

            .form-container {
                padding: var(--spacing-md);
            }

            .gallery-item {
                width: 80px;
                height: 80px;
            }

            .primary-badge,
            .set-primary-btn {
                font-size: 8px;
                padding: 1px 4px;
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

            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-msg"><?php echo htmlspecialchars($_SESSION['error']);
                                        unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-msg"><?php echo htmlspecialchars($_SESSION['success']);
                                            unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form action="<?php echo $baseUrl; ?>php/endpoints/edit-product.php" method="post" enctype="multipart/form-data">
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

                    <div class="form-group">
                        <label>Change Main Image (Optional)</label>
                        <input type="file" name="main_image" accept="image/*">
                        <?php if (!empty($product->getImageUrl())): ?>
                            <div class="current-image">
                                <img src="<?php echo $productRepo->getProductImageUrl($product->getImageUrl()); ?>" alt="Current main image">
                                <p>Leave empty to keep current image</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="gallery-grid" id="existing-gallery">
                        <?php foreach ($gallery_images as $img): ?>
                            <div class="gallery-item" data-image-id="<?php echo $img['image_id']; ?>">
                                <img src="<?php echo $productRepo->getProductImageUrl($img['image_url']); ?>" alt="Gallery image">
                                <a href="javascript:void(0)" class="remove-btn" onclick="removeGalleryImage(<?php echo $img['image_id']; ?>, <?php echo $product->getProductId(); ?>)">×</a>
                                <?php if ($img['is_primary']): ?>
                                    <span class="primary-badge">Primary</span>
                                <?php else: ?>
                                    <a href="javascript:void(0)" class="set-primary-btn" onclick="setPrimaryImage(<?php echo $img['image_id']; ?>, <?php echo $product->getProductId(); ?>)">Set as Primary</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top: var(--spacing-xl); display: flex; gap: var(--spacing-sm)">
                        <button type="submit" class="btn-submit">Save Changes</button>
                        <a href="my-products.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function removeGalleryImage(imageId, productId) {
            if (confirm('Remove this image from the gallery?')) {
                $.ajax({
                    url: baseUrl + 'php/endpoints/remove-gallery-image.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        image_id: imageId,
                        product_id: productId
                    }),
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            $('.gallery-item[data-image-id="' + imageId + '"]').remove();
                            showSuccessToast('Image removed');
                        } else {
                            showErrorToast(data.message || 'Could not remove image');
                        }
                    },
                    error: function() {
                        showErrorToast('Something went wrong');
                    }
                });
            }
        }

        function setPrimaryImage(imageId, productId) {
            $.ajax({
                url: baseUrl + 'php/endpoints/set-primary-image.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    image_id: imageId,
                    product_id: productId
                }),
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        showSuccessToast('Primary image updated');
                        location.reload();
                    } else {
                        showErrorToast(data.message || 'Could not set primary image');
                    }
                },
                error: function() {
                    showErrorToast('Something went wrong');
                }
            });
        }
    </script>
</body>

</html>