<?php
/*
 * ConsuTrade - Add Product Page
 * Author: Kamogelo Phale
 * 
 * Allows sellers to add new products to the marketplace
 */

require_once dirname(__DIR__) . '/init.php';
include dirname(__DIR__) . '/includes/session-vars.php';
include dirname(__DIR__) . '/includes/functions.php';

if (!$auth->isSeller()) {
    header('Location: login.php');
    exit;
}

$seller_id = $currentUser->getUserId();
$user_name = $currentUser->getFullName();
$profile_image = $currentUser->getProfileImageUrl();
$categories = $categoryRepo->getAll();

// Breadcrumb for subpage navigation
$breadcrumbItems = [
    ['url' => 'admin/my-products.php', 'label' => 'My Products'],
    ['label' => 'Add New Product']
];

// Get flash messages from session (set by endpoint)
$error = $_SESSION['product_error'] ?? null;
$success = $_SESSION['product_success'] ?? null;
unset($_SESSION['product_error'], $_SESSION['product_success']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Add New Product - ConsuTrade</title>

    <!-- CSS Imports - Using component-based architecture -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>

    <style>
        /* Page-specific styles */
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
            padding: 10px 12px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: var(--font-md);
            transition: all var(--transition-fast);
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

        .gallery-preview {
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
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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

        /* Using global error/success message styles from main.css */
        /* .error-msg and .success-msg classes are now handled by .error-message and .flash-message */

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
            }

            .form-container {
                padding: var(--spacing-lg);
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

            <!-- Flash messages using global styles -->
            <?php include '..includes/flash-message.php' ?>

            <div class="form-container">
                <form id="product-form" action="<?php echo $baseUrl; ?>php/endpoints/add-product.php" method="post" enctype="multipart/form-data">
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

                    <div class="form-group">
                        <label>Main Product Image *</label>
                        <input type="file" name="main_image" accept="image/*" required>
                        <small>Main image will be shown as the primary product photo</small>
                    </div>

                    <div class="form-group">
                        <label>Additional Images (Up to 4)</label>
                        <input type="file" name="gallery_images[]" accept="image/*" multiple>
                        <small>You can select multiple images at once (max 4)</small>
                        <div id="gallery-preview" class="gallery-preview"></div>
                    </div>

                    <div style="margin-top: var(--spacing-xl); display: flex; gap: var(--spacing-sm)">
                        <button type="submit" class="btn-submit">Publish Product</button>
                        <a href="my-products.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Gallery preview script - uses jQuery from sidebar.php -->
    <script>
        $(document).ready(function() {
            $('input[name="gallery_images[]"]').on('change', function(e) {
                var preview = $('#gallery-preview');
                preview.empty();
                var files = this.files;
                var maxFiles = Math.min(files.length, 4);

                for (var i = 0; i < maxFiles; i++) {
                    var reader = new FileReader();
                    reader.onload = (function(fileIndex) {
                        return function(event) {
                            preview.append('<div class="gallery-item"><img src="' + event.target.result + '" alt="Preview ' + (fileIndex + 1) + '"></div>');
                        };
                    })(i);
                    reader.readAsDataURL(files[i]);
                }
            });
        });
    </script>
</body>

</html>