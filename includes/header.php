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

$show_sell_link = !$is_logged_in;

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
                    <?php if ($show_sell_link): ?>
                        <li><a href="<?php echo $baseUrl; ?>sell.php"><?php t('sell'); ?></a></li>
                    <?php endif; ?>
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

        <!-- Right Section: Language + Cart + Account -->
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
                            <?php echo $name; ?>
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
            <button class="mobile-search-toggle" id="mobileSearchIcon">
                <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="20" height="20" alt="Search">
            </button>

            <!-- Mobile Menu Toggle -->
            <button class="menu-toggle" id="menuToggle">
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
            <button class="close-menu" id="closeMenu">
                <span></span><span></span>
            </button>
        </div>

        <?php if ($is_logged_in): ?>
            <div class="mobile-profile">
                <img src="<?php echo $user_profile_image; ?>" alt="<?php echo htmlspecialchars($user_name); ?>" class="mobile-profile-img" onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
                <div class="mobile-profile-info">
                    <span class="mobile-profile-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <span class="mobile-profile-email"><?php echo htmlspecialchars($currentUser->getEmail()); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <ul class="mobile-nav-links">
            <li><a href="<?php echo $baseUrl; ?>index.php"><?php t('home'); ?></a></li>
            <li><a href="<?php echo $baseUrl; ?>product-listings.php"><?php t('products'); ?></a></li>
            <?php if ($show_sell_link): ?>
                <li><a href="<?php echo $baseUrl; ?>sell.php"><?php t('sell'); ?></a></li>
            <?php endif; ?>
            <li><a href="<?php echo $baseUrl; ?>about.php"><?php t('about'); ?></a></li>

            <!-- Mobile Language Selector -->
            <li class="mobile-divider"></li>
            <li class="mobile-lang-label"><?php t('language'); ?></li>
            <?php foreach (getAvailableLanguages() as $code => $name): ?>
                <li>
                    <a href="?lang=<?php echo $code; ?>" class="<?php echo $code == getCurrentLanguage() ? 'active' : ''; ?>">
                        <?php echo $name; ?>
                    </a>
                </li>
            <?php endforeach; ?>

            <?php if ($is_logged_in): ?>
                <li class="mobile-divider"></li>
                <li><a href="<?php echo $baseUrl; ?>profile.php"><?php t('my_profile'); ?></a></li>

                <?php if ($hasBuyerRole): ?>
                    <li><a href="<?php echo $baseUrl; ?>orders.php"><?php t('orders'); ?></a></li>
                    <li><a href="<?php echo $baseUrl; ?>cart.php"><?php t('my_cart'); ?></a></li>
                <?php endif; ?>

                <?php if ($hasSellerRole): ?>
                    <li><a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php"><?php t('seller_dashboard'); ?></a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/my-products.php"><?php t('my_products'); ?></a></li>
                    <li><a href="<?php echo $baseUrl; ?>orders.php"><?php t('orders'); ?></a></li>
                <?php endif; ?>

                <?php if ($hasAdminRole): ?>
                    <li><a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php"><?php t('admin_dashboard'); ?></a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/users.php"><?php t('users'); ?></a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/all-orders.php"><?php t('all_orders'); ?></a></li>
                <?php endif; ?>

                <li class="mobile-divider"></li>
                <li><a href="<?php echo $baseUrl; ?>php/endpoints/auth/logout.php" class="logout-link"><?php t('logout'); ?></a></li>
            <?php else: ?>
                <li class="mobile-divider"></li>
                <li><button class="mobile-login-btn" id="mobileLoginBtn"><?php t('login'); ?></button></li>
                <li><button class="mobile-signup-btn" id="mobileRegisterBtn"><?php t('sign_up'); ?></button></li>
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

<!-- Login Modal -->
<div id="login-modal" class="modal">
    <div class="modal-content">
        <button class="btn-close"></button>
        <div class="modal-header">
            <h1>Consu<span>Trade</span></h1>
            <p><?php t('welcome_back'); ?></p>
        </div>
        <div id="login-error-container" class="error-container" style="display: none;"></div>
        <form id="login-form" class="login-form" method="POST" action="<?php echo $baseUrl; ?>php/endpoints/auth/login.php">
            <input type="hidden" name="role_type" value="buyer">
            <div class="input-group">
                <label for="login-email"><?php t('email_address'); ?></label>
                <input type="email" id="login-email" name="email" placeholder="<?php t('email_address'); ?>" required>
            </div>
            <div class="input-group">
                <label for="login-password"><?php t('password'); ?></label>
                <div class="password-field-wrapper">
                    <input type="password" id="login-password" name="password" placeholder="<?php t('password'); ?>" required>
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('login-password', this)">
                        <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="18" height="18">
                    </button>
                </div>
            </div>
            <div class="reset-pass"><a href="#" id="forgotPasswordLink"><?php t('forgot_password'); ?></a></div>
            <button type="submit" class="submit-btn"><?php t('login'); ?></button>
            <div class="register-link"><?php t('no_account'); ?> <a href="#" id="switch-to-register"><?php t('register_here'); ?></a></div>
        </form>
    </div>
</div>

<!-- Register Modal -->
<div id="register-modal" class="modal">
    <div class="modal-content">
        <button class="btn-close"></button>
        <div class="modal-header">
            <h1>Consu<span>Trade</span></h1>
            <p><?php t('create_account'); ?></p>
        </div>
        <div id="register-error-container" class="error-container" style="display: none;"></div>
        <form id="register-form" class="register-form" method="POST" action="<?php echo $baseUrl; ?>php/endpoints/auth/register.php">
            <div class="input-group">
                <label for="register-full-name"><?php t('full_name'); ?></label>
                <input type="text" id="register-full-name" name="full_name" placeholder="<?php t('full_name'); ?>" required>
            </div>
            <div class="input-group">
                <label for="register-email"><?php t('email_address'); ?></label>
                <input type="email" id="register-email" name="email" placeholder="<?php t('email_address'); ?>" required>
            </div>
            <div class="input-group">
                <label for="register-phone"><?php t('phone_number'); ?></label>
                <input type="tel" id="register-phone" name="phone" placeholder="<?php t('phone_number'); ?>" required>
            </div>
            <div class="input-group">
                <label for="register-password"><?php t('password'); ?></label>
                <div class="password-field-wrapper">
                    <input type="password" id="register-password" name="password" placeholder="<?php t('password'); ?>" required>
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('register-password', this)">
                        <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="18" height="18">
                    </button>
                </div>
            </div>
            <div class="input-group">
                <label for="register-confirm-password"><?php t('confirm_password'); ?></label>
                <div class="password-field-wrapper">
                    <input type="password" id="register-confirm-password" name="confirm_password" placeholder="<?php t('confirm_password'); ?>" required>
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('register-confirm-password', this)">
                        <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="18" height="18">
                    </button>
                </div>
            </div>

            <fieldset class="user-type">
                <legend><?php t('i_want_to'); ?></legend>
                <div class="radio-buttons">
                    <input type="radio" id="buyer" name="role" value="buyer" checked>
                    <label for="buyer" class="radio-btn radio"><?php t('buy_products'); ?></label>
                    <input type="radio" id="seller" name="role" value="seller">
                    <label for="seller" class="radio-btn radio"><?php t('sell_products'); ?></label>
                </div>
            </fieldset>

            <button type="submit" class="submit-btn"><?php t('create_account_btn'); ?></button>
            <div class="login-link"><?php t('already_have_account'); ?> <a href="#" id="switch-to-login"><?php t('login_here'); ?></a></div>
        </form>
    </div>
</div>