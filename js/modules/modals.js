/**
 * ConsuTrade - Modal Management
 * Handles all modal rendering, opening, closing, and form handling
 * Depends on: jQuery, utils.js, ui.js
 */

// ============================================================
// MODAL HTML TEMPLATES
// ============================================================

function getModalTemplates() {
    return {
        // Login Modal
        login: function() {
            return `
                <div id="login-modal" class="modal">
                    <div class="modal-content">
                        <button class="btn-close"></button>
                        <div class="modal-header">
                            <h1>Consu<span>Trade</span></h1>
                            <p>${t('welcome_back')}</p>
                        </div>
                        <div id="login-error-container" class="error-container" style="display: none;"></div>
                        <form id="login-form" class="login-form" method="POST" action="${baseUrl}php/endpoints/auth/login.php">
                            <input type="hidden" name="role_type" value="buyer">
                            <div class="input-group">
                                <label for="login-email">${t('email_address')}</label>
                                <input type="email" id="login-email" name="email" placeholder="${t('email_address')}" required>
                            </div>
                            <div class="input-group">
                                <label for="login-password">${t('password')}</label>
                                <div class="password-field-wrapper">
                                    <input type="password" id="login-password" name="password" placeholder="${t('password')}" required>
                                    <button type="button" class="password-toggle-btn" onclick="togglePassword('login-password', this)">
                                        <img src="${baseUrl}images/icons/eye-open-svgrepo-com.svg" width="18" height="18">
                                    </button>
                                </div>
                            </div>
                            <div class="reset-pass"><a href="#" id="forgotPasswordLink">${t('forgot_password')}</a></div>
                            <button type="submit" class="submit-btn">${t('login')}</button>
                            <div class="register-link">${t('no_account')} <a href="#" id="switch-to-register">${t('register_here')}</a></div>
                        </form>
                    </div>
                </div>
            `;
        },
        
        // Register Modal
        register: function() {
            return `
                <div id="register-modal" class="modal">
                    <div class="modal-content">
                        <button class="btn-close"></button>
                        <div class="modal-header">
                            <h1>Consu<span>Trade</span></h1>
                            <p>${t('create_account')}</p>
                        </div>
                        <div id="register-error-container" class="error-container" style="display: none;"></div>
                        <form id="register-form" class="register-form" method="POST" action="${baseUrl}php/endpoints/auth/register.php">
                            <div class="input-group">
                                <label for="register-full-name">${t('full_name')}</label>
                                <input type="text" id="register-full-name" name="full_name" placeholder="${t('full_name')}" required>
                            </div>
                            <div class="input-group">
                                <label for="register-email">${t('email_address')}</label>
                                <input type="email" id="register-email" name="email" placeholder="${t('email_address')}" required>
                            </div>
                            <div class="input-group">
                                <label for="register-phone">${t('phone_number')}</label>
                                <input type="tel" id="register-phone" name="phone" placeholder="${t('phone_number')}" required>
                            </div>
                            <div class="input-group">
                                <label for="register-password">${t('password')}</label>
                                <div class="password-field-wrapper">
                                    <input type="password" id="register-password" name="password" placeholder="${t('password')}" required>
                                    <button type="button" class="password-toggle-btn" onclick="togglePassword('register-password', this)">
                                        <img src="${baseUrl}images/icons/eye-open-svgrepo-com.svg" width="18" height="18">
                                    </button>
                                </div>
                            </div>
                            <div class="input-group">
                                <label for="register-confirm-password">${t('confirm_password')}</label>
                                <div class="password-field-wrapper">
                                    <input type="password" id="register-confirm-password" name="confirm_password" placeholder="${t('confirm_password')}" required>
                                    <button type="button" class="password-toggle-btn" onclick="togglePassword('register-confirm-password', this)">
                                        <img src="${baseUrl}images/icons/eye-open-svgrepo-com.svg" width="18" height="18">
                                    </button>
                                </div>
                            </div>
                            <fieldset class="user-type">
                                <legend>${t('i_want_to')}</legend>
                                <div class="radio-buttons">
                                    <input type="radio" id="buyer" name="role" value="buyer" checked>
                                    <label for="buyer" class="radio-btn radio">${t('buy_products')}</label>
                                    <input type="radio" id="seller" name="role" value="seller">
                                    <label for="seller" class="radio-btn radio">${t('sell_products')}</label>
                                </div>
                            </fieldset>
                            <button type="submit" class="submit-btn">${t('create_account_btn')}</button>
                            <div class="login-link">${t('already_have_account')} <a href="#" id="switch-to-login">${t('login_here')}</a></div>
                        </form>
                    </div>
                </div>
            `;
        },
        
        // Delete Confirmation Modal
        deleteConfirm: function() {
            return `
                <div id="delete-modal" class="modal">
                    <div class="modal-content" style="max-width: 400px;">
                        <button class="btn-close"></button>
                        <div class="modal-header">
                            <h1>${t('confirm_delete')}</h1>
                            <p>${t('confirm_delete_text')}</p>
                        </div>
                        <div style="display: flex; gap: var(--spacing-md); justify-content: flex-end; margin-top: var(--spacing-lg);">
                            <button class="btn-secondary" id="cancelDeleteBtn">${t('cancel')}</button>
                            <button class="btn-danger" id="confirmDeleteBtn">${t('delete')}</button>
                        </div>
                    </div>
                </div>
            `;
        }
    };
}

// ============================================================
// MODAL INJECTION
// ============================================================

var modalsInjected = false;

/**
 * Inject all modals into the DOM
 */
function injectModals() {
    if (modalsInjected) return;
    
    var templates = getModalTemplates();
    var modalHtml = '';
    
    // Add all modals
    modalHtml += templates.login();
    modalHtml += templates.register();
    modalHtml += templates.deleteConfirm();
    
    // Append to body
    $('body').append(modalHtml);
    modalsInjected = true;
    
    // Initialize modal event listeners
    initModalEvents();
}

/**
 * Initialize all modal event listeners
 */
function initModalEvents() {
    // Close buttons
    $('.btn-close').on('click', function() {
        var $modal = $(this).closest('.modal');
        closeModal($modal);
    });
    
    // Click outside modal to close
    $('.modal').on('click', function(e) {
        if ($(e.target).is('.modal')) {
            closeModal($(this));
        }
    });
    
    // Switch between login/register
    $('#switch-to-register').on('click', function(e) {
        e.preventDefault();
        closeModal($('#login-modal'));
        setTimeout(function() {
            openModal($('#register-modal'));
        }, 300);
    });
    
    $('#switch-to-login').on('click', function(e) {
        e.preventDefault();
        closeModal($('#register-modal'));
        setTimeout(function() {
            openModal($('#login-modal'));
        }, 300);
    });
    
    // Forgot password
    $('#forgotPasswordLink').on('click', function(e) {
        e.preventDefault();
        showInfoToast('Password reset link will be sent to your email');
    });
    
    // Delete modal buttons
    $('#cancelDeleteBtn').on('click', function() {
        closeModal($('#delete-modal'));
    });
    
    $('#confirmDeleteBtn').on('click', function() {
        closeModal($('#delete-modal'));
    });
    
    // ESC key to close modals
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            var $activeModal = $('.modal.active');
            if ($activeModal.length) {
                closeModal($activeModal);
            }
        }
    });
}

// ============================================================
// MODAL HELPERS
// ============================================================

/**
 * Open a delete confirmation modal
 * @param {string} message - Confirmation message
 * @param {function} onConfirm - Callback when confirmed
 */
function openDeleteModal(message, onConfirm) {
    $('#delete-modal .modal-header p').text(message || t('confirm_delete_text'));
    $('#confirmDeleteBtn').off('click').on('click', function() {
        closeModal($('#delete-modal'));
        if (typeof onConfirm === 'function') {
            onConfirm();
        }
    });
    openModal($('#delete-modal'));
}

/**
 * Clear all modal forms
 */
function clearAllModals() {
    if ($('#login-form').length) $('#login-form')[0].reset();
    if ($('#register-form').length) $('#register-form')[0].reset();
    $('#login-error-container').hide().empty();
    $('#register-error-container').hide().empty();
    $('.input-group').removeClass('error');
    $('.error-text').remove();
}

// ============================================================
// TRANSLATION HELPER (if t() not available)
// ============================================================

/**
 * Get translation or fallback to key
 */
function t(key) {
    if (typeof translations !== 'undefined' && translations[key]) {
        return translations[key];
    }
    // Fallback translations for common keys
    var fallbacks = {
        'welcome_back': 'Welcome Back',
        'email_address': 'Email Address',
        'password': 'Password',
        'forgot_password': 'Forgot Password?',
        'no_account': "Don't have an account?",
        'register_here': 'Register here',
        'login': 'Login',
        'create_account': 'Create Account',
        'full_name': 'Full Name',
        'phone_number': 'Phone Number',
        'confirm_password': 'Confirm Password',
        'i_want_to': 'I want to',
        'buy_products': 'Buy Products',
        'sell_products': 'Sell Products',
        'create_account_btn': 'Create Account',
        'already_have_account': 'Already have an account?',
        'login_here': 'Login here',
        'confirm_delete': 'Confirm Delete',
        'confirm_delete_text': 'Are you sure you want to delete this? This action cannot be undone.',
        'cancel': 'Cancel',
        'delete': 'Delete'
    };
    return fallbacks[key] || key;
}

// ============================================================
// AUTO-INJECT ON PAGE LOAD
// ============================================================

// Inject modals immediately when DOM is ready
$(function() {
    injectModals();
});