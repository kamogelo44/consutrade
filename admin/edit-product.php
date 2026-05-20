<?php
/*
 * ConsuTrade - Edit Product Page
 * Author: Kamogelo Phale
 * 
 * Sellers can edit products and manage gallery images
 */

require_once dirname(__DIR__) . '/init.php';

if (!isSellerLoggedIn()) {
    header('Location: login.php');
    exit;
}

$baseUrl = getBaseUrl();
$seller_id = $current_user_id;
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: my-products.php');
    exit;
}

// Get product data
$product = getProductForEdit($conn, $product_id, $seller_id);
if (!$product) {
    header('Location: my-products.php');
    exit;
}

// Get gallery images from product_images table
$gallery_images = [];
$gallery_stmt = $conn->prepare("SELECT image_id, image_url, is_primary, sort_order FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
$gallery_stmt->bind_param('i', $product_id);
$gallery_stmt->execute();
$gallery_result = $gallery_stmt->get_result();
while ($img = $gallery_result->fetch_assoc()) {
    $gallery_images[] = $img;
}
$gallery_stmt->close();

$user = getUserById($conn, $seller_id);
$profile_image = getUserProfileImage($user['profile_image'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-clean.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar-clean.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/modal.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
    <script>var baseUrl = '<?php echo $baseUrl; ?>';</script>
    <style>
        .form-container { max-width: 800px; margin: 0 auto; background: var(--white); border-radius: var(--radius-lg); padding: var(--spacing-xl); border: 1px solid var(--border-light); }
        .form-group { margin-bottom: var(--spacing-lg); }
        .form-group label { display: block; font-weight: var(--font-semibold); margin-bottom: var(--spacing-sm); color: var(--dark-bg); }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: var(--font-md); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md); }
        .btn-submit { background: var(--primary-color); color: var(--white); padding: 12px 24px; border: none; border-radius: var(--radius-md); cursor: pointer; }
        .btn-cancel { background: var(--gray-bg-light); color: var(--gray-dark); padding: 12px 24px; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; margin-left: var(--spacing-sm); }
        .current-image { margin-bottom: var(--spacing-md); padding: var(--spacing-md); background: var(--gray-bg-light); border-radius: var(--radius-md); }
        .current-image img { max-width: 150px; border-radius: var(--radius-md); }
        .gallery-section { margin-top: var(--spacing-lg); padding-top: var(--spacing-lg); border-top: 1px solid var(--border-light); }
        .gallery-grid { display: flex; gap: var(--spacing-md); flex-wrap: wrap; margin-top: var(--spacing-md); }
        .gallery-item { position: relative; width: 100px; height: 100px; border: 1px solid var(--border-light); border-radius: var(--radius-md); overflow: hidden; }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; }
        .gallery-item .remove-btn { position: absolute; top: 4px; right: 4px; background: var(--error); color: white; border-radius: 50%; width: 20px; height: 20px; text-align: center; cursor: pointer; font-size: 12px; line-height: 18px; text-decoration: none; }
        .gallery-item .primary-badge { position: absolute; bottom: 4px; left: 4px; background: var(--primary-color); color: white; padding: 2px 6px; border-radius: var(--radius-sm); font-size: 10px; }
        .error-msg { background: var(--error-light); color: var(--error); padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg); }
        .success-msg { background: var(--success-light); color: var(--success); padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg); }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .form-container { margin: 0 var(--spacing-md); padding: var(--spacing-lg); }
            .form-row { grid-template-columns: 1fr; gap: var(--spacing-sm); }
        }
        @media (max-width: 768px) {
            .form-container { margin: 0 var(--spacing-sm); padding: var(--spacing-md); }
            .gallery-grid { justify-content: center; }
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<main class="admin-main-content">
    <div class="dashboard-content">
        <h1 style="margin-bottom: var(--spacing-md); font-size: var(--font-2xl); font-weight: var(--font-bold)">Edit Product</h1>
        <p style="margin-bottom: var(--spacing-lg); color: var(--gray-medium)">Update your product information</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-msg"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-msg"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form action="<?php echo $baseUrl; ?>php/edit-product-handler.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                
                <div class="form-group">
                    <label>Product Title *</label>
                    <input type="text" name="title" required value="<?php echo htmlspecialchars($product['title']); ?>">
                </div>

                <div class="form-group">
                    <label>Category *</label>
                    <select name="category_id" required>
                        <option value="">Select Category</option>
                        <option value="1" <?php echo $product['category_id'] == 1 ? 'selected' : ''; ?>>Clothing & Accessories</option>
                        <option value="2" <?php echo $product['category_id'] == 2 ? 'selected' : ''; ?>>Electronics</option>
                        <option value="3" <?php echo $product['category_id'] == 3 ? 'selected' : ''; ?>>Food and Drinks</option>
                        <option value="4" <?php echo $product['category_id'] == 4 ? 'selected' : ''; ?>>Furniture</option>
                        <option value="5" <?php echo $product['category_id'] == 5 ? 'selected' : ''; ?>>Home & Garden</option>
                        <option value="6" <?php echo $product['category_id'] == 6 ? 'selected' : ''; ?>>Beauty & Health</option>
                        <option value="7" <?php echo $product['category_id'] == 7 ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Price (R) *</label>
                        <input type="number" name="price" step="0.01" required value="<?php echo $product['price']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Stock Quantity *</label>
                        <input type="number" name="stock_quantity" min="1" required value="<?php echo $product['stock_quantity']; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Condition</label>
                        <select name="condition">
                            <option value="">Not Applicable</option>
                            <option value="New" <?php echo ($product['condition'] ?? '') == 'New' ? 'selected' : ''; ?>>Brand New</option>
                            <option value="Like New" <?php echo ($product['condition'] ?? '') == 'Like New' ? 'selected' : ''; ?>>Like New</option>
                            <option value="Good" <?php echo ($product['condition'] ?? '') == 'Good' ? 'selected' : ''; ?>>Good</option>
                            <option value="Fair" <?php echo ($product['condition'] ?? '') == 'Fair' ? 'selected' : ''; ?>>Fair</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" value="<?php echo htmlspecialchars($product['location'] ?? ''); ?>" placeholder="e.g., Johannesburg">
                    </div>
                </div>

                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" rows="5" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Change Main Image (Optional)</label>
                    <input type="file" name="main_image" accept="image/*">
                    <?php if (!empty($product['image_url'])): ?>
                        <div class="current-image">
                            <img src="<?php echo getProductImageUrl($product['image_url']); ?>" alt="Current main image">
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
                                <img src="<?php echo getProductImageUrl($img['image_url']); ?>" alt="Gallery image">
                                <a href="javascript:void(0)" class="remove-btn" onclick="removeGalleryImage(<?php echo $img['image_id']; ?>, <?php echo $product_id; ?>)">×</a>
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
// Preview new gallery images
$('input[name="new_gallery_images[]"]').on('change', function(e) {
    const preview = $('#new-gallery-preview');
    preview.empty();
    const files = this.files;
    
    for (let i = 0; i < files.length; i++) {
        const reader = new FileReader();
        reader.onload = function(event) {
            preview.append(`
                <div class="gallery-item">
                    <img src="${event.target.result}" alt="Preview">
                </div>
            `);
        };
        reader.readAsDataURL(files[i]);
    }
});

function removeGalleryImage(imageId, productId) {
    if (confirm('Remove this image from the gallery?')) {
        $.ajax({
            url: baseUrl + 'admin/php/remove-gallery-image.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ image_id: imageId, product_id: productId }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $('.gallery-item[data-image-id="' + imageId + '"]').remove();
                    showSuccessToast('Image removed');
                } else {
                    showErrorToast('Error: ' + data.message);
                }
            },
            error: function() {
                showErrorToast('Something went wrong');
            }
        });
    }
}
</script>

</body>
</html>