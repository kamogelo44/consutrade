<?php
/*
 * ConsuTrade - Edit Product Page
 * Author: Kamogelo Phale
 * 
 * This page allows sellers to edit their existing products
 */

session_start();

$baseUrl = "/www/consutrade/";

// Check if user is logged in and is a seller
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'seller') {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: ' . $baseUrl . 'admin/my-products.php');
    exit;
}

require_once dirname(__DIR__) . '/php/config.php';

$seller_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get product data
$sql = "SELECT product_id, title, description, price, category_id, `condition`, location, image_url, status
        FROM products 
        WHERE product_id = ? AND seller_id = ? AND status != 'deleted'";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $product_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ' . $baseUrl . 'admin/my-products.php');
    exit;
}

$product = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
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
    
    if (empty($description)) {
        $errors[] = 'Product description is required';
    }
    
    // Handle image upload if new image is provided
    $image_path = $product['image_url'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $new_image_path = convertToWebP($_FILES['image'], $seller_id, $title);
        if ($new_image_path) {
            // Delete old image if exists
            if (!empty($product['image_url']) && file_exists(dirname(__DIR__) . '/' . $product['image_url'])) {
                unlink(dirname(__DIR__) . '/' . $product['image_url']);
            }
            $image_path = $new_image_path;
        } else {
            $errors[] = 'Failed to process image. Please try again.';
        }
    }
    
    // Update database if no errors
    if (empty($errors)) {
        $update_sql = "UPDATE products 
                    SET title = ?, description = ?, price = ?, category_id = ?, 
                        `condition` = ?, location = ?, image_url = ?
                    WHERE product_id = ? AND seller_id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('ssdissii', $title, $description, $price, $category_id, 
                                $condition, $location, $image_path, $product_id, $seller_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['flash'] = 'Product updated successfully!';
            header('Location: ' . $baseUrl . 'admin/my-products.php');
            exit;
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
        $update_stmt->close();
    }
    
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    }
}

$conn->close();

// Include header
include dirname(__DIR__) . '/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - ConsuTrade Seller</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/header.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/login-signup.css">
    <link rel="stylesheet" href="css/add-product.css">
