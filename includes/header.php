<?php
/*
 * ConsuTrade - Site Header Component
 * Author: Kamogelo Phale
 * 
 * Simple text-only dropdown navigation for all users.
 */

$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = $auth->isLoggedIn();

if ($is_logged_in && isset($currentUser)) {
    $user_name = $currentUser->getDisplayName();
    $user_profile_image = $currentUser->getProfileImageUrl();
    $user_roles = $currentUser->getRoles();
    $hasBuyerRole = in_array('buyer', $user_roles);
    $hasSellerRole = in_array('seller', $user_roles);
    $hasAdminRole = in_array('admin', $user_roles);
    $primaryRole = $currentUser->getPrimaryRole();
} else {
    $user_name = 'Account';
    $user_profile_image = $baseUrl . 'images/icons/profile-svgrepo-com.svg';
    $user_roles = [];
    $hasBuyerRole = false;
    $hasSellerRole = false;
    $hasAdminRole = false;
    $primaryRole = null;
}

// Show Sell link to everyone - logged out users see it to sign up, logged in buyers see it to upgrade
$show_sell_link = true;

// Only show "New" badge to buyers who can upgrade to seller
$show_upgrade_badge = $is_logged_in && $hasBuyerRole && !$hasSellerRole;

if ($is_logged_in && isset($currentUser) && $currentUser->hasRole('buyer')) {
    $cart_count = $cartRepo->countItems($currentUser->getUserId());
} else {
    $cart_count = 0;
}
?>

