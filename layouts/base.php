<?php
/*
 * ConsuTrade - Base Layout Template
 * Author: Kamogelo Phale
 * 
 * "DRY - Don't Repeat Yourself. That's what layouts are for."
 * This is the foundation for all pages. Set your variables, 
 * and let this file handle the boilerplate HTML.
 * 
 * Required: $pageTitle
 * Optional: $pageDescription, $pageCss, $pageJs, $bodyClass, $inlineScript
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'ConsuTrade'; ?></title>
    <meta name="author" content="Kamogelo Phale">
    <?php if (!empty($pageDescription)): ?>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <?php endif; ?>
    
    <!-- Master CSS - One stylesheet to rule them all -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    
    <!-- Page specific CSS for unique page styles -->
    <?php if (!empty($pageCss)): ?>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/pages/<?php echo $pageCss; ?>">
    <?php endif; ?>
</head>
<body class="<?php echo $bodyClass ?? ''; ?>">

<?php include $basePath . '/includes/header.php'; ?>

<main>
    <?php echo $content; ?>
</main>

<?php include $basePath . '/includes/footer.php'; ?>

<!-- jQuery - Because vanilla JS is overrated sometimes -->
<script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>

<!-- Main JS - The brains of the operation -->
<script src="<?php echo $baseUrl; ?>js/main.js"></script>

<!-- Page specific JavaScript -->
<?php if (!empty($pageJs)): ?>
<script src="<?php echo $baseUrl; ?>js/pages/<?php echo $pageJs; ?>"></script>
<?php endif; ?>

<!-- Inline scripts for page-specific initialization -->
<?php if (!empty($inlineScript)): ?>
<script><?php echo $inlineScript; ?></script>
<?php endif; ?>
</body>
</html>