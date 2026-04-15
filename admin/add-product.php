<?php
/*
 * ConsuTrade - Add Product Page
 * Author: Kamogelo Phale
 * 
 * Separate page for sellers to add new products with multiple images
 */

session_start();

$baseUrl = "/www/consutrade/";

// Check if user is logged in and is a seller
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'seller') {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Include header
include dirname(__DIR__) . '/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - ConsuTrade Seller</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/header.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/login-signup.css">
    <link rel="stylesheet" href="css/add-product.css">
</head>
<body class="add-product-page">
    <main class="add-product-container">
        <div class="add-product-header">
            <h1>Add New Product</h1>
            <p>Fill in the details below to list your product on ConsuTrade</p>
        </div>

        <div class="add-product-layout">
            <!-- Left Column: Tips & Progress -->
            <div class="left-column tips-column">
                <div class="tips-card">
                    <h3>
                        <img src="<?php echo $baseUrl; ?>images/icons/valid-document-svgrepo-com.svg" width="24px" height="24px" alt="Tips" class="icon">
                        Listing Tips
                    </h3>
                    <ul class="tips-list">
                        <li>
                            <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16px" height="16px" alt="check">
                            Use clear, high-quality photos
                        </li>
                        <li>
                            <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16px" height="16px" alt="check">
                            Write detailed product descriptions
                        </li>
                        <li>
                            <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16px" height="16px" alt="check">
                            Price competitively
                        </li>
                        <li>
                            <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16px" height="16px" alt="check">
                            Include product condition
                        </li>
                        <li>
                            <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16px" height="16px" alt="check">
                            Respond quickly to buyer questions
                        </li>
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
                <form id="add-product-form" action="../php/add-product.php" method="post" enctype="multipart/form-data">
                    <!-- Basic Information -->
                    <fieldset class="form-section">
                        <legend>Basic Information</legend>
                        
                        <div class="form-group">
                            <label for="product-title">Product Title *</label>
                            <input type="text" id="product-title" name="title" required 
                                   placeholder="e.g., Handmade Leather Bag" onkeyup="updateProgress()">
                        </div>

                        <div class="form-group">
                            <label for="product-category">Category *</label>
                            <select id="product-category" name="category_id" required onchange="updateProgress()">
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
                                <label for="product-price">Price (R) *</label>
                                <input type="number" id="product-price" name="price" step="0.01" required 
                                       placeholder="0.00" onkeyup="updateProgress()">
                            </div>
                            <div class="form-group half">
                                <label for="product-condition">Condition *</label>
                                <select id="product-condition" name="condition" required onchange="updateProgress()">
                                    <option value="">Select Condition</option>
                                    <option value="New">Brand New</option>
                                    <option value="Like New">Like New</option>
                                    <option value="Good">Good</option>
                                    <option value="Fair">Fair</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="product-description">Description *</label>
                            <textarea id="product-description" name="description" rows="5" 
                                      placeholder="Describe your product in detail..." 
                                      onkeyup="updateProgress()"></textarea>
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
                            <label>Main Product Image *</label>
                            <div class="image-upload-container" id="main-image-container">
                                <input type="file" id="main-image" name="main_image" accept="image/*" required>
                                <div class="image-preview" id="main-image-preview">
                                    <div class="upload-placeholder">
                                        <img src="<?php echo $baseUrl; ?>images/icons/photos-filled-svgrepo-com.svg" width="48px" height="48px" alt="Upload">
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
                                <div class="thumbnail-upload" data-index="0">
                                    <input type="file" name="thumbnail_0" accept="image/*" class="thumbnail-input">
                                    <div class="thumbnail-preview">
                                        <div class="upload-placeholder-small">
                                            +
                                        </div>
                                    </div>
                                </div>
                                <div class="thumbnail-upload" data-index="1">
                                    <input type="file" name="thumbnail_1" accept="image/*" class="thumbnail-input">
                                    <div class="thumbnail-preview">
                                        <div class="upload-placeholder-small">
                                            +
                                        </div>
                                    </div>
                                </div>
                                <div class="thumbnail-upload" data-index="2">
                                    <input type="file" name="thumbnail_2" accept="image/*" class="thumbnail-input">
                                    <div class="thumbnail-preview">
                                        <div class="upload-placeholder-small">
                                            +
                                        </div>
                                    </div>
                                </div>
                                <div class="thumbnail-upload" data-index="3">
                                    <input type="file" name="thumbnail_3" accept="image/*" class="thumbnail-input">
                                    <div class="thumbnail-preview">
                                        <div class="upload-placeholder-small">
                                            +
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Location & Contact -->
                    <fieldset class="form-section">
                        <legend>
                            <img src="<?php echo $baseUrl; ?>images/icons/delivery-svgrepo-com.svg" width="20px" height="20px" alt="Location">
                            Location & Contact
                        </legend>
                        
                        <div class="form-group">
                            <label for="product-location">Location</label>
                            <input type="text" id="product-location" name="location" 
                                   placeholder="e.g., Johannesburg, Soweto" onkeyup="updateProgress()">
                        </div>
                    </fieldset>

                    <div class="form-actions">
                        <button type="button" class="cancel-btn" onclick="window.location.href='seller-dashboard.php'">
                            <img src="<?php echo $baseUrl; ?>images/icons/form-close-svgrepo-com.svg" width="16px" height="16px" alt="Cancel">
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
    </main>
    
    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
    <script>
        // Update progress bar based on form completion
        function updateProgress() {
            var fields = ['product-title', 'product-category', 'product-price', 'product-condition', 'product-description'];
            var filled = 0;
            
            for (var i = 0; i < fields.length; i++) {
                var field = document.getElementById(fields[i]);
                if (field && field.value && field.value !== '') {
                    filled++;
                }
            }
            
            // Check main image
            var mainImage = document.getElementById('main-image');
            if (mainImage && mainImage.files.length > 0) {
                filled++;
            }
            
            var totalFields = fields.length + 1;
            var percentage = Math.round((filled / totalFields) * 100);
            
            var progressBar = document.getElementById('progress-bar');
            var progressText = document.getElementById('progress-text');
            
            if (progressBar) progressBar.style.width = percentage + '%';
            if (progressText) progressText.textContent = percentage + '% Complete';
        }

        // Main image preview
        var mainImageInput = document.getElementById('main-image');
        if (mainImageInput) {
            mainImageInput.addEventListener('change', function(e) {
                var file = e.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(event) {
                        var preview = document.getElementById('main-image-preview');
                        preview.innerHTML = '<img src="' + event.target.result + '" alt="Product preview">';
                        updateProgress();
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Thumbnail previews
        var thumbnailInputs = document.querySelectorAll('.thumbnail-input');
        thumbnailInputs.forEach(function(input, index) {
            input.addEventListener('change', function(e) {
                var file = e.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    var container = document.querySelector('.thumbnail-upload[data-index="' + index + '"] .thumbnail-preview');
                    reader.onload = function(event) {
                        container.innerHTML = '<img src="' + event.target.result + '" alt="Thumbnail">';
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Make thumbnail-upload clickable
            var thumbnailDiv = document.querySelector('.thumbnail-upload[data-index="' + index + '"]');
            if (thumbnailDiv) {
                thumbnailDiv.addEventListener('click', function() {
                    input.click();
                });
            }
        });
        
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