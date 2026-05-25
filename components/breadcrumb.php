<?php
/**
 * Breadcrumb Component
 * Usage: $breadcrumbItems = [['url' => 'index.php', 'label' => 'Home'], ['label' => 'Current']]
 */
if (empty($breadcrumbItems)) return;
?>
<div class="breadcrumb">
    <?php foreach ($breadcrumbItems as $index => $item): ?>
        <?php if ($index > 0): ?>
        <span class="breadcrumb-separator">›</span>
        <?php endif; ?>
        
        <?php if (isset($item['url']) && $index < count($breadcrumbItems) - 1): ?>
        <a href="<?php echo $baseUrl . $item['url']; ?>"><?php echo htmlspecialchars($item['label']); ?></a>
        <?php else: ?>
        <span class="current-page"><?php echo htmlspecialchars($item['label']); ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</div>