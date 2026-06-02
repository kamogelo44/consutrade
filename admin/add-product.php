<?php
/*
 * ConsuTrade - Add Product Page
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__) . '/init.php';

if (!$auth->isSeller()) {
    header('Location: login.php');
    exit;
}

$seller_id = $currentUser->getUserId();
$user_name = $currentUser->getFullName();
$profile_image = $currentUser->getProfileImageUrl();
$categories = $categoryRepo->getAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/form-master.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main-content">
        <div class="dashboard-content">
            <div class="page-header">
                <h1>Add New Product</h1>
                <p>Fill in the details below to list your product</p>
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
                <form id="product-form" action="<?php echo getBaseUrl(); ?>php/endpoints/add-product.php" method="post" enctype="multipart/form-data">
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
                        <small style="color: var(--gray-medium)">Main image will be shown as the primary product photo</small>
                    </div>

                    <div class="form-group">
                        <label>Additional Images (Up to 4)</label>
                        <input type="file" name="gallery_images[]" accept="image/*" multiple>
                        <small style="color: var(--gray-medium)">You can select multiple images at once (max 4)</small>
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

    <script>
        var baseUrl = '<?php echo getBaseUrl(); ?>';
        var currentUserId = <?php echo $current_user_id ?: 0; ?>;
        var currentUserRole = '<?php echo $current_user ? $current_user['role'] : ''; ?>';
        var isLoggedIn = true;
    </script>
    <script src="<?php echo getBaseUrl(); ?>admin/js/dashboard.js"></script>
    <script>
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
    </script>

</body>

</html>