</head>
<body class="add-product-page">
    <main class="add-product-container">
        <div class="add-product-header">
            <h1>Edit Product</h1>
            <p>Update your product information</p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message" style="background:#ffebee; border-left:3px solid #f44336; padding:12px 20px; margin-bottom:20px; border-radius:8px;">
                <?php echo $error_message; ?>
            </div>
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
                        <li>✓ Use clear, high-quality photos</li>
                        <li>✓ Write detailed product descriptions</li>
                        <li>✓ Price competitively</li>
                        <li>✓ Include product condition</li>
                        <li>✓ Respond quickly to buyer questions</li>
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
                                <label for="product-condition">Condition (if applicable)</label>
                                <select id="product-condition" name="condition">
                                    <option value="">Not Applicable</option>
                                    <option value="New" <?php echo $product['condition'] == 'New' ? 'selected' : ''; ?>>Brand New</option>
                                    <option value="Like New" <?php echo $product['condition'] == 'Like New' ? 'selected' : ''; ?>>Like New</option>
                                    <option value="Good" <?php echo $product['condition'] == 'Good' ? 'selected' : ''; ?>>Good</option>
                                    <option value="Fair" <?php echo $product['condition'] == 'Fair' ? 'selected' : ''; ?>>Fair</option>
                                </select>
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
                        <?php if (!empty($product['image_url'])): ?>
                            <div class="form-group">
                                <label>Current Image</label>
                                <div class="current-image">
                                    <?php
                                    $currentImagePath = $product['image_url'];
                                    if (!empty($currentImagePath)) {
                                        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $currentImagePath;
                                        if (file_exists($fullPath)) {
                                            $currentImagePath = $baseUrl . $currentImagePath;
                                        } else {
                                            $currentImagePath = $baseUrl . 'images/default-product.png';
                                        }
                                    } else {
                                        $currentImagePath = $baseUrl . 'images/default-product.png';
                                    }
                                    ?>
                                    <img src="<?php echo $currentImagePath; ?>" alt="Current product image" style="max-width: 200px; border-radius: 8px;">
                                    <p style="font-size: 12px; color: #666; margin-top: 8px;">Leave empty to keep current image</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- New Image Upload -->
                        <div class="form-group">
                            <label>Change Image (Optional)</label>
                            <div class="image-upload-container" id="main-image-container">
                                <input type="file" id="main-image" name="image" accept="image/*">
                                <div class="image-preview" id="main-image-preview">
                                    <div class="upload-placeholder">
                                        <img src="<?php echo $baseUrl; ?>images/icons/photos-filled-svgrepo-com.svg" width="48px" height="48px" alt="Upload">
                                        <p>Click to upload new image</p>
                                        <small>Recommended: 800x800px, max 2MB</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Location -->
                    <fieldset class="form-section">
                        <legend>
                            <img src="<?php echo $baseUrl; ?>images/icons/pin-location-svgrepo-com.svg" width="20px" height="20px" alt="Location">
                            Location
                        </legend>
                        
                        <div class="form-group">
                            <label for="product-location">Location</label>
                            <input type="text" id="product-location" name="location" 
                                   value="<?php echo htmlspecialchars($product['location'] ?? ''); ?>"
                                   placeholder="e.g., Johannesburg, Soweto">
                        </div>
                    </fieldset>

                    <div class="form-actions">
                        <button type="button" class="cancel-btn" onclick="window.location.href='my-products.php'">
                            <img src="<?php echo $baseUrl; ?>images/icons/form-close-svgrepo-com.svg" width="16px" height="16px" alt="Cancel">
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
    </main>
    
    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
    <script>
        // Main image preview
        var mainImageInput = document.getElementById('main-image');
        if (mainImageInput) {
            mainImageInput.addEventListener('change', function(e) {
                var file = e.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(event) {
                        var preview = document.getElementById('main-image-preview');
                        preview.innerHTML = '<img src="' + event.target.result + '" alt="Product preview" style="max-width: 100%; max-height: 200px; object-fit: contain;">';
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
        
        // Make main image container clickable
        var mainImageContainer = document.getElementById('main-image-container');
        if (mainImageContainer) {
            mainImageContainer.addEventListener('click', function() {
                document.getElementById('main-image').click();
            });
        }
    </script>
</body>
</html>

<?php
/**
 * Convert uploaded image to WebP format
 * 
 * @param array $file The uploaded file from $_FILES
 * @param int $seller_id The seller's ID
 * @param string $product_title The product title for filename
 * @return string|false The path to the WebP image or false on failure
 */
function convertToWebP($file, $seller_id, $product_title) {
    $upload_dir = dirname(__DIR__) . '/uploads/products/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $timestamp = time();
    $safe_title = preg_replace('/[^a-zA-Z0-9_-]/', '_', $product_title);
    $safe_title = substr($safe_title, 0, 50);
    $filename = $seller_id . '_' . $timestamp . '_' . $safe_title . '.webp';
    $destination = $upload_dir . $filename;
    
    $source = $file['tmp_name'];
    $image_info = getimagesize($source);
    
    if (!$image_info) {
        return false;
    }
    
    switch ($image_info['mime']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    if (!$image) {
        return false;
    }
    
    $orig_width = imagesx($image);
    $orig_height = imagesy($image);
    $max_dimension = 1200;
    
    if ($orig_width > $max_dimension || $orig_height > $max_dimension) {
        $ratio = min($max_dimension / $orig_width, $max_dimension / $orig_height);
        $new_width = round($orig_width * $ratio);
        $new_height = round($orig_height * $ratio);
        
        $resized = imagecreatetruecolor($new_width, $new_height);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
        $image = $resized;
    }
    
    $success = imagewebp($image, $destination, 80);
    
    if ($success) {
        return 'uploads/products/' . $filename;
    }
    
    return false;
}
?>