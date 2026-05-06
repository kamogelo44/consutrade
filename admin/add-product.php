<?php
/*
 * ConsuTrade - Add Product Page
 * Author: Kamogelo Phale
 * 
 * Separate page for sellers to add new products with multiple images
 */

require_once dirname(__DIR__) . '/init.php';

// Check if seller is logged in
if (!isSellerLoggedIn()) {
    header('Location: login.php');
    exit;
}
$baseUrl = getBaseUrl();

// Get user data using helper
$user = getUserById($conn, $current_user_id);
$profile_image = getUserProfileImage($user['profile_image'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - ConsuTrade Seller</title>
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/add-product.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';
    </script>
</head>
<body class="add-product-page seller-dashboard-page">
    
    <?php include 'includes/sidebar.php'; ?>

    <main class="dashboard-main">
        <div class="dashboard-content">
            <!-- Breadcrumb Navigation -->
            <div class="breadcrumb-nav">
                <a href="seller-dashboard.php">Dashboard</a>
                <span class="separator">›</span>
                <a href="my-products.php">My Products</a>
                <span class="separator">›</span>
                <span class="current">Add New Product</span>
            </div>

            <!-- Add Product Content -->
            <div class="add-product-container">
                <div class="add-product-header">
                    <h1>Add New Product</h1>
                    <p>Fill in the details below to list your product on ConsuTrade</p>
                </div>

                <!-- Flash Messages -->
                <?php if (isset($_SESSION['flash'])): ?>
                    <div class="success-message"><?php echo $_SESSION['flash']; unset($_SESSION['flash']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['product_errors'])): ?>
                    <div class="error-message"><?php echo implode(', ', $_SESSION['product_errors']); unset($_SESSION['product_errors']); ?></div>
                <?php endif; ?>

                <div class="add-product-layout">
                    <!-- Left Column: Tips & Progress -->
                    <div class="left-column tips-column">
                        <div class="tips-card">
                            <h3>
                                <img src="<?php echo $baseUrl; ?>images/icons/valid-document-svgrepo-com.svg" width="24px" height="24px" alt="Tips" class="icon">
                                Listing Tips
                            </h3>
                            <ul class="tips-list">
                                <li><img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16px" height="16px" alt="check"> Use clear, high-quality photos</li>
                                <li><img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16px" height="16px" alt="check"> Write detailed product descriptions</li>
                                <li><img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16px" height="16px" alt="check"> Price competitively</li>
                                <li><img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16px" height="16px" alt="check"> Include product condition</li>
                                <li><img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16px" height="16px" alt="check"> Set accurate stock quantity</li>
                            </ul>
                        </div>

                        <div class="progress-card">
                            <h3>
                                <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="24px" height="24px" alt="Progress" class="icon">
                                Completion Progress
                            </h3>
                            <div class="progress-bar-container">
                                <div class="progress-bar" id="progress-bar" style="width: 0%;"></div>
                            </div>
                            <p class="progress-text" id="progress-text">0% Complete</p>
                        </div>

                        <div class="help-card">
                            <h3>
                                <img src="<?php echo $baseUrl; ?>images/icons/phone-number.svg" width="24px" height="24px" alt="Help">
                                Need Help?
                            </h3>
                            <p>Contact our seller support:</p>
                            <p><a href="mailto:seller@consutrade.co.za">seller@consutrade.co.za</a></p>
                        </div>
                    </div>

                    <!-- Right Column: Product Form -->
                    <div class="right-column form-column">
                        <form id="add-product-form" action="<?php echo $baseUrl; ?>php/add-product.php" method="post" enctype="multipart/form-data">
                            <!-- Basic Information -->
                            <fieldset class="form-section">
                                <legend>Basic Information</legend>
                                
                                <div class="form-group">
                                    <label for="product-title">Product Title</label>
                                    <input type="text" id="product-title" name="title" required placeholder="e.g., Handmade Leather Bag">
                                </div>

                                <div class="form-group">
                                    <label for="product-category">Category</label>
                                    <select id="product-category" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <option value="1">Clothing & Accessories</option>
                                        <option value="2">Electronics</option>
                                        <option value="3">Food and Drinks</option>
                                        <option value="4">Furniture</option>
                                        <option value="5">Home & Garden</option>
                                        <option value="6">Beauty & Health</option>
                                        <option value="7">Other</option>
                                    </select>
                                </div>

                                <div class="form-row">
                                    <div class="form-group half">
                                        <label for="product-price">Price (R)</label>
                                        <input type="number" id="product-price" name="price" step="0.01" required placeholder="0.00">
                                    </div>
                                    <div class="form-group half">
                                        <label for="product-stock">Stock Quantity</label>
                                        <input type="number" id="product-stock" name="stock_quantity" value="1" min="1" max="999" required>
                                        <small>How many units do you have available?</small>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group half">
                                        <label for="product-condition">Condition (if applicable)</label>
                                        <select id="product-condition" name="condition">
                                            <option value="">Not Applicable</option>
                                            <option value="New">Brand New</option>
                                            <option value="Like New">Like New</option>
                                            <option value="Good">Good</option>
                                            <option value="Fair">Fair</option>
                                        </select>
                                    </div>
                                    <div class="form-group half">
                                        <label for="product-location">Location</label>
                                        <input type="text" id="product-location" name="location" placeholder="e.g., Johannesburg, Soweto">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="product-description">Description</label>
                                    <textarea id="product-description" name="description" rows="5" 
                                              placeholder="Describe your product in detail..." required></textarea>
                                </div>
                            </fieldset>

                            <!-- Images Section -->
                            <fieldset class="form-section">
                                <legend>
                                    <img src="<?php echo $baseUrl; ?>images/icons/photos-filled-svgrepo-com.svg" width="20px" height="20px" alt="Images">
                                    Product Images
                                </legend>
                                
                                <!-- Main Image Upload -->
                                <div class="form-group">
                                    <label>Main Product Image</label>
                                    <div class="image-upload-container" id="main-image-container">
                                        <input type="file" id="main-image" name="image" accept="image/*" required style="display: none;">
                                        <div class="image-preview" id="main-image-preview">
                                            <div class="upload-placeholder">
                                                <img src="<?php echo $baseUrl; ?>images/icons/camera-svgrepo-com.svg" width="48px" height="48px" alt="Upload">
                                                <p>Click to upload main image</p>
                                                <small>Recommended: 800x800px, max 2MB</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Thumbnail Images (4 smaller images) -->
                                <div class="form-group">
                                    <label>Additional Images (Up to 4)</label>
                                    <div class="thumbnails-grid" id="thumbnails-grid">
                                        <?php for ($i = 0; $i < 4; $i++): ?>
                                        <div class="thumbnail-upload" data-index="<?php echo $i; ?>">
                                            <input type="file" name="thumbnail_<?php echo $i; ?>" accept="image/*" class="thumbnail-input" style="display: none;">
                                            <div class="thumbnail-preview">
                                                <div class="upload-placeholder-small">+</div>
                                            </div>
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </fieldset>

                            <div class="form-actions">
                                <button type="button" class="cancel-btn" onclick="window.location.href='seller-dashboard.php'">
                                    <img src="<?php echo $baseUrl; ?>images/icons/delete-svgrepo-com.svg" width="16px" height="16px" alt="Cancel">
                                    Cancel
                                </button>
                                <button type="submit" class="submit-btn">
                                    <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16px" height="16px" alt="Publish">
                                    Publish Product
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="<?php echo $baseUrl; ?>js/main.js"></script>
<script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
<script>
$(function() {
    function updateProgress() {
        var fields = ['#product-title', '#product-category', '#product-price', '#product-stock', '#product-description'];
        var filled = 0;
        
        for (var i = 0; i < fields.length; i++) {
            if ($(fields[i]).val() && $(fields[i]).val() !== '') {
                filled++;
            }
        }
        
        if ($('#product-condition').val() && $('#product-condition').val() !== '') filled++;
        if ($('#product-location').val() && $('#product-location').val() !== '') filled++;
        if ($('#main-image')[0].files.length > 0) filled++;
        
        $('.thumbnail-input').each(function() {
            if ($(this)[0].files.length > 0) filled++;
        });
        
        var totalFields = fields.length + 6;
        var percentage = Math.min(Math.round((filled / totalFields) * 100), 100);
        
        $('#progress-bar').css('width', percentage + '%');
        $('#progress-text').text(percentage + '% Complete');
        
        if (percentage < 30) $('#progress-bar').css('background-color', '#dc3545');
        else if (percentage < 70) $('#progress-bar').css('background-color', '#ffc107');
        else $('#progress-bar').css('background-color', '#28a745');
    }

    $('#main-image').on('change', function(e) {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(event) {
                $('#main-image-preview').html('<img src="' + event.target.result + '" alt="Product preview">');
                updateProgress();
            };
            reader.readAsDataURL(file);
        }
    });

    $('#main-image-container').on('click', function() {
        $('#main-image').click();
    });

    $('.thumbnail-upload').each(function() {
        var $container = $(this);
        var $input = $container.find('.thumbnail-input');
        var $preview = $container.find('.thumbnail-preview');
        
        $input.on('change', function(e) {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(event) {
                    $preview.html('<img src="' + event.target.result + '" alt="Thumbnail">');
                    updateProgress();
                };
                reader.readAsDataURL(file);
            }
        });
        
        $container.on('click', function() {
            $input.click();
        });
    });
    
    $('#add-product-form input, #add-product-form select, #add-product-form textarea').on('change keyup', function() {
        updateProgress();
    });
    
    $('#add-product-form').on('submit', function(e) {
        if ($('#main-image')[0].files.length === 0) {
            e.preventDefault();
            alert('Please upload a main product image.');
            return false;
        }
        return true;
    });
    
    updateProgress();
});
</script>
</body>
</html>