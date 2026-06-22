<?php

/**
 * ConsuTrade - ProductImageService
 *
 * Handles product image upload, manipulation, and file management.
 * Separate from repositories to follow Single Responsibility Principle.
 *
 * @author Kamogelo Phale
 * @version 1.0.0
 */

class ProductImageService
{
    /** @var string Absolute path to upload directory */
    private string $uploadPath;

    /** @var string Web-accessible URL path to uploads */
    private string $uploadUrl;

    public function __construct()
    {
        $this->initializeUploadPaths();
    }

    /**
     * Initialize upload directory paths.
     */
    private function initializeUploadPaths(): void
    {
        $projectRoot = $this->detectProjectRoot();
        $this->uploadPath = $projectRoot . '/uploads/products/';
        $this->uploadUrl = '/uploads/products/';

        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Detect the project root directory.
     *
     * @return string Absolute path to project root
     */
    private function detectProjectRoot(): string
    {
        $currentDir = __DIR__;

        for ($i = 0; $i < 5; $i++) {
            if (is_dir($currentDir . '/uploads') || file_exists($currentDir . '/init.php')) {
                return $currentDir;
            }
            $currentDir = dirname($currentDir);
        }

        return rtrim($_SERVER['DOCUMENT_ROOT'], '/');
    }

    /**
     * Upload and convert product image to WebP.
     *
     * @param array $file The uploaded file from $_FILES
     * @param int $sellerId The seller's ID (used in filename)
     * @param string $productTitle The product title (used in filename)
     * @param string $prefix Filename prefix (main, gallery, thumb)
     * @return string|false Relative URL on success, false on failure
     */
    public function uploadImage(array $file, int $sellerId, string $productTitle, string $prefix = 'main'): string|false
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log("[ProductImageService] Upload error: " . $this->getUploadErrorMessage($file['error']));
            return false;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            error_log("[ProductImageService] File too large: " . $file['size'] . " bytes");
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $image = $this->loadImageFromFile($file['tmp_name'], $mimeType);
        if (!$image) {
            error_log("[ProductImageService] Failed to load image: " . $mimeType);
            return false;
        }

        $image = $this->resizeImage($image, 1200);

        $safeTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $productTitle);
        $safeTitle = substr($safeTitle, 0, 50) ?: 'product';
        $filename = sprintf('%d_%d_%s_%s.webp', $sellerId, time(), $prefix, $safeTitle);
        $destination = $this->uploadPath . $filename;

        $success = imagewebp($image, $destination, 80);
        imagedestroy($image);

        if ($success && file_exists($destination)) {
            return $this->uploadUrl . $filename;
        }

        error_log("[ProductImageService] Failed to save WebP: " . $destination);
        return false;
    }

    /**
     * Load image from file based on MIME type.
     *
     * @param string $filePath Path to uploaded file
     * @param string $mimeType MIME type of the file
     * @return GdImage|false
     */
    private function loadImageFromFile(string $filePath, string $mimeType): mixed
    {
        return match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($filePath),
            'image/png' => $this->loadPngWithAlpha($filePath),
            'image/webp' => imagecreatefromwebp($filePath),
            'image/gif' => imagecreatefromgif($filePath),
            default => false,
        };
    }

    /**
     * Load PNG image preserving transparency.
     *
     * @param string $filePath Path to PNG file
     * @return GdImage|false
     */
    private function loadPngWithAlpha(string $filePath): mixed
    {
        $image = imagecreatefrompng($filePath);
        if ($image) {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }
        return $image;
    }

    /**
     * Resize image to fit within max dimensions.
     *
     * @param GdImage $image Source image
     * @param int $maxSize Maximum width/height in pixels
     * @return GdImage Resized image (original if no resize needed)
     */
    private function resizeImage($image, int $maxSize): mixed
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxSize && $height <= $maxSize) {
            return $image;
        }

        $ratio = min($maxSize / $width, $maxSize / $height);
        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
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

        $filename = basename($imageUrl);
        if (file_exists($this->uploadPath . $filename)) {
            return $baseUrl . ltrim($imageUrl, '/');
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
