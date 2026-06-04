<?php if (!empty($flash)): ?>
    <div class="flash-message flash-message-<?php echo strpos($flash, 'error') !== false ? 'error' : 'success'; ?>">
        <?php echo htmlspecialchars($flash); ?>
    </div>
<?php endif; ?>