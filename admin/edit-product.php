<?php
/*
 * ConsuTrade - Edit Product Page
 * Author: Kamogelo Phale
 * 
 * Sellers can edit products and manage gallery images
 */

require_once dirname(__DIR__) . '/init.php';

if (!$auth->isSellerLoggedIn()) {
    header('Location: login.php');
    exit;
}

$seller_id = $current_user_id;
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: my-products.php');
    exit;
}

// Get product as Product object using ProductRepository
$product = $productRepo->getProductObject($product_id);

if (!$product || $product->getSellerId() != $seller_id) {
    header('Location: my-products.php');
    exit;
}

// Get gallery images using ProductImageRepository
$gallery_images = $productImageRepo->getByProductId($product_id);

// Get categories using CategoryRepository
$categories = $categoryRepo->getAll();

// Get user data using UserRepository
$user_data = $userRepo->getById($seller_id);
$profile_image = !empty($user_data['profile_image']) ? getBaseUrl() . $user_data['profile_image'] : getBaseUrl() . 'images/icons/profile-svgrepo-com.svg';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>css/style.css">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>admin/css/dashboard-clean.css">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>admin/css/sidebar.css">
    <script src="<?php echo getBaseUrl(); ?>js/jquery-3.7.1.min.js"></script>
    <script src="<?php echo getBaseUrl(); ?>js/main.js"></script>
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
        }

        .btn-cancel:hover {
            background: var(--border-light);
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

        .gallery-section {
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-lg);
            border-top: 1px solid var(--border-light);
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
            cursor: pointer;
            font-size: 12px;
            line-height: 18px;
            text-decoration: none;
            z-index: 1;
        }

        .gallery-item .primary-badge {
            position: absolute;
            bottom: 4px;
            left: 4px;
            background: var(--primary-color);
            color: white;
            padding: 2px 6px;
            border-radius: var(--radius-sm);
            font-size: 10px;
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

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .form-container {
                padding: var(--spacing-lg);
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main-content">
        <div class="dashboard-content">
            <h1 style="margin-bottom: var(--spacing-sm); font-size: var(--font-3xl); font-weight: var(--font-bold)">Edit Product</h1>
            <p style="margin-bottom: var(--spacing-xl); color: var(--gray-medium)">Update your product information</p>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-msg"><?php echo htmlspecialchars($_SESSION['error']);
                                        unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-msg"><?php echo htmlspecialchars($_SESSION['success']);
                                            unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form action="<?php echo getBaseUrl(); ?>php/endpoints/edit-product.php" method="post" enctype="multipart/form-data">
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
                                <p style="font-size: var(--font-sm); color: var(--gray-medium); margin-top: var(--spacing-sm)">Leave empty to keep current image</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Gallery Images Section -->
                    <div class="gallery-section">
                        <label>Product Gallery Images</label>
                        <div class="gallery-grid" id="existing-gallery">
                            <?php foreach ($gallery_images as $img): ?>
                                <div class="gallery-item" data-image-id="<?php echo $img['image_id']; ?>">
                                    <img src="<?php echo $productRepo->getProductImageUrl($img['image_url']); ?>" alt="Gallery image">
                                    <a href="javascript:void(0)" class="remove-btn" onclick="removeGalleryImage(<?php echo $img['image_id']; ?>, <?php echo $product->getProductId(); ?>)">×</a>
                                    <?php if ($img['is_primary']): ?>
                                        <span class="primary-badge">Primary</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-group" style="margin-top: var(--spacing-md)">
                            <label>Add More Images (Optional)</label>
                            <input type="file" name="new_gallery_images[]" accept="image/*" multiple>
                            <small style="color: var(--gray-medium)">You can select multiple images at once</small>
                            <div id="new-gallery-preview" class="gallery-grid" style="margin-top: var(--spacing-md)"></div>
                        </div>
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
        var baseUrl = '<?php echo getBaseUrl(); ?>';
        var currentUserId = <?php echo $current_user_id ?: 0; ?>;
        var currentUserRole = '<?php echo $current_user ? $current_user['role'] : ''; ?>';
        var isLoggedIn = true;

        $('input[name="new_gallery_images[]"]').on('change', function(e) {
            var preview = $('#new-gallery-preview');
            preview.empty();
            var files = this.files;

            for (var i = 0; i < files.length; i++) {
                var reader = new FileReader();
                reader.onload = (function(fileIndex) {
                    return function(event) {
                        preview.append('<div class="gallery-item"><img src="' + event.target.result + '" alt="Preview ' + (fileIndex + 1) + '"></div>');
                    };
                })(i);
                reader.readAsDataURL(files[i]);
            }
        });

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
                        } else {
                            alert('Could not remove image: ' + data.message);
                        }
                    },
                    error: function() {
                        alert('Something went wrong.');
                    }
                });
            }
        }
    </script>
    <script src="<?php echo getBaseUrl(); ?>admin/js/dashboard.js"></script>

</body>

</html>