<header class="site-header">
    <div class="header-container">
        <!-- Left Section: Logo + Desktop Nav -->
        <div class="header-left">
            <div class="logo">
                <a href="<?php echo $baseUrl; ?>index.php">Consu<span>Trade</span></a>
            </div>
            <!-- South African Flag Badge -->
            <div class="sa-badge">
                <span class="sa-flag">🇿🇦</span>
                <span class="sa-text">Proudly South African</span>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="<?php echo $baseUrl; ?>index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>"><?php t('home'); ?></a></li>
                    <li><a href="<?php echo $baseUrl; ?>product-listings.php" class="<?php echo $current_page == 'product-listings.php' ? 'active' : ''; ?>"><?php t('products'); ?></a></li>
                    <li><a href="<?php echo $baseUrl; ?>sell.php"><?php t('sell'); ?></a></li>
                    <li><a href="<?php echo $baseUrl; ?>about.php" class="<?php echo $current_page == 'about.php' ? 'active' : ''; ?>"><?php t('about'); ?></a></li>
                </ul>
            </nav>
        </div>

        <!-- Center Section: Search Bar -->
        <div class="header-center">
            <div class="search-wrapper">
                <form action="<?php echo $baseUrl; ?>search-results.php" method="GET">
                    <input type="search" name="search" placeholder="<?php t('search_placeholder'); ?>" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" class="search-btn">
                        <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="18" height="18" alt="Search">
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Section: Language + Cart + Notifications + Account -->
        <div class="header-right">
            <!-- Language Selector - Always visible on desktop -->
            <div class="language-dropdown">
                <button class="language-btn" id="languageBtn">
                    <span class="lang-flag"><?php echo strtoupper(getCurrentLanguage()); ?></span>
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div class="language-menu" id="languageMenu">
                    <?php foreach (getAvailableLanguages() as $code => $name): ?>
                        <a href="?lang=<?php echo $code; ?>" class="<?php echo $code == getCurrentLanguage() ? 'active' : ''; ?>">
                            <?php echo getFlagEmoji($code); ?> <?php echo $name; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cart -->
            <?php if (!$is_logged_in || $hasBuyerRole): ?>
                <a href="<?php echo $baseUrl; ?>cart.php" class="cart-link">
                    <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="22" height="22" alt="Cart">
                    <span class="cart-badge"><?php echo $cart_count; ?></span>
                </a>
            <?php endif; ?>

            <!-- Notification Bell -->
            <?php if ($is_logged_in): ?>
                <div class="notifications-dropdown">
                    <button class="notif-btn" id="notifBtn" aria-label="Notifications">
                        <img src="<?php echo $baseUrl; ?>images/icons/bell-svgrepo-com.svg" width="20" height="20" alt="Notifications">
                        <span class="notif-badge" id="notifBadge"></span>
                    </button>
                    <div class="notif-menu" id="notifMenu">
                        <div class="notif-list">
                            <div class="notif-empty">No notifications yet</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Account Dropdown -->
            <?php if ($is_logged_in): ?>
                <div class="account-dropdown">
                    <button class="account-btn" id="accountBtn">
                        <img src="<?php echo $user_profile_image; ?>" alt="<?php echo htmlspecialchars($user_name); ?>" class="account-avatar" onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
                        <span class="account-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="dropdown-menu" id="accountDropdown">
                        <a href="<?php echo $baseUrl; ?>profile.php"><?php t('my_profile'); ?></a>

                        <?php if ($hasBuyerRole): ?>
                            <a href="<?php echo $baseUrl; ?>orders.php"><?php t('orders'); ?></a>
                            <a href="<?php echo $baseUrl; ?>cart.php"><?php t('my_cart'); ?></a>
                        <?php endif; ?>

                        <?php if ($hasSellerRole): ?>
                            <?php if ($hasBuyerRole): ?>
                                <hr><?php endif; ?>
                            <a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php"><?php t('seller_dashboard'); ?></a>
                            <a href="<?php echo $baseUrl; ?>admin/my-products.php"><?php t('my_products'); ?></a>
                            <a href="<?php echo $baseUrl; ?>orders.php"><?php t('orders'); ?></a>
                        <?php endif; ?>

                        <?php if ($hasAdminRole): ?>
                            <?php if ($hasBuyerRole || $hasSellerRole): ?>
                                <hr><?php endif; ?>
                            <a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php"><?php t('admin_dashboard'); ?></a>
                            <a href="<?php echo $baseUrl; ?>admin/users.php"><?php t('users'); ?></a>
                            <a href="<?php echo $baseUrl; ?>admin/all-orders.php"><?php t('all_orders'); ?></a>
                        <?php endif; ?>

                        <hr>
                        <a href="<?php echo $baseUrl; ?>php/endpoints/auth/logout.php" class="logout-link"><?php t('logout'); ?></a>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <button class="login-btn" id="loginBtn"><?php t('login'); ?></button>
                    <button class="signup-btn" id="registerBtn"><?php t('sign_up'); ?></button>
                </div>
            <?php endif; ?>

            <!-- Mobile Search Toggle -->
            <button class="mobile-search-toggle" id="mobileSearchIcon" aria-label="Search">
                <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="20" height="20" alt="Search">
            </button>

            <!-- Mobile Menu Toggle -->
            <button class="menu-toggle" id="menuToggle" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <!-- Mobile Search -->
    <div class="mobile-search" id="mobileSearch">
        <form action="<?php echo $baseUrl; ?>product-listings.php" method="GET">
            <input type="search" name="search" placeholder="<?php t('search_placeholder'); ?>">
            <button type="submit">
                <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="18" height="18" alt="Search">
            </button>
        </form>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <div class="mobile-logo">
                <a href="<?php echo $baseUrl; ?>index.php">Consu<span>Trade</span></a>
            </div>
            <button class="close-menu" id="closeMenu" aria-label="Close menu">
                <span></span><span></span>
            </button>
        </div>

        <?php if ($is_logged_in): ?>
            <!-- User Profile Card -->
            <div class="mobile-profile-card">
                <img src="<?php echo $user_profile_image; ?>" alt="<?php echo htmlspecialchars($user_name); ?>" class="mobile-profile-img" onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
                <div class="mobile-profile-info">
                    <span class="mobile-profile-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <span class="mobile-profile-email"><?php echo htmlspecialchars($currentUser->getEmail()); ?></span>
                    <span class="mobile-profile-role">
                        <?php
                        $roleLabels = [];
                        if ($hasBuyerRole) $roleLabels[] = 'Buyer';
                        if ($hasSellerRole) $roleLabels[] = 'Seller';
                        if ($hasAdminRole) $roleLabels[] = 'Admin';
                        echo implode(' • ', $roleLabels);
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="mobile-quick-actions">
            <?php if (!$is_logged_in || $hasBuyerRole): ?>
                <a href="<?php echo $baseUrl; ?>cart.php" class="mobile-quick-action">
                    <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="20" height="20" alt="Cart" class="quick-icon">
                    <span class="quick-label"><?php t('cart'); ?></span>
                    <span class="quick-badge cart-badge-mobile"><?php echo $cart_count; ?></span>
                </a>
            <?php endif; ?>

            <?php if ($is_logged_in): ?>
                <a href="<?php echo $baseUrl; ?>orders.php" class="mobile-quick-action">
                    <img src="<?php echo $baseUrl; ?>images/icons/clipboard-svgrepo-com.svg" width="20" height="20" alt="Orders" class="quick-icon">
                    <span class="quick-label"><?php t('orders'); ?></span>
                </a>
                <a href="<?php echo $baseUrl; ?>messages.php" class="mobile-quick-action">
                    <img src="<?php echo $baseUrl; ?>images/icons/comment-svgrepo-com.svg" width="20" height="20" alt="Messages" class="quick-icon">
                    <span class="quick-label"><?php t('messages'); ?></span>
                    <span class="quick-badge">2</span>
                </a>
            <?php endif; ?>

            <a href="<?php echo $baseUrl; ?>product-listings.php" class="mobile-quick-action">
                <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="20" height="20" alt="Browse" class="quick-icon">
                <span class="quick-label"><?php t('browse'); ?></span>
            </a>
        </div>

        <!-- Search in menu -->
        <div class="mobile-menu-search">
            <form action="<?php echo $baseUrl; ?>search-results.php" method="GET">
                <input type="search" name="search" placeholder="<?php t('search_placeholder'); ?>" class="mobile-menu-search-input">
                <button type="submit">
                    <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="16" height="16" alt="Search">
                </button>
            </form>
        </div>

        <!-- Main Navigation -->
        <ul class="mobile-nav-links">
            <!-- Main Pages -->
            <li class="mobile-nav-section"><?php t('menu'); ?></li>
            <li>
                <a href="<?php echo $baseUrl; ?>index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <img src="<?php echo $baseUrl; ?>images/icons/products-svgrepo-com.svg" width="18" height="18" alt="" class="nav-icon">
                    <span class="nav-label"><?php t('home'); ?></span>
                </a>
            </li>
            <li>
                <a href="<?php echo $baseUrl; ?>product-listings.php" class="<?php echo $current_page == 'product-listings.php' ? 'active' : ''; ?>">
                    <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="18" height="18" alt="" class="nav-icon">
                    <span class="nav-label"><?php t('products'); ?></span>
                </a>
            </li>
            <li>
                <a href="<?php echo $baseUrl; ?>sell.php">
                    <img src="<?php echo $baseUrl; ?>images/icons/sell-svgrepo-com.svg" width="18" height="18" alt="" class="nav-icon">
                    <span class="nav-label"><?php t('sell'); ?></span>
                    <?php if ($show_upgrade_badge): ?>
                        <span class="nav-badge new">New</span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="<?php echo $baseUrl; ?>about.php" class="<?php echo $current_page == 'about.php' ? 'active' : ''; ?>">
                    <img src="<?php echo $baseUrl; ?>images/icons/info-svgrepo-com.svg" width="18" height="18" alt="" class="nav-icon">
                    <span class="nav-label"><?php t('about'); ?></span>
                </a>
            </li>

            <!-- Account Section -->
            <?php if ($is_logged_in): ?>
                <li class="mobile-nav-section"><?php t('account'); ?></li>
                <li>
                    <a href="<?php echo $baseUrl; ?>profile.php">
                        <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" width="18" height="18" alt="" class="nav-icon">
                        <span class="nav-label"><?php t('my_profile'); ?></span>
                    </a>
                </li>

                <?php if ($hasBuyerRole): ?>
                    <li>
                        <a href="<?php echo $baseUrl; ?>orders.php">
                            <img src="<?php echo $baseUrl; ?>images/icons/clipboard-svgrepo-com.svg" width="18" height="18" alt="" class="nav-icon">
                            <span class="nav-label"><?php t('orders'); ?></span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($hasSellerRole): ?>
                    <li class="mobile-nav-sub-section"><?php t('seller'); ?></li>
                    <li>
                        <a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php">
                            <img src="<?php echo $baseUrl; ?>images/icons/dashboard-svgrepo-com.svg" width="18" height="18" alt="" class="nav-icon">
                            <span class="nav-label"><?php t('seller_dashboard'); ?></span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $baseUrl; ?>admin/my-products.php">
                            <img src="<?php echo $baseUrl; ?>images/icons/products-svgrepo-com.svg" width="18" height="18" alt="" class="nav-icon">
                            <span class="nav-label"><?php t('my_products'); ?></span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($hasAdminRole): ?>
                    <li class="mobile-nav-sub-section"><?php t('admin'); ?></li>
                    <li>
                        <a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php">
                            <img src="<?php echo $baseUrl; ?>images/icons/dashboard-svgrepo-com.svg" width="18" height="18" alt="" class="nav-icon">
                            <span class="nav-label"><?php t('admin_dashboard'); ?></span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $baseUrl; ?>admin/users.php">
                            <img src="<?php echo $baseUrl; ?>images/icons/users-svgrepo-com.svg" width="18" height="18" alt="" class="nav-icon">
                            <span class="nav-label"><?php t('users'); ?></span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $baseUrl; ?>admin/all-orders.php">
                            <img src="<?php echo $baseUrl; ?>images/icons/clipboard-svgrepo-com.svg" width="18" height="18" alt="" class="nav-icon">
                            <span class="nav-label"><?php t('all_orders'); ?></span>
                        </a>
                    </li>
                <?php endif; ?>

            <?php else: ?>
                <!-- Auth Section (not logged in) -->
                <li class="mobile-nav-section"><?php t('account'); ?></li>
                <li>
                    <button class="mobile-login-btn" id="mobileLoginBtn">
                        <img src="<?php echo $baseUrl; ?>images/icons/login-svgrepo-com.svg" width="18" height="18" alt="" class="nav-icon">
                        <span class="nav-label"><?php t('login'); ?></span>
                    </button>
                </li>
                <li>
                    <button class="mobile-signup-btn" id="mobileRegisterBtn">
                        <img src="<?php echo $baseUrl; ?>images/icons/register-svgrepo-com.svg" width="18" height="18" alt="" class="nav-icon">
                        <span class="nav-label"><?php t('sign_up'); ?></span>
                        <span class="nav-badge free">Free</span>
                    </button>
                </li>
            <?php endif; ?>

            <!-- Language Section -->
            <li class="mobile-nav-section"><?php t('language'); ?></li>
            <li class="mobile-lang-compact">
                <div class="mobile-lang-current" id="mobileLangToggle">
                    <span class="lang-flag"><?php echo getFlagEmoji(getCurrentLanguage()); ?></span>
                    <span class="lang-name"><?php echo getAvailableLanguages()[getCurrentLanguage()] ?? 'English'; ?></span>
                    <img src="<?php echo $baseUrl; ?>images/icons/chevron-down-svgrepo-com.svg" width="14" height="14" alt="Toggle" class="lang-chevron">
                </div>
                <div class="mobile-lang-options" id="mobileLangOptions">
                    <?php foreach (getAvailableLanguages() as $code => $name): ?>
                        <a href="?lang=<?php echo $code; ?>" class="mobile-lang-option <?php echo $code == getCurrentLanguage() ? 'active' : ''; ?>">
                            <span class="lang-flag"><?php echo getFlagEmoji($code); ?></span>
                            <span class="lang-name"><?php echo $name; ?></span>
                            <?php if ($code == getCurrentLanguage()): ?>
                                <span class="lang-check">✓</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </li>

            <!-- Logout (if logged in) -->
            <?php if ($is_logged_in): ?>
                <li class="mobile-divider"></li>
                <li class="mobile-logout">
                    <a href="<?php echo $baseUrl; ?>php/endpoints/auth/logout.php" class="logout-link">
                        <img src="<?php echo $baseUrl; ?>images/icons/logout-svgrepo-com.svg" width="18" height="18" alt="" class="nav-icon">
                        <span class="nav-label"><?php t('logout'); ?></span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="menu-overlay" id="menuOverlay"></div>
</header>

<!-- GLOBAL JAVASCRIPT VARIABLES -->
<script>
    var baseUrl = '<?php echo $baseUrl; ?>';
    var currentUserId = <?php echo $currentUser ? $currentUser->getUserId() : 0; ?>;
    var currentUserRole = '<?php echo $currentUserRole ?? ''; ?>';
    var isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

    // Translations for JavaScript
    var translations = <?php
                        $lang = getCurrentLanguage();
                        $trans = getTranslations()[$lang] ?? [];
                        echo json_encode($trans);
                        ?>;
</script>