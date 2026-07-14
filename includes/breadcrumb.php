<?php

/**
 * Reusable Breadcrumb Component
 * 
 * Usage:
 * $breadcrumbItems = [
 *     ['url' => 'product-listings.php', 'label' => 'Products'],
 *     ['label' => 'Current Page']
 * ];
 * include __DIR__ . '/includes/breadcrumb.php';
 */

if (isset($breadcrumbItems) && !empty($breadcrumbItems)) {
    $baseUrl = getBaseUrl();

    // Detect if we are in the admin folder
    $currentPath = $_SERVER['SCRIPT_NAME'];
    $isAdminPage = strpos($currentPath, '/admin/') !== false;

    // Helper function to build correct URL
    function buildBreadcrumbUrl($url, $isAdminPage, $baseUrl)
    {
        // If URL already has admin/ prefix or starts with /, use as is
        if (strpos($url, 'admin/') === 0 || strpos($url, '/') === 0) {
            return $baseUrl . ltrim($url, '/');
        }

        // If we are in admin folder and URL doesn't have admin/, add it
        if ($isAdminPage && !strpos($url, 'admin/')) {
            return $baseUrl . 'admin/' . $url;
        }

        // Main website page
        return $baseUrl . $url;
    }
?>
    <div class="breadcrumb">
        <a href="<?php echo $baseUrl; ?>index.php">Home</a>
        <?php foreach ($breadcrumbItems as $index => $item): ?>
            <span class="breadcrumb-separator">›</span>
            <?php if (isset($item['url'])): ?>
                <a href="<?php echo buildBreadcrumbUrl($item['url'], $isAdminPage, $baseUrl); ?>"><?php echo htmlspecialchars($item['label']); ?></a>
            <?php else: ?>
                <span class="current-page"><?php echo htmlspecialchars($item['label']); ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php
}
?>