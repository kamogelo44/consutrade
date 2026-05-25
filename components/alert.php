<?php
/**
 * Alert Component
 * Usage: include 'components/alert.php' with $alertType, $alertMessage
 */
if (empty($alertMessage)) return;
?>
<div class="alert alert-<?php echo $alertType ?? 'info'; ?>">
    <?php echo htmlspecialchars($alertMessage); ?>
</div>