$(function() {
    var $sellerRegisterBtn = $('#sellerRegisterBtn');
    var $createSellerBtn = $('#createSellerBtn');
    var $upgradeBtn = $('#upgradeToSellerBtn');
    var $upgradeBtn2 = $('#upgradeToSellerBtn2');
    var $registerModal = $('#register-modal');
    var $loginModal = $('#login-modal');
    var $sellerRadio = $('#seller');
    var $loginInsteadBtn = $('#loginInsteadBtn');

    function openSellerRegisterModal() {
        // If already a seller, redirect to dashboard
        if (isLoggedIn && currentUserRole === 'seller') {
            window.location.href = baseUrl + 'admin/seller-dashboard.php';
            return;
        }

        // If logged in as buyer, upgrade modal
        if (isLoggedIn && currentUserRole === 'buyer') {
            // Set role to seller
            if ($sellerRadio.length) {
                $sellerRadio.prop('checked', true);
            }

            // Update modal title
            $('#register-modal .modal-header p').text('Add seller access to your existing account');

            // Pre-fill existing user data (these variables should be available)
            // You'll need to pass these from PHP or fetch them
            if (typeof currentUserName !== 'undefined' && currentUserName) {
                $('#register-full-name').val(currentUserName).prop('readonly', true);
            }
            if (typeof currentUserEmail !== 'undefined' && currentUserEmail) {
                $('#register-email').val(currentUserEmail).prop('readonly', true);
            }
            if (typeof currentUserPhone !== 'undefined' && currentUserPhone) {
                $('#register-phone').val(currentUserPhone).prop('readonly', true);
            }

            // Hide password fields (they already have an account)
            // The user still needs to enter their password to confirm identity
            // Show password fields with a different label
            $('#register-password').attr('placeholder', 'Enter your password to confirm').prop('required', true);
            $('#register-confirm-password').prop('required', true);

            // Update submit button text
            $('.submit-btn', $registerModal).text('Upgrade to Seller');

            openModal($registerModal);
            return;
        }

        // Not logged in - full registration
        $('#register-full-name').val('').prop('readonly', false);
        $('#register-email').val('').prop('readonly', false);
        $('#register-phone').val('').prop('readonly', false);
        $('#register-password').attr('placeholder', 'Password').prop('required', true);
        $('#register-confirm-password').prop('required', true);
        $('.submit-btn', $registerModal).text('Create Account');
        $('#register-modal .modal-header p').text('Create your account to start selling');

        if ($sellerRadio.length) {
            $sellerRadio.prop('checked', true);
        }

        openModal($registerModal);
    }

    // Register/Upgrade buttons
    if ($sellerRegisterBtn.length) $sellerRegisterBtn.on('click', openSellerRegisterModal);
    if ($createSellerBtn.length) $createSellerBtn.on('click', openSellerRegisterModal);
    if ($upgradeBtn.length) $upgradeBtn.on('click', openSellerRegisterModal);
    if ($upgradeBtn2.length) $upgradeBtn2.on('click', openSellerRegisterModal);

    // Login instead button
    if ($loginInsteadBtn.length) {
        $loginInsteadBtn.on('click', function() {
            if (typeof openModal === 'function') {
                openModal($loginModal);
            } else {
                $loginModal.addClass('active').css('visibility', 'visible');
                $('body').css('overflow', 'hidden');
            }
        });
    }

    // Also handle login redirect for existing users
    $('#loginBtn, #mobileLoginBtn').on('click', function() {
        if (typeof openModal === 'function') {
            openModal($loginModal);
        }
    });
});