<?php
/*
 * ConsuTrade - Edit Product Page
 * Author: Kamogelo Phale
 * 
 * This page allows sellers to edit their existing products
 */

require_once dirname(__DIR__) . '/init.php';

// Check if seller is logged in using centralized auth
if (!isSellerLoggedIn()) {
    header('Location: login.php');
    exit;
}

$baseUrl = getBaseUrl();
$seller_id = $current_user_id;

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: my-products.php');
    exit;
}

// Get user data for profile image (for sidebar)
$user = getUserById($conn, $seller_id);
$profile_image = getUserProfileImage($user['profile_image'] ?? null);

// Get product data using helper
$product = getProductForEdit($conn, $product_id, $seller_id);

if (!$product) {
    header('Location: my-products.php');
    exit;
}

$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $stock_quantity = (int)($_POST['stock_quantity'] ?? 1);
    $description = trim($_POST['description'] ?? '');
    $condition = !empty($_POST['condition']) ? trim($_POST['condition']) : NULL;
    $location = trim($_POST['location'] ?? '');
    
    $errors = [];
    
    // Validate inputs
    if (empty($title)) {
        $errors[] = 'Product title is required';
    }
    
    if ($category_id <= 0) {
        $errors[] = 'Please select a category';
    }
    
    if ($price <= 0) {
        $errors[] = 'Please enter a valid price';
    }
    
    if ($stock_quantity < 1) {
        $errors[] = 'Stock quantity must be at least 1';
    }
    
    if (empty($description)) {
        $errors[] = 'Product description is required';
    }
    
    // Handle image upload if new image is provided
    $image_path = $product['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $new_image_path = convertToWebP($_FILES['image'], $seller_id, $title, 'main');
        if ($new_image_path) {
            // Delete old image if exists
            if (!empty($product['image'])) {
                deleteProductImage($product['image']);
            }
            $image_path = $new_image_path;
        } else {
            $errors[] = 'Failed to process image. Please try again.';
        }
    }
    
    // Update database if no errors
    if (empty($errors)) {
        $update_data = [
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'stock_quantity' => $stock_quantity,
            'condition' => $condition,
            'location' => $location,
            'category_id' => $category_id
        ];
        
        $result = updateSellerProduct($conn, $product_id, $seller_id, $update_data);
        
        // Also update image separately if changed
        if ($image_path !== $product['image']) {
            $img_sql = "UPDATE products SET image_url = ? WHERE product_id = ? AND seller_id = ?";
            $img_stmt = $conn->prepare($img_sql);
            $img_stmt->bind_param('sii', $image_path, $product_id, $seller_id);
            $img_stmt->execute();
            $img_stmt->close();
        }
        
        if ($result['success']) {
            $_SESSION['flash'] = $result['message'];
            header('Location: my-products.php');
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
    
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    }
}

// Set current page for active sidebar link
$current_page = 'products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - ConsuTrade Seller</title>
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/add-product.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';
    </script>
