/**
 * ConsuTrade - Sell Page Init
 * Depends on: jQuery
 */

$(function() {
    var $sellerRegisterBtn = $('#sellerRegisterBtn');
    var $createSellerBtn = $('#createSellerBtn');
    var $upgradeBtn = $('#upgradeToSellerBtn');
    var $upgradeBtn2 = $('#upgradeToSellerBtn2');
    var $registerModal = $('#register-modal');
    var $sellerRadio = $('#seller');

    function openSellerRegisterModal() {
        if ($sellerRadio.length) {
            $sellerRadio.prop('checked', true);
        }

        if (isLoggedIn && currentUserRole === 'seller') {
            window.location.href = baseUrl + 'admin/seller-dashboard.php';
            return;
        }

        if (isLoggedIn) {
            $('#register-modal .modal-header p').text('Add seller access to your existing account');
        } else {
            $('#register-full-name').val('');
            $('#register-email').val('');
            $('#register-phone').val('');
            $('#register-email').prop('readonly', false);
            $('#register-phone').prop('readonly', false);
            $('#register-modal .modal-header p').text('Create your account to start selling');
        }

        if (typeof openModal === 'function') {
            openModal($registerModal);
        } else {
            $registerModal.addClass('active');
            $registerModal.css('visibility', 'visible');
            $('body').css('overflow', 'hidden');
        }
    }

    if ($sellerRegisterBtn.length) $sellerRegisterBtn.on('click', openSellerRegisterModal);
    if ($createSellerBtn.length) $createSellerBtn.on('click', openSellerRegisterModal);
    if ($upgradeBtn.length) $upgradeBtn.on('click', openSellerRegisterModal);
    if ($upgradeBtn2.length) $upgradeBtn2.on('click', openSellerRegisterModal);
});