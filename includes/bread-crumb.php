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

// Only render if breadcrumb items are defined
if (isset($breadcrumbItems) && !empty($breadcrumbItems)) {
    $baseUrl = getBaseUrl();
    ?>
    <div class="breadcrumb">
        <a href="<?php echo $baseUrl; ?>index.php">Home</a>
        <?php foreach ($breadcrumbItems as $item): ?>
            <span class="breadcrumb-separator">›</span>
            <?php if (isset($item['url'])): ?>
                <a href="<?php echo $baseUrl . $item['url']; ?>"><?php echo htmlspecialchars($item['label']); ?></a>
            <?php else: ?>
                <span class="breadcrumb-current"><?php echo htmlspecialchars($item['label']); ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php
}
?>