</head>
<body class="edit-product-page seller-dashboard-page">
    
    <?php include 'includes/sidebar.php'; ?>

    <main class="dashboard-main">
        <div class="dashboard-content">
            <!-- Breadcrumb Navigation -->
            <div class="breadcrumb-nav">
                <a href="seller-dashboard.php">Dashboard</a>
                <span class="separator">›</span>
                <a href="my-products.php">My Products</a>
                <span class="separator">›</span>
                <span class="current">Edit Product</span>
            </div>

            <!-- Edit Product Content -->
            <div class="add-product-container">
                <div class="add-product-header">
                    <h1>Edit Product</h1>
                    <p>Update your product information</p>
                </div>

                <!-- Flash Messages -->
                <?php if (isset($_SESSION['flash'])): ?>
                    <div class="success-message"><?php echo $_SESSION['flash']; unset($_SESSION['flash']); ?></div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
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
                        <form id="edit-product-form" action="" method="post" enctype="multipart/form-data">
                            <!-- Basic Information -->
                            <fieldset class="form-section">
                                <legend>Basic Information</legend>
                                
                                <div class="form-group">
                                    <label for="product-title">Product Title</label>
                                    <input type="text" id="product-title" name="title" required 
                                           value="<?php echo htmlspecialchars($product['title']); ?>"
                                           placeholder="e.g., Handmade Leather Bag">
                                </div>

                                <div class="form-group">
                                    <label for="product-category">Category</label>
                                    <select id="product-category" name="category_id" required>
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
                                    <div class="form-group half">
                                        <label for="product-price">Price (R)</label>
                                        <input type="number" id="product-price" name="price" step="0.01" required 
                                               value="<?php echo $product['price']; ?>"
                                               placeholder="0.00">
                                    </div>
                                    <div class="form-group half">
                                        <label for="product-stock">Stock Quantity</label>
                                        <input type="number" id="product-stock" name="stock_quantity" 
                                               value="<?php echo $product['stock_quantity']; ?>" 
                                               min="1" max="999" required>
                                        <small>How many units do you have available?</small>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group half">
                                        <label for="product-condition">Condition (if applicable)</label>
                                        <select id="product-condition" name="condition">
                                            <option value="">Not Applicable</option>
                                            <option value="New" <?php echo $product['condition'] == 'New' ? 'selected' : ''; ?>>Brand New</option>
                                            <option value="Like New" <?php echo $product['condition'] == 'Like New' ? 'selected' : ''; ?>>Like New</option>
                                            <option value="Good" <?php echo $product['condition'] == 'Good' ? 'selected' : ''; ?>>Good</option>
                                            <option value="Fair" <?php echo $product['condition'] == 'Fair' ? 'selected' : ''; ?>>Fair</option>
                                        </select>
                                    </div>
                                    <div class="form-group half">
                                        <label for="product-location">Location</label>
                                        <input type="text" id="product-location" name="location" 
                                               value="<?php echo htmlspecialchars($product['location'] ?? ''); ?>"
                                               placeholder="e.g., Johannesburg, Soweto">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="product-description">Description</label>
                                    <textarea id="product-description" name="description" rows="5" 
                                              placeholder="Describe your product in detail..."><?php echo htmlspecialchars($product['description']); ?></textarea>
                                </div>
                            </fieldset>

                            <!-- Images Section -->
                            <fieldset class="form-section">
                                <legend>
                                    <img src="<?php echo $baseUrl; ?>images/icons/photos-filled-svgrepo-com.svg" width="20px" height="20px" alt="Images">
                                    Product Images
                                </legend>
                                
                                <!-- Current Image Display -->
                                <?php if (!empty($product['image'])): ?>
                                    <div class="form-group">
                                        <label>Current Image</label>
                                        <div class="current-image">
                                            <img src="<?php echo getProductImageUrl($product['image']); ?>" alt="Current product image" style="max-width: 200px; border-radius: 8px;">
                                            <p style="font-size: 12px; color: #666; margin-top: 8px;">Leave empty to keep current image</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- New Image Upload -->
                                <div class="form-group">
                                    <label>Change Image (Optional)</label>
                                    <div class="image-upload-container" id="main-image-container">
                                        <input type="file" id="main-image" name="image" accept="image/*" style="display: none;">
                                        <div class="image-preview" id="main-image-preview">
                                            <div class="upload-placeholder">
                                                <img src="<?php echo $baseUrl; ?>images/icons/camera-svgrepo-com.svg" width="48px" height="48px" alt="Upload">
                                                <p>Click to upload new image</p>
                                                <small>Recommended: 800x800px, max 2MB</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                            <div class="form-actions">
                                <button type="button" class="cancel-btn" onclick="window.location.href='my-products.php'">
                                    <img src="<?php echo $baseUrl; ?>images/icons/delete-svgrepo-com.svg" width="16px" height="16px" alt="Cancel">
                                    Cancel
                                </button>
                                <button type="submit" class="submit-btn">
                                    <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16px" height="16px" alt="Save">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
    <script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
    <script>
    $(function() {
        // Main image preview
        $('#main-image').on('change', function(e) {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(event) {
                    $('#main-image-preview').html('<img src="' + event.target.result + '" alt="Product preview" style="max-width: 100%; max-height: 200px; object-fit: contain;">');
                };
                reader.readAsDataURL(file);
            } else {
                // Restore placeholder if no file selected
                $('#main-image-preview').html(`
                    <div class="upload-placeholder">
                        <img src="${baseUrl}images/icons/camera-svgrepo-com.svg" width="48px" height="48px" alt="Upload">
                        <p>Click to upload new image</p>
                        <small>Recommended: 800x800px, max 2MB</small>
                    </div>
                `);
            }
        });
        
        // Make main image container clickable
        $('#main-image-container').on('click', function() {
            $('#main-image').click();
        });
    });
    </script>

</body>
</html>