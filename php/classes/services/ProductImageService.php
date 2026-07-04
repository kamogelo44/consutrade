<?php

/**
 * ConsuTrade - ProductImageService
 * 
 * SIMPLIFIED VERSION - Client-side compression handles the heavy lifting.
 * Server just moves files and keeps track of them.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */

class ProductImageService
{
    private string $uploadPath;
    private string $uploadUrl;
    private int $maxFileSize = 5 * 1024 * 1024; // 5MB (files are already compressed on client)

    public function __construct()
    {
        $this->uploadPath = '/home/site/wwwroot/uploads/products/';
        $this->uploadUrl = '/uploads/products/';

        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Upload and save product image (NO PROCESSING - just move the file)
     * 
     * @param array $file The uploaded file from $_FILES
     * @param int $sellerId The seller's ID (used in filename)
     * @param string $productTitle The product title (used in filename)
     * @param string $prefix Filename prefix (main, gallery, thumb)
     * @return string|false Relative URL on success, false on failure
     */
    public function uploadImage(array $file, int $sellerId, string $productTitle, string $prefix = 'main'): string|false
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log("[ProductImageService] Upload error: " . $file['error']);
            return false;
        }

        // Check file size (files are already compressed on client)
        if ($file['size'] > $this->maxFileSize) {
            error_log("[ProductImageService] File too large: " . $file['size'] . " bytes (max 5MB)");
            return false;
        }

        // Get file extension (should be .webp from client compression)
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['webp', 'jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($extension, $allowedExtensions)) {
            error_log("[ProductImageService] Invalid extension: " . $extension);
            return false;
        }

        // Generate unique filename
        $safeTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $productTitle);
        $safeTitle = substr($safeTitle, 0, 50) ?: 'product';
        $filename = sprintf('%d_%d_%s_%s.%s', $sellerId, time(), $prefix, $safeTitle, $extension);
        $destination = $this->uploadPath . $filename;

        // Just move the file - NO PROCESSING
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            error_log("[ProductImageService] SUCCESS: " . $filename);
            return $this->uploadUrl . $filename;
        }

        error_log("[ProductImageService] FAILED to move file to: " . $destination);
        return false;
    }

    /**
     * Delete a product image file from disk.
     *
     * @param string|null $imageUrl Relative URL of the image
     * @return bool True if deleted or not needed, false on failure
     */
    public function deleteImageFile(?string $imageUrl): bool
    {
        if (empty($imageUrl)) {
            return true;
        }

        $filename = basename($imageUrl);
        $filePath = $this->uploadPath . $filename;

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return true;
    }

    /**
     * Get full URL for a product image.
     *
     * @param string|null $imageUrl Stored image path
     * @return string Full URL with fallback to default
     */
    public function getImageUrl(?string $imageUrl): string
    {
        $baseUrl = getBaseUrl();

        if (empty($imageUrl)) {
            return $baseUrl . 'images/default-product.png';
        }

        if (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://')) {
            return $imageUrl;
        }

        $cleanPath = ltrim($imageUrl, '/');
        $fullPath = $this->uploadPath . basename($cleanPath);

        if (file_exists($fullPath)) {
            return $baseUrl . $cleanPath;
        }

        return $baseUrl . 'images/default-product.png';
    }

    /**
     * Get user-friendly upload error message.
     *
     * @param int $errorCode PHP upload error code
     * @return string
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File too large (max 5MB)',
            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
            default => 'Unknown upload error',
        };
    }
}
