<?php

/**
 * ConsuTrade - Search Bar Component
 * Author: Kamogelo Phale
 * 
 * Displays a search form with optional status preservation
 * 
 * Usage:
 * $searchTerm = $_GET['search'] ?? '';
 * $currentStatus = $_GET['status'] ?? 'all';
 * $placeholder = 'Search by order number or customer...';
 * include __DIR__ . '/includes/search-bar.php';
 */

$searchTerm = $searchTerm ?? '';
$currentStatus = $currentStatus ?? 'all';
$placeholder = $placeholder ?? 'Search...';
$searchName = $searchName ?? 'search';
$showClearButton = $showClearButton ?? true;
$formMethod = $formMethod ?? 'GET';
?>
<div class="search-bar">
    <form method="<?php echo $formMethod; ?>" action="">
        <?php if ($currentStatus !== 'all'): ?>
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($currentStatus); ?>">
        <?php endif; ?>
        <input type="text" name="<?php echo $searchName; ?>"
            placeholder="<?php echo htmlspecialchars($placeholder); ?>"
            value="<?php echo htmlspecialchars($searchTerm); ?>">
        <button type="submit">
            <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg"
                width="16" height="16" alt="Search">
        </button>
        <?php if ($showClearButton && !empty($searchTerm)): ?>
            <a href="?status=<?php echo urlencode($currentStatus); ?>" class="clear-search">Clear</a>
        <?php endif; ?>
    </form>
</div>