/**
 * ConsuTrade - Authentication Module
 * Login/register modals, password toggle, modal controls
 * Depends on: jQuery, core/ui.js
 */

// ============================================================
// PASSWORD TOGGLE
// ============================================================

function togglePassword(fieldId, button) {
    var $input = $('#' + fieldId);
    var $img = $(button).find('img');

    if ($input.attr('type') == 'password') {
        $input.attr('type', 'text');
        $img.attr('src', baseUrl + 'images/icons/eye-close-svgrepo-com.svg');
        $img.attr('alt', 'Hide password');
    } else {
        $input.attr('type', 'password');
        $img.attr('src', baseUrl + 'images/icons/eye-open-svgrepo-com.svg');
        $img.attr('alt', 'Show password');
    }
}

// ============================================================
// ERROR CLEARING
// ============================================================

function clearLoginErrors() {
    $('#login-error-container').hide().empty();
    $('#login-form .input-group').removeClass('error');
    $('#login-form .error-text').remove();
}

function clearRegisterErrors() {
    $('#register-error-container').hide().empty();
    $('#register-form .input-group').removeClass('error');
    $('#register-form .error-text').remove();
}

// ============================================================
// AJAX LOGIN
// ============================================================

function initAjaxLogin() {
    var $loginForm = $('#login-form');
    if (!$loginForm.length) return;

    $loginForm.off('submit').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();
        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.text();

        $submitBtn.prop('disabled', true).text('Logging in...');

        $.ajax({
            url: baseUrl + 'php/endpoints/auth/login.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.redirect;
                } else {
                    displayModalErrors('#login-modal', { general: response.message }, { email: $('#login-email').val() });
                    
                    // Show resend verification link if needed
                    if (response.needs_verification) {
                        $('#login-error-container').append(
                            '<p style="margin-top:8px;"><a href="#" id="resendVerificationLink">Resend verification email</a></p>'
                        );
                        
                        $('#resendVerificationLink').on('click', function(e) {
                            e.preventDefault();
                            var email = response.email || $('#login-email').val();
                            $.ajax({
                                url: baseUrl + 'php/endpoints/users/resend-verification.php',
                                type: 'POST',
                                data: { email: email },
                                dataType: 'json',
                                success: function(res) {
                                    $('#login-error-container').html(res.message)
                                        .removeClass('error-container')
                                        .addClass(res.success ? 'success-message' : 'error-message');
                                }
                            });
                        });
                    }
                    
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            }
        });
    });
}

// ============================================================
// AJAX REGISTER
// ============================================================

function initAjaxRegister() {
    var $registerForm = $('#register-form');
    if (!$registerForm.length) return;

    $registerForm.off('submit').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();
        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.text();

        $submitBtn.prop('disabled', true).text('Creating account...');

        $.ajax({
            url: baseUrl + 'php/endpoints/auth/register.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.redirect;
                } else {
                    displayModalErrors('#register-modal', response.errors, response.form_data);
                    if (response.errors && response.errors.general) {
                        $('#register-error-container').show().text(response.errors.general);
                    }
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                $('#register-error-container').show().text('Something went wrong. Please try again.');
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
}

// ============================================================
// MODAL CONTROLS
// ============================================================

function initModalControls() {
    var $registerModal = $('#register-modal');
    var $loginModal = $('#login-modal');
    var $deleteModal = $('#delete-modal');
    var $registerBtns = $('#registerBtn, #mobileRegisterBtn');
    var $loginBtns = $('#loginBtn, #mobileLoginBtn');
    var $modalCloseBtns = $('.modal-close, .btn-close');
    var $switchToRegister = $('#switch-to-register');
    var $switchToLogin = $('#switch-to-login');

    if ($registerBtns.length) {
        $registerBtns.on('click', function(e) {
            e.preventDefault();
            if ($loginModal.hasClass('active')) closeModal($loginModal);
            openModal($registerModal);
        });
    }

    if ($loginBtns.length) {
        $loginBtns.on('click', function(e) {
            e.preventDefault();
            if ($registerModal.hasClass('active')) closeModal($registerModal);
            openModal($loginModal);
        });
    }

    if ($modalCloseBtns.length) {
        $modalCloseBtns.on('click', function() {
            closeModal($registerModal);
            closeModal($loginModal);
            closeModal($deleteModal);
        });
    }

    $registerModal.on('click', function(e) {
        if ($(e.target).is($registerModal)) closeModal($registerModal);
    });

    $loginModal.on('click', function(e) {
        if ($(e.target).is($loginModal)) closeModal($loginModal);
    });

    if ($deleteModal.length) {
        $deleteModal.on('click', function(e) {
            if ($(e.target).is($deleteModal)) closeModal($deleteModal);
        });
    }

    if ($switchToRegister.length) {
        $switchToRegister.on('click', function(e) {
            e.preventDefault();
            clearLoginErrors();
            closeModal($loginModal);
            setTimeout(function() { openModal($registerModal); }, 300);
        });
    }

    if ($switchToLogin.length) {
        $switchToLogin.on('click', function(e) {
            e.preventDefault();
            clearRegisterErrors();
            closeModal($registerModal);
            setTimeout(function() { openModal($loginModal); }, 300);
        });
    